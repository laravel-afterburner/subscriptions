<?php

namespace Afterburner\Subscriptions\Providers;

use Afterburner\Subscriptions\Console\Commands\InstallCommand;
use Afterburner\Subscriptions\Console\Commands\NotifyTrialEndingCommand;
use Afterburner\Subscriptions\Console\Commands\SyncTeamBillingCommand;
use Afterburner\Subscriptions\Database\Seeders\SubscriptionsPermissionsSeeder;
use Afterburner\Subscriptions\Events\SubscriptionCancelled;
use Afterburner\Subscriptions\Events\SubscriptionPaymentFailed;
use Afterburner\Subscriptions\Events\TeamSubscribed;
use Afterburner\Subscriptions\Middleware\EnsureEntitlement;
use Afterburner\Subscriptions\Middleware\EnsureSubscriptionActive;
use Afterburner\Subscriptions\Support\SubscriptionEntitlementGate;
use Afterburner\Subscriptions\Listeners\LogSubscriptionAudit;
use Afterburner\Subscriptions\Listeners\ProcessStripeWebhook;
use Afterburner\Subscriptions\Listeners\SendBillingNotification;
use Afterburner\Subscriptions\Listeners\SendSubscriptionCancelledNotification;
use Afterburner\Subscriptions\Listeners\StartTrialOnTeamCreated;
use Afterburner\Subscriptions\Livewire\Admin\SubscriptionPromotions\Create as CreatePromotion;
use Afterburner\Subscriptions\Livewire\Admin\SubscriptionPromotions\Edit as EditPromotion;
use Afterburner\Subscriptions\Livewire\Admin\SubscriptionPromotions\Index as PromotionsIndex;
use Afterburner\Subscriptions\Livewire\Admin\SubscriptionPromotions\Show as ShowPromotion;
use Illuminate\Console\Scheduling\Schedule;
use Afterburner\Subscriptions\Livewire\Admin\SubscriptionPlans\Create as CreatePlan;
use Afterburner\Subscriptions\Livewire\Admin\SubscriptionPlans\Edit as EditPlan;
use Afterburner\Subscriptions\Livewire\Admin\SubscriptionPlans\Index as PlansIndex;
use Afterburner\Subscriptions\Livewire\Admin\SubscriptionPlans\Show as ShowPlan;
use Afterburner\Subscriptions\Livewire\Teams\SubscriptionManager;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Afterburner\Subscriptions\Policies\SubscriptionPlanPolicy;
use Afterburner\Subscriptions\Policies\SubscriptionPromotionPolicy;
use Afterburner\Playbook\Support\Playbook;
use App\Models\Team;
use App\Support\SystemAdminNavigation;
use App\Support\TeamNavigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;
use Livewire\Livewire;

class SubscriptionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! class_exists(Team::class)) {
            return;
        }

        $this->mergeConfigFrom(
            __DIR__.'/../../config/afterburner-subscriptions.php',
            'afterburner-subscriptions'
        );
    }

    public function boot(): void
    {
        if (! class_exists(Team::class)) {
            return;
        }

        if (! config('afterburner-subscriptions.enabled', true)) {
            return;
        }

        Cashier::useCustomerModel(Team::class);

        $this->publishes([
            __DIR__.'/../../config/afterburner-subscriptions.php' => config_path('afterburner-subscriptions.php'),
        ], 'afterburner-subscriptions-config');

        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'afterburner-subscriptions-migrations');

        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/afterburner-subscriptions'),
        ], 'afterburner-subscriptions-assets');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'afterburner-subscriptions');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        $this->registerLivewireComponents();
        $this->registerPolicies();
        $this->registerGates();
        $this->registerMiddleware();
        $this->registerNavigation();
        $this->registerPlaybook();
        $this->registerEventListeners();
        $this->registerPackageSeeder();
        $this->registerSchedule();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                NotifyTrialEndingCommand::class,
                SyncTeamBillingCommand::class,
            ]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('subscriptions.manager', SubscriptionManager::class);
        Livewire::component('subscriptions.admin.plans.index', PlansIndex::class);
        Livewire::component('subscriptions.admin.plans.show', ShowPlan::class);
        Livewire::component('subscriptions.admin.plans.create', CreatePlan::class);
        Livewire::component('subscriptions.admin.plans.edit', EditPlan::class);
        Livewire::component('subscriptions.admin.promotions.index', PromotionsIndex::class);
        Livewire::component('subscriptions.admin.promotions.show', ShowPromotion::class);
        Livewire::component('subscriptions.admin.promotions.create', CreatePromotion::class);
        Livewire::component('subscriptions.admin.promotions.edit', EditPromotion::class);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(SubscriptionPlan::class, SubscriptionPlanPolicy::class);
        Gate::policy(SubscriptionPromotionCode::class, SubscriptionPromotionPolicy::class);
    }

    protected function registerGates(): void
    {
        Gate::define('manageBilling', function ($user, Model $team) {
            return app(SubscriptionPlanPolicy::class)->manageBilling($user, $team);
        });

        Gate::define('viewBilling', function ($user, Model $team) {
            return app(SubscriptionPlanPolicy::class)->viewBilling($user, $team);
        });

        Gate::define('teamEntitlement', function ($user, Model $team, string $featureSlug) {
            return SubscriptionEntitlementGate::allows($team, $featureSlug);
        });
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('subscription.entitlement', EnsureEntitlement::class);
        $router->aliasMiddleware('subscription.active', EnsureSubscriptionActive::class);
    }

    protected function registerNavigation(): void
    {
        if (class_exists(TeamNavigation::class)) {
            TeamNavigation::register([
                'label' => 'Subscriptions',
                'route' => 'teams.subscriptions.index',
                'order' => 15,
                'route_params' => function () {
                    $user = auth()->user();
                    if (! $user || ! $user->currentTeam) {
                        return [];
                    }

                    return ['team' => $user->currentTeam->id];
                },
                'permission' => function ($user) {
                    if (! $user || ! $user->currentTeam) {
                        return false;
                    }

                    return $user->can('viewBilling', $user->currentTeam);
                },
                'active' => fn () => request()->routeIs('teams.subscriptions.*'),
            ]);
        }

        if (class_exists(SystemAdminNavigation::class)) {
            SystemAdminNavigation::register([
                'label' => 'Subscription Plans',
                'route' => 'admin.subscription-plans.index',
                'order' => 10,
                'active' => fn () => request()->routeIs('admin.subscription-plans.*'),
            ]);
        }
    }

    protected function registerPlaybook(): void
    {
        if (! class_exists(Playbook::class)) {
            return;
        }

        Playbook::register([
            'key' => 'subscriptions',
            'label' => 'Subscriptions',
            'order' => 40,
            'path' => __DIR__.'/../../playbook',
            'enabled' => fn () => config('afterburner-subscriptions.enabled', true),
            'permission' => fn ($user) => $user?->currentTeam
                && $user->can('viewBilling', $user->currentTeam),
        ]);
    }

    protected function registerEventListeners(): void
    {
        Event::listen(WebhookReceived::class, ProcessStripeWebhook::class);
        Event::listen(SubscriptionPaymentFailed::class, SendBillingNotification::class);
        Event::listen(SubscriptionCancelled::class, SendSubscriptionCancelledNotification::class);

        $auditListener = LogSubscriptionAudit::class;
        Event::listen(SubscriptionPaymentFailed::class, [$auditListener, 'handlePaymentFailed']);
        Event::listen(SubscriptionCancelled::class, [$auditListener, 'handleCancelled']);
        Event::listen(TeamSubscribed::class, [$auditListener, 'handleSubscribed']);

        if (class_exists(\App\Events\TeamCreated::class)) {
            Event::listen(\App\Events\TeamCreated::class, StartTrialOnTeamCreated::class);
        }
    }

    protected function registerPackageSeeder(): void
    {
        if (! class_exists(\App\Support\PackageSeederRegistry::class)) {
            return;
        }

        \App\Support\PackageSeederRegistry::register(SubscriptionsPermissionsSeeder::class);
    }

    protected function registerSchedule(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('afterburner:subscriptions:notify-trial-ending')->daily();
        });
    }
}
