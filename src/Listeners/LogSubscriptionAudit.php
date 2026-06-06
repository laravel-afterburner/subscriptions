<?php

namespace Afterburner\Subscriptions\Listeners;

use Afterburner\Subscriptions\Events\SubscriptionCancelled;
use Afterburner\Subscriptions\Events\SubscriptionPaymentFailed;
use Afterburner\Subscriptions\Events\TeamSubscribed;
use Afterburner\Support\EntityLabel;
use App\Support\Audit\AuditLogger;

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
    protected function log(string $category, string $eventName, object $team, array $context): void
    {
        $summary = match ($eventName) {
            'subscription.payment_failed' => 'Subscription payment failed.',
            'subscription.cancelled' => 'Subscription cancelled.',
            'subscription.subscribed' => isset($context['plan_name'])
                ? EntityLabel::singularTitle()." subscribed to {$context['plan_name']}."
                : EntityLabel::singularTitle().' subscribed to a plan.',
            default => $eventName,
        };

        AuditLogger::log(
            category: $category,
            eventName: $eventName,
            auditable: $team,
            changes: AuditLogger::changesWithSummary($summary, context: $context),
            teamId: $team->getKey(),
            actionType: 'event',
        );
    }
}
