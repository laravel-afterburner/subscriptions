<?php

namespace Afterburner\Subscriptions\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubscriptionPlansAdminController
{
    public function index(): View
    {
        Gate::authorize('viewAny', \Afterburner\Subscriptions\Models\SubscriptionPlan::class);

        return view('afterburner-subscriptions::admin.subscription-plans.index');
    }

    public function create(): View
    {
        Gate::authorize('create', \Afterburner\Subscriptions\Models\SubscriptionPlan::class);

        return view('afterburner-subscriptions::admin.subscription-plans.create');
    }

    public function show(int $plan): View
    {
        $subscriptionPlan = \Afterburner\Subscriptions\Models\SubscriptionPlan::query()->findOrFail($plan);

        Gate::authorize('view', $subscriptionPlan);

        return view('afterburner-subscriptions::admin.subscription-plans.show', [
            'plan' => $subscriptionPlan,
        ]);
    }

    public function edit(int $plan): View
    {
        $subscriptionPlan = \Afterburner\Subscriptions\Models\SubscriptionPlan::query()->findOrFail($plan);

        Gate::authorize('update', $subscriptionPlan);

        return view('afterburner-subscriptions::admin.subscription-plans.edit', [
            'plan' => $subscriptionPlan,
        ]);
    }
}
