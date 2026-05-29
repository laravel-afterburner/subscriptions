<?php

namespace Afterburner\Subscriptions\Livewire\Admin\SubscriptionPlans;

use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\PlanFeaturesInput;
use Livewire\Component;

class Show extends Component
{
    public SubscriptionPlan $plan;

    public function mount(SubscriptionPlan $plan): void
    {
        $this->authorize('view', $plan);

        $this->plan = $plan;
    }

    public function editPlan(): void
    {
        $this->authorize('update', $this->plan);

        $this->redirectRoute('admin.subscription-plans.edit', $this->plan);
    }

    public function render()
    {
        return view('afterburner-subscriptions::admin.subscription-plans.livewire.show', [
            'features' => PlanFeaturesInput::toForm($this->plan->features),
            'canEdit' => auth()->user()?->can('update', $this->plan) ?? false,
        ]);
    }
}
