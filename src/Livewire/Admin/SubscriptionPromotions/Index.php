<?php

namespace Afterburner\Subscriptions\Livewire\Admin\SubscriptionPromotions;

use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', SubscriptionPromotionCode::class);
    }

    public function createPromotion(): void
    {
        $this->authorize('create', SubscriptionPromotionCode::class);

        $this->redirectRoute('admin.subscription-plans.promotion-codes.create');
    }

    public function showPromotion(int $promotionId): void
    {
        $promotion = SubscriptionPromotionCode::query()->findOrFail($promotionId);

        $this->authorize('view', $promotion);

        $this->redirectRoute('admin.subscription-plans.promotion-codes.show', $promotion);
    }

    public function editPromotion(int $promotionId): void
    {
        $promotion = SubscriptionPromotionCode::query()->findOrFail($promotionId);

        $this->authorize('update', $promotion);

        $this->redirectRoute('admin.subscription-plans.promotion-codes.edit', $promotion);
    }

    public function render()
    {
        return view('afterburner-subscriptions::admin.subscription-promotions.livewire.index', [
            'promotions' => SubscriptionPromotionCode::query()
                ->with('subscriptionPlan')
                ->orderByDesc('created_at')
                ->paginate(10),
        ]);
    }
}
