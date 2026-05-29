<?php

namespace Afterburner\Subscriptions\Http\Controllers;

use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubscriptionPromotionsAdminController
{
    public function index(): View
    {
        Gate::authorize('viewAny', SubscriptionPromotionCode::class);

        return view('afterburner-subscriptions::admin.subscription-promotions.index');
    }

    public function create(): View
    {
        Gate::authorize('create', SubscriptionPromotionCode::class);

        return view('afterburner-subscriptions::admin.subscription-promotions.create');
    }

    public function show(int $promotion): View
    {
        $promotionCode = SubscriptionPromotionCode::query()->findOrFail($promotion);

        Gate::authorize('view', $promotionCode);

        return view('afterburner-subscriptions::admin.subscription-promotions.show', [
            'promotion' => $promotionCode,
        ]);
    }

    public function edit(int $promotion): View
    {
        $promotionCode = SubscriptionPromotionCode::query()->findOrFail($promotion);

        Gate::authorize('update', $promotionCode);

        return view('afterburner-subscriptions::admin.subscription-promotions.edit', [
            'promotion' => $promotionCode,
        ]);
    }
}
