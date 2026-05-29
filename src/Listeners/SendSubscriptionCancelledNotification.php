<?php

namespace Afterburner\Subscriptions\Listeners;

use Afterburner\Subscriptions\Events\SubscriptionCancelled;
use Afterburner\Subscriptions\Notifications\SubscriptionCancelledNotification;
use Afterburner\Subscriptions\Support\BillingRecipients;
use Illuminate\Support\Facades\Notification;

class SendSubscriptionCancelledNotification
{
    public function handle(SubscriptionCancelled $event): void
    {
        $recipients = BillingRecipients::forTeam($event->team);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new SubscriptionCancelledNotification($event->team));
    }
}
