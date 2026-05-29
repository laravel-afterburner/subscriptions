<?php

namespace Afterburner\Subscriptions\Actions\Stripe;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription;

class SyncStripeSubscriptionToDatabase
{
    /**
     * Mirror Cashier's customer.subscription.created / updated webhook handling.
     *
     * @param  array<string, mixed>  $data  Stripe subscription payload (object array)
     */
    public function __invoke(Model $team, array $data): Subscription
    {
        $subscriptionName = config('cashier.subscription_name', 'default');

        if (isset($data['trial_end'])) {
            $trialEndsAt = Carbon::createFromTimestamp($data['trial_end']);
        } else {
            $trialEndsAt = null;
        }

        $firstItem = $data['items']['data'][0] ?? null;
        $isSinglePrice = isset($data['items']['data']) && count($data['items']['data']) === 1;

        $existing = $team->subscriptions()->where('stripe_id', $data['id'])->first();

        $subscription = $team->subscriptions()->updateOrCreate([
            'stripe_id' => $data['id'],
        ], [
            'type' => $existing?->type
                ?? $data['metadata']['type']
                ?? $data['metadata']['name']
                ?? $subscriptionName,
            'stripe_status' => $data['status'],
            'stripe_price' => $isSinglePrice && $firstItem ? $firstItem['price']['id'] : null,
            'quantity' => $isSinglePrice && $firstItem && isset($firstItem['quantity']) ? $firstItem['quantity'] : null,
            'trial_ends_at' => $trialEndsAt,
            'ends_at' => $this->resolveEndsAt($existing ?? new Subscription, $data, $trialEndsAt),
        ]);

        if (isset($data['items']['data'])) {
            $subscriptionItemIds = [];

            foreach ($data['items']['data'] as $item) {
                $subscriptionItemIds[] = $item['id'];

                $subscription->items()->updateOrCreate([
                    'stripe_id' => $item['id'],
                ], [
                    'stripe_product' => $item['price']['product'],
                    'stripe_price' => $item['price']['id'],
                    'quantity' => $item['quantity'] ?? null,
                ]);
            }

            $subscription->items()->whereNotIn('stripe_id', $subscriptionItemIds)->delete();
        }

        if (! is_null($team->trial_ends_at)) {
            $team->forceFill(['trial_ends_at' => null])->save();
        }

        return $subscription;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveEndsAt(Subscription $subscription, array $data, ?Carbon $trialEndsAt): ?Carbon
    {
        if ($data['cancel_at_period_end'] ?? false) {
            return $subscription->onTrial()
                ? $trialEndsAt
                : Carbon::createFromTimestamp($data['current_period_end'] ?? $data['items']['data'][0]['current_period_end']);
        }

        if (isset($data['cancel_at']) || isset($data['canceled_at'])) {
            return Carbon::createFromTimestamp($data['cancel_at'] ?? $data['canceled_at']);
        }

        return null;
    }
}
