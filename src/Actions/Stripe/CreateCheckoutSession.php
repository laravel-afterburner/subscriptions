<?php

namespace Afterburner\Subscriptions\Actions\Stripe;

use Afterburner\Subscriptions\Enums\BillingInterval;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;

class CreateCheckoutSession
{
    /**
     * @return \Laravel\Cashier\Checkout|null
     */
    public function __invoke(
        Model $team,
        SubscriptionPlan $plan,
        BillingInterval $interval,
        ?string $promotionCode = null
    ) {
        $priceId = $plan->stripePriceIdFor($interval);

        if ($priceId === null || $priceId === '') {
            throw new \RuntimeException('This plan has not been synced to Stripe yet.');
        }

        if (! method_exists($team, 'newSubscription')) {
            throw new \RuntimeException('Entity model must use HasSubscriptions.');
        }

        $subscriptionName = config('cashier.subscription_name', 'default');

        $builder = $team->newSubscription($subscriptionName, $priceId);

        if ($team->onGenericTrial()) {
            $builder->trialUntil($team->trial_ends_at);
        }

        if ($promotionCode) {
            $promotion = app(ValidatePromotionCode::class)($promotionCode, $plan);
            $builder->withPromotionCode($promotion->stripe_promotion_code_id);
        } elseif (config('afterburner-subscriptions.allow_checkout_promotion_codes', true)) {
            $builder->allowPromotionCodes();
        }

        $subscriptionsUrl = route('teams.subscriptions.index', $team);

        return $builder->checkout([
            'success_url' => $subscriptionsUrl.'?checkout=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $subscriptionsUrl.'?checkout=cancelled',
            'metadata' => [
                'team_id' => (string) $team->getKey(),
                'subscription_plan_id' => (string) $plan->getKey(),
                'billing_interval' => $interval->value,
            ],
        ]);
    }
}
