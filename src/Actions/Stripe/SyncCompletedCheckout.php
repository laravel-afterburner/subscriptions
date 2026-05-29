<?php

namespace Afterburner\Subscriptions\Actions\Stripe;

use Afterburner\Subscriptions\Events\TeamSubscribed;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;

class SyncCompletedCheckout
{
    public function __construct(
        protected SyncStripeSubscriptionToDatabase $syncSubscription,
    ) {}

    public function __invoke(Model $team, string $sessionId): bool
    {
        $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription', 'customer'],
        ]);

        if (($session->status ?? null) !== 'complete') {
            return false;
        }

        $metadata = $session->metadata?->toArray() ?? [];
        $teamId = $metadata['team_id'] ?? null;

        if ($teamId !== null && (string) $teamId !== (string) $team->getKey()) {
            Log::warning('Checkout session team_id does not match route team.', [
                'session_id' => $sessionId,
                'expected_team_id' => $team->getKey(),
                'metadata_team_id' => $teamId,
            ]);

            return false;
        }

        $customerId = is_string($session->customer)
            ? $session->customer
            : ($session->customer->id ?? null);

        if ($customerId && $team->stripe_id !== $customerId) {
            $team->forceFill(['stripe_id' => $customerId])->save();
        }

        $plan = $this->resolvePlan($metadata);

        if ($plan && method_exists($team, 'assignPlan')) {
            $team->assignPlan($plan);
            event(new TeamSubscribed($team, $plan, $metadata));
        }

        $subscriptionId = is_string($session->subscription)
            ? $session->subscription
            : ($session->subscription->id ?? null);

        if ($subscriptionId) {
            $stripeSubscription = Cashier::stripe()->subscriptions->retrieve($subscriptionId, [
                'expand' => ['items.data.price', 'default_payment_method'],
            ]);

            ($this->syncSubscription)($team, $stripeSubscription->toArray());
            $this->syncPaymentMethod($team, $stripeSubscription->toArray());
        } else {
            $team->updateDefaultPaymentMethodFromStripe();
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function resolvePlan(array $metadata): ?SubscriptionPlan
    {
        $planId = $metadata['subscription_plan_id'] ?? null;

        if (! $planId) {
            return null;
        }

        return SubscriptionPlan::query()->find($planId);
    }

    /**
     * @param  array<string, mixed>  $subscriptionData
     */
    protected function syncPaymentMethod(Model $team, array $subscriptionData): void
    {
        if (! method_exists($team, 'updateDefaultPaymentMethodFromStripe')) {
            return;
        }

        $team->updateDefaultPaymentMethodFromStripe();

        if (filled($team->pm_type) && filled($team->pm_last_four)) {
            return;
        }

        $paymentMethod = $subscriptionData['default_payment_method'] ?? null;

        if (! is_array($paymentMethod)) {
            return;
        }

        $this->fillPaymentMethodFromStripePayload($team, $paymentMethod);
    }

    /**
     * @param  array<string, mixed>  $paymentMethod
     */
    protected function fillPaymentMethodFromStripePayload(Model $team, array $paymentMethod): void
    {
        if (($paymentMethod['type'] ?? null) === 'card' && isset($paymentMethod['card'])) {
            $team->forceFill([
                'pm_type' => $paymentMethod['card']['brand'] ?? 'card',
                'pm_last_four' => $paymentMethod['card']['last4'] ?? null,
            ])->save();

            return;
        }

        $type = $paymentMethod['type'] ?? null;

        if ($type && isset($paymentMethod[$type])) {
            $team->forceFill([
                'pm_type' => $type,
                'pm_last_four' => $paymentMethod[$type]['last4'] ?? null,
            ])->save();
        }
    }
}
