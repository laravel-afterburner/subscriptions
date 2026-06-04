<?php

namespace Afterburner\Subscriptions\Support;

class SubscriptionPermissionDefinitions
{
    /**
     * @return array<int, array{name: string, slug: string, description: string}>
     */
    public static function all(): array
    {
        if (class_exists(\App\Support\PermissionCatalog::class)) {
            return collect(\App\Support\PermissionCatalog::definitions())
                ->filter(fn (array $permission) => in_array($permission['slug'], self::slugs(), true))
                ->values()
                ->all();
        }

        return [
            [
                'name' => 'View Billing',
                'slug' => 'view_billing',
                'description' => 'View all subscription billing sections',
            ],
            [
                'name' => 'View Subscription Status',
                'slug' => 'view_subscription_status',
                'description' => 'View current plan and trial status',
            ],
            [
                'name' => 'View Subscription Plans',
                'slug' => 'view_subscription_plans',
                'description' => 'View available subscription plans',
            ],
            [
                'name' => 'View Subscription Invoices',
                'slug' => 'view_subscription_invoices',
                'description' => 'View billing invoice history',
            ],
            [
                'name' => 'Manage Billing',
                'slug' => 'manage_billing',
                'description' => 'Subscribe, change plans, update payment methods, and cancel subscriptions',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return [
            'view_billing',
            'view_subscription_status',
            'view_subscription_plans',
            'view_subscription_invoices',
            'manage_billing',
        ];
    }
}
