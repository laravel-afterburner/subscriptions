<?php

namespace Afterburner\Subscriptions\Livewire\Admin\SubscriptionPlans;

use Afterburner\Subscriptions\Actions\Stripe\SyncSubscriptionPlanToStripe;
use Afterburner\Subscriptions\Http\Requests\SaveSubscriptionPlanRequest;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\PlanFeaturesInput;
use Afterburner\Subscriptions\Support\PlanPriceInput;
use Livewire\Component;

class Edit extends Component
{
    public SubscriptionPlan $plan;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $currency = 'cad';

    public string $monthly_price = '';

    public string $annual_price = '';

    public int $trial_days = 30;

    public bool $is_active = true;

    public int $sort_order = 0;

    public ?int $max_users_per_team = null;

    public ?int $max_storage_gb = null;

    /** @var array<int, string> */
    public array $feature_slugs = [];

    public function mount(SubscriptionPlan $plan): void
    {
        $this->authorize('update', $plan);

        $this->plan = $plan;
        $this->name = $plan->name;
        $this->slug = $plan->slug;
        $this->description = $plan->description ?? '';
        $this->currency = $plan->currencyCode();
        $this->monthly_price = PlanPriceInput::centsToDollars($plan->monthly_price_cents);
        $this->annual_price = PlanPriceInput::centsToDollars($plan->annual_price_cents);
        $this->trial_days = $plan->trial_days;
        $this->is_active = $plan->is_active;
        $this->sort_order = $plan->sort_order;

        $features = PlanFeaturesInput::toForm($plan->features);
        $this->max_users_per_team = $features['max_users_per_team'];
        $this->max_storage_gb = $features['max_storage_gb'];
        $this->feature_slugs = $features['feature_slugs'];
    }

    public function save(): void
    {
        $this->authorize('update', $this->plan);

        $validated = $this->validate(
            SaveSubscriptionPlanRequest::formRulesFor($this->plan->id),
            SaveSubscriptionPlanRequest::formValidationMessages()
        );

        $validated['features'] = PlanFeaturesInput::fromForm(
            $this->max_users_per_team,
            $this->max_storage_gb,
            $this->feature_slugs
        );

        $this->plan->update(
            SaveSubscriptionPlanRequest::toPlanAttributes($validated)
        );

        app(SyncSubscriptionPlanToStripe::class)($this->plan->fresh());

        session()->flash('flash', [
            'bannerStyle' => 'success',
            'banner' => 'Subscription plan updated and synced to Stripe.',
        ]);

        $this->redirectRoute('admin.subscription-plans.show', $this->plan);
    }

    public function render()
    {
        return view('afterburner-subscriptions::admin.subscription-plans.livewire.edit', [
            'knownFeatureSlugs' => config('afterburner-subscriptions.known_feature_slugs', []),
            'supportedCurrencies' => config('afterburner-subscriptions.supported_currencies', []),
        ]);
    }
}
