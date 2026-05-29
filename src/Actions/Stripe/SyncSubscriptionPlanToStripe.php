<?php

namespace Afterburner\Subscriptions\Actions\Stripe;

use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\StripeProductPayload;
use Illuminate\Support\Facades\Log;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class SyncSubscriptionPlanToStripe
{
    public function __invoke(SubscriptionPlan $plan): SubscriptionPlan
    {
        Stripe::setApiKey(config('afterburner-subscriptions.stripe.secret'));

        $currency = $plan->currencyCode();

        if ($plan->stripe_product_id) {
            Product::update($plan->stripe_product_id, StripeProductPayload::forPlan($plan));
        } else {
            $product = Product::create(StripeProductPayload::forPlan($plan) + [
                'metadata' => [
                    'subscription_plan_id' => (string) $plan->getKey(),
                    'slug' => $plan->slug,
                ],
            ]);

            $plan->stripe_product_id = $product->id;
        }

        $plan->stripe_price_id_monthly = $this->syncPrice(
            $plan->stripe_price_id_monthly,
            $plan->stripe_product_id,
            $plan->monthly_price_cents,
            'month',
            $currency
        );

        $plan->stripe_price_id_annual = $this->syncPrice(
            $plan->stripe_price_id_annual,
            $plan->stripe_product_id,
            $plan->annual_price_cents,
            'year',
            $currency
        );

        $plan->save();

        return $plan->fresh();
    }

    protected function syncPrice(
        ?string $existingPriceId,
        string $productId,
        int $amountCents,
        string $interval,
        string $currency
    ): string {
        if ($existingPriceId) {
            try {
                $existing = Price::retrieve($existingPriceId);

                if ((int) $existing->unit_amount === $amountCents
                    && $existing->currency === $currency
                    && $existing->recurring?->interval === $interval
                    && $existing->active) {
                    return $existingPriceId;
                }

                Price::update($existingPriceId, ['active' => false]);
            } catch (\Throwable $exception) {
                Log::warning('Unable to retrieve existing Stripe price.', [
                    'price_id' => $existingPriceId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $price = Price::create([
            'product' => $productId,
            'unit_amount' => $amountCents,
            'currency' => $currency,
            'recurring' => ['interval' => $interval],
        ]);

        return $price->id;
    }
}
