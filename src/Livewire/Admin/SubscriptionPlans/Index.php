<?php

namespace Afterburner\Subscriptions\Livewire\Admin\SubscriptionPlans;

use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\PlanSubscriberStats;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', SubscriptionPlan::class);
    }

    public function createPlan(): void
    {
        $this->authorize('create', SubscriptionPlan::class);

        $this->redirectRoute('admin.subscription-plans.create');
    }

    public function showPlan(int $planId): void
    {
        $plan = SubscriptionPlan::query()->findOrFail($planId);

        $this->authorize('view', $plan);

        $this->redirectRoute('admin.subscription-plans.show', $plan);
    }

    public function editPlan(int $planId): void
    {
        $plan = SubscriptionPlan::query()->findOrFail($planId);

        $this->authorize('update', $plan);

        $this->redirectRoute('admin.subscription-plans.edit', $plan);
    }

    public function render()
    {
        $plans = SubscriptionPlan::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10);

        return view('afterburner-subscriptions::admin.subscription-plans.livewire.index', [
            'plans' => $plans,
            'subscriberStats' => PlanSubscriberStats::forPlans($plans->getCollection()),
        ]);
    }
}
