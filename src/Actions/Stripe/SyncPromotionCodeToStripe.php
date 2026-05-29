<?php

namespace Afterburner\Subscriptions\Actions\Stripe;

use Afterburner\Subscriptions\Enums\PromotionDuration;
use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Stripe\Coupon;
use Stripe\PromotionCode;
use Stripe\Stripe;

class SyncPromotionCodeToStripe
{
    public function __invoke(SubscriptionPromotionCode $promotion): SubscriptionPromotionCode
    {
        Stripe::setApiKey(config('afterburner-subscriptions.stripe.secret'));

        $currency = config('afterburner-subscriptions.currency', 'usd');

        if (! $promotion->stripe_coupon_id) {
            $couponPayload = array_filter([
                'name' => $promotion->name,
                'duration' => $promotion->duration instanceof PromotionDuration
                    ? $promotion->duration->value
                    : $promotion->duration,
                'duration_in_months' => $promotion->duration === PromotionDuration::Repeating
                    ? $promotion->duration_in_months
                    : null,
                'max_redemptions' => $promotion->max_redemptions,
                'redeem_by' => $promotion->redeem_by?->getTimestamp(),
                'metadata' => [
                    'subscription_promotion_code_id' => (string) $promotion->getKey(),
                ],
            ]);

            if ($promotion->percent_off !== null) {
                $couponPayload['percent_off'] = $promotion->percent_off;
            } else {
                $couponPayload['amount_off'] = $promotion->amount_off_cents;
                $couponPayload['currency'] = $currency;
            }

            $coupon = Coupon::create($couponPayload);
            $promotion->stripe_coupon_id = $coupon->id;
        }

        if ($promotion->stripe_promotion_code_id) {
            PromotionCode::update($promotion->stripe_promotion_code_id, [
                'active' => $promotion->is_active,
            ]);
        } else {
            $stripePromotion = PromotionCode::create([
                'coupon' => $promotion->stripe_coupon_id,
                'code' => $promotion->code,
                'active' => $promotion->is_active,
                'metadata' => [
                    'subscription_promotion_code_id' => (string) $promotion->getKey(),
                ],
            ]);

            $promotion->stripe_promotion_code_id = $stripePromotion->id;
        }

        $promotion->save();

        return $promotion->fresh();
    }
}
