<?php

namespace Afterburner\Subscriptions\Actions\Stripe;

use Afterburner\Subscriptions\Events\SubscriptionCancelled;
use Afterburner\Subscriptions\Events\SubscriptionPaymentFailed;
use Afterburner\Subscriptions\Events\TeamSubscribed;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

class HandleWebhookEvent
{
    public function __construct(
        protected SyncStripeSubscriptionToDatabase $syncSubscription,
    ) {}

    public function __invoke(WebhookReceived $event): void
    {
        $payload = $event->payload;
        $type = $payload['type'] ?? null;

        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($payload),
            'invoice.payment_failed' => $this->handlePaymentFailed($payload),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($payload),
            default => null,
        };
    }

    protected function handleCheckoutCompleted(array $payload): void
    {
        $session = $payload['data']['object'] ?? [];
        $metadata = $session['metadata'] ?? [];
        $teamId = $metadata['team_id'] ?? null;
        $planId = $metadata['subscription_plan_id'] ?? null;

        if (! $teamId || ! $planId) {
            return;
        }

        $teamModel = config('afterburner.team_model', \App\Models\Team::class);
        $team = $teamModel::query()->find($teamId);

        if (! $team) {
            return;
        }

        $plan = SubscriptionPlan::query()->find($planId);

        if ($plan && method_exists($team, 'assignPlan')) {
            $team->assignPlan($plan);

            event(new TeamSubscribed($team, $plan, $metadata));
        }

        $subscriptionId = $session['subscription'] ?? null;

        if (is_string($subscriptionId) && $subscriptionId !== '') {
            $stripeSubscription = Cashier::stripe()->subscriptions->retrieve($subscriptionId, [
                'expand' => ['items.data.price', 'default_payment_method'],
            ]);

            ($this->syncSubscription)($team, $stripeSubscription->toArray());
            $this->syncPaymentMethodFromSubscription($team, $stripeSubscription->toArray());
        }
    }

    /**
     * @param  array<string, mixed>  $subscriptionData
     */
    protected function syncPaymentMethodFromSubscription(Model $team, array $subscriptionData): void
    {
        if (! method_exists($team, 'updateDefaultPaymentMethodFromStripe')) {
            return;
        }

        $team->updateDefaultPaymentMethodFromStripe();

        if (filled($team->pm_type) && filled($team->pm_last_four)) {
            return;
        }

        $paymentMethod = $subscriptionData['default_payment_method'] ?? null;

        if (! is_array($paymentMethod) || ($paymentMethod['type'] ?? null) !== 'card') {
            return;
        }

        $team->forceFill([
            'pm_type' => $paymentMethod['card']['brand'] ?? 'card',
            'pm_last_four' => $paymentMethod['card']['last4'] ?? null,
        ])->save();
    }

    protected function handlePaymentFailed(array $payload): void
    {
        $invoice = $payload['data']['object'] ?? [];
        $customerId = $invoice['customer'] ?? null;

        $team = $this->resolveTeamByStripeCustomer($customerId);

        if ($team) {
            event(new SubscriptionPaymentFailed($team, $invoice));
        }
    }

    protected function handleSubscriptionDeleted(array $payload): void
    {
        $subscription = $payload['data']['object'] ?? [];
        $customerId = $subscription['customer'] ?? null;

        $team = $this->resolveTeamByStripeCustomer($customerId);

        if ($team) {
            event(new SubscriptionCancelled($team, $subscription));
        }
    }

    protected function resolveTeamByStripeCustomer(?string $customerId): ?Model
    {
        if (! $customerId) {
            return null;
        }

        $teamModel = config('afterburner.team_model', \App\Models\Team::class);

        return $teamModel::query()->where('stripe_id', $customerId)->first();
    }
}
