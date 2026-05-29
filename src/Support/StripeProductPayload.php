<?php

namespace Afterburner\Subscriptions\Support;

use Afterburner\Subscriptions\Models\SubscriptionPlan;

class StripeProductPayload
{
    /**
     * Build a Stripe Product create/update payload without empty strings.
     *
     * @return array<string, mixed>
     */
    public static function forPlan(SubscriptionPlan $plan): array
    {
        $payload = [
            'name' => $plan->name,
            'active' => $plan->is_active,
        ];

        if (filled($plan->description)) {
            $payload['description'] = $plan->description;
        }

        return $payload;
    }
}
