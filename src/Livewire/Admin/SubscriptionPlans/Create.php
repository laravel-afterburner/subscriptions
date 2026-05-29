<?php

namespace Afterburner\Subscriptions\Livewire\Admin\SubscriptionPlans;

use Afterburner\Subscriptions\Actions\Stripe\SyncSubscriptionPlanToStripe;
use Afterburner\Subscriptions\Http\Requests\SaveSubscriptionPlanRequest;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\PlanFeaturesInput;
use Afterburner\Subscriptions\Support\PlanPriceInput;
use Illuminate\Support\Str;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $currency = '';

    public string $monthly_price = '';

    public string $annual_price = '';

    public int $trial_days = 30;

    public bool $is_active = true;

    public int $sort_order = 0;

    public ?int $max_users_per_team = null;

    public ?int $max_storage_gb = null;

    /** @var array<int, string> */
    public array $feature_slugs = [];

    public function mount(): void
    {
        $this->authorize('create', SubscriptionPlan::class);
        $this->trial_days = (int) config('afterburner-subscriptions.default_trial_days', 30);
        $this->currency = config('afterburner-subscriptions.currency', 'usd');
    }

    public function updatedName(string $value): void
    {
        if ($this->slug === '') {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
    {
        $this->authorize('create', SubscriptionPlan::class);

        $validated = $this->validate(
            SaveSubscriptionPlanRequest::formRulesFor(),
            SaveSubscriptionPlanRequest::formValidationMessages()
        );

        $validated['features'] = PlanFeaturesInput::fromForm(
            $this->max_users_per_team,
            $this->max_storage_gb,
            $this->feature_slugs
        );

        $plan = SubscriptionPlan::query()->create(
            SaveSubscriptionPlanRequest::toPlanAttributes($validated)
        );

        app(SyncSubscriptionPlanToStripe::class)($plan);

        session()->flash('flash', [
            'bannerStyle' => 'success',
            'banner' => 'Subscription plan created and synced to Stripe.',
        ]);

        $this->redirectRoute('admin.subscription-plans.show', $plan);
    }

    public function render()
    {
        return view('afterburner-subscriptions::admin.subscription-plans.livewire.create', [
            'knownFeatureSlugs' => config('afterburner-subscriptions.known_feature_slugs', []),
            'supportedCurrencies' => config('afterburner-subscriptions.supported_currencies', []),
        ]);
    }
}
