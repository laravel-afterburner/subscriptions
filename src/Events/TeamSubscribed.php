<?php

namespace Afterburner\Subscriptions\Events;

use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamSubscribed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $team,
        public SubscriptionPlan $plan,
        public array $checkoutMetadata = []
    ) {}
}
