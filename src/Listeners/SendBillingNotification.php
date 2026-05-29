<?php

namespace Afterburner\Subscriptions\Listeners;

use Afterburner\Subscriptions\Events\SubscriptionPaymentFailed;
use Afterburner\Subscriptions\Notifications\BillingIssueNotification;
use Afterburner\Subscriptions\Support\BillingRecipients;
use Illuminate\Support\Facades\Notification;

class SendBillingNotification
{
    public function handle(SubscriptionPaymentFailed $event): void
    {
        $recipients = BillingRecipients::forTeam($event->team);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new BillingIssueNotification($event->team, $event->invoice));
    }
}
