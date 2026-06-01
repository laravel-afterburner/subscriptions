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
