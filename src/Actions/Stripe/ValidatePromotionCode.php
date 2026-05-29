<?php

namespace Afterburner\Subscriptions\Actions\Stripe;

use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Illuminate\Validation\ValidationException;

class ValidatePromotionCode
{
    public function __invoke(string $code, ?SubscriptionPlan $plan = null): SubscriptionPromotionCode
    {
        $promotion = SubscriptionPromotionCode::query()
            ->where('code', strtoupper(trim($code)))
            ->first();

        if (! $promotion) {
            throw ValidationException::withMessages([
                'promotionCode' => 'This promotion code is not valid.',
            ]);
        }

        if (! $promotion->isRedeemable()) {
            throw ValidationException::withMessages([
                'promotionCode' => 'This promotion code is no longer available.',
            ]);
        }

        if (! $promotion->appliesToPlan($plan)) {
            throw ValidationException::withMessages([
                'promotionCode' => 'This promotion code does not apply to the selected plan.',
            ]);
        }

        return $promotion;
    }
}
