<?php

namespace Afterburner\Subscriptions\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancelled
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $subscription
     */
    public function __construct(
        public Model $team,
        public array $subscription = []
    ) {}
}
