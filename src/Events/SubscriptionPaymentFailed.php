<?php

namespace Afterburner\Subscriptions\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPaymentFailed
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $invoice
     */
    public function __construct(
        public Model $team,
        public array $invoice = []
    ) {}
}
