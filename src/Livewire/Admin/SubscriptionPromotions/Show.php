<?php

namespace Afterburner\Subscriptions\Livewire\Admin\SubscriptionPromotions;

use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Livewire\Component;

class Show extends Component
{
    public SubscriptionPromotionCode $promotion;

    public function mount(SubscriptionPromotionCode $promotion): void
    {
        $this->authorize('view', $promotion);

        $this->promotion = $promotion->load('subscriptionPlan');
    }

    public function editPromotion(): void
    {
        $this->authorize('update', $this->promotion);

        $this->redirectRoute('admin.subscription-promotions.edit', $this->promotion);
    }

    public function render()
    {
        return view('afterburner-subscriptions::admin.subscription-promotions.livewire.show', [
            'canEdit' => auth()->user()?->can('update', $this->promotion) ?? false,
        ]);
    }
}
