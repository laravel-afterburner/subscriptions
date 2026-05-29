<?php

namespace Afterburner\Subscriptions\Listeners;

use Afterburner\Subscriptions\Actions\Stripe\HandleWebhookEvent;
use Laravel\Cashier\Events\WebhookReceived;

class ProcessStripeWebhook
{
    public function __construct(protected HandleWebhookEvent $handler) {}

    public function handle(WebhookReceived $event): void
    {
        ($this->handler)($event);
    }
}
