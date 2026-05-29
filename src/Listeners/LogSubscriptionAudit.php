<?php

namespace Afterburner\Subscriptions\Listeners;

use Afterburner\Subscriptions\Events\SubscriptionCancelled;
use Afterburner\Subscriptions\Events\SubscriptionPaymentFailed;
use Afterburner\Subscriptions\Events\TeamSubscribed;
use App\Services\AuditService;

class LogSubscriptionAudit
{
    public function handlePaymentFailed(SubscriptionPaymentFailed $event): void
    {
        $this->log(
            'billing',
            'subscription.payment_failed',
            $event->team,
            [
                'invoice_id' => $event->invoice['id'] ?? null,
                'amount_due' => $event->invoice['amount_due'] ?? null,
                'status' => $event->invoice['status'] ?? null,
            ]
        );
    }

    public function handleCancelled(SubscriptionCancelled $event): void
    {
        $this->log(
            'billing',
            'subscription.cancelled',
            $event->team,
            [
                'stripe_subscription_id' => $event->subscription['id'] ?? null,
                'status' => $event->subscription['status'] ?? null,
            ]
        );
    }

    public function handleSubscribed(TeamSubscribed $event): void
    {
        $this->log(
            'billing',
            'subscription.subscribed',
            $event->team,
            [
                'subscription_plan_id' => $event->plan->id,
                'plan_name' => $event->plan->name,
                'billing_interval' => $event->checkoutMetadata['billing_interval'] ?? null,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    protected function log(string $category, string $eventName, object $team, array $changes): void
    {
        if (! class_exists(AuditService::class) || ! config('audit.enabled', true)) {
            return;
        }

        try {
            app(AuditService::class)->log(
                actionType: 'event',
                category: $category,
                eventName: $eventName,
                auditable: $team,
                changes: $changes,
                teamId: $team->getKey()
            );
        } catch (\Throwable) {
            // Audit failures must not break billing flows.
        }
    }
}
