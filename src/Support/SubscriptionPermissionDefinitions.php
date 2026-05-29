<?php

namespace Afterburner\Subscriptions\Support;

class SubscriptionPermissionDefinitions
{
    /**
     * @return array<int, array{name: string, slug: string, description: string}>
     */
    public static function all(): array
    {
        return [
            [
                'name' => 'Manage Billing',
                'slug' => 'manage_billing',
                'description' => 'Subscribe, change plans, update payment methods, and cancel subscriptions',
            ],
            [
                'name' => 'View Billing',
                'slug' => 'view_billing',
                'description' => 'View subscription status and invoice history',
            ],
        ];
    }
}
