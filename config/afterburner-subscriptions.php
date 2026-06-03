<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscriptions Enabled
    |--------------------------------------------------------------------------
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Trial Days
    |--------------------------------------------------------------------------
    |
    | Applied automatically when a new entity is created.
    |
    */

    'default_trial_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    'currency' => 'cad',

    /*
    |--------------------------------------------------------------------------
    | Supported Plan Currencies
    |--------------------------------------------------------------------------
    |
    | ISO 4217 codes available when creating or editing subscription plans.
    |
    */

    'supported_currencies' => [
        'usd' => 'USD — US Dollar',
        'cad' => 'CAD — Canadian Dollar',
        'aud' => 'AUD — Australian Dollar',
        'gbp' => 'GBP — British Pound',
        'eur' => 'EUR — Euro',
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum Price (cents)
    |--------------------------------------------------------------------------
    |
    | Stripe minimum charge amount for the configured currency (50 for USD).
    |
    */

    'minimum_price_cents' => 50,

    /*
    |--------------------------------------------------------------------------
    | Billing Role Slugs
    |--------------------------------------------------------------------------
    |
    | Entity members with these role slugs receive billing notification emails
    | in addition to the entity owner.
    |
    */

    'billing_role_slugs' => ['president', 'treasurer'],

    /*
    |--------------------------------------------------------------------------
    | Trial Ending Notification Days
    |--------------------------------------------------------------------------
    */

    'trial_ending_notification_days' => [7, 1],

    /*
    |--------------------------------------------------------------------------
    | Full-Access Trial
    |--------------------------------------------------------------------------
    |
    | When true, entities on a generic trial bypass plan entitlement checks so
    | they can explore all add-on packages before subscribing. After trial,
    | entitlements are enforced based on the subscribed plan.
    |
    */

    'trial_full_access' => true,

    /*
    |--------------------------------------------------------------------------
    | Plan Features Template
    |--------------------------------------------------------------------------
    |
    | Default entitlements merged with each plan's features JSON column.
    | null limits mean unlimited.
    |
    */

    'plan_features_template' => [
        'max_users_per_team' => null,
        'max_storage_gb' => null,
        'features' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Known Feature Slugs
    |--------------------------------------------------------------------------
    |
    | Optional slugs shown as checkboxes when editing plan entitlements.
    | Add-on packages gate on these slugs via SubscriptionEntitlementGate.
    |
    */

    'known_feature_slugs' => [
        'documents',
        'voting',
        'meetings',
        'communications',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Slug Labels
    |--------------------------------------------------------------------------
    |
    | Optional display labels for known_feature_slugs. Slugs are stored on plans
    | and checked by packages; labels are for admin and billing UI only.
    |
    */

    'feature_slug_labels' => [
        'meetings' => 'Events',
    ],

    /*
    |--------------------------------------------------------------------------
    | Included App Features
    |--------------------------------------------------------------------------
    |
    | Human-readable labels for core features the host app includes with every
    | active subscription. Shown on subscription plans and billing screens in
    | addition to plan-specific add-on package slugs.
    |
    */

    'included_app_features' => [
        'Members, roles & permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Promotions
    |--------------------------------------------------------------------------
    */

    'promotions_enabled' => true,

    'allow_checkout_promotion_codes' => true,

    /*
    |--------------------------------------------------------------------------
    | Usage-Based Billing
    |--------------------------------------------------------------------------
    |
    | Reserved for a future release. Stripe metered billing is not implemented.
    |
    */

    'usage_billing_enabled' => false,

    /*
    |--------------------------------------------------------------------------
    | Stripe
    |--------------------------------------------------------------------------
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Exempt Route Names
    |--------------------------------------------------------------------------
    |
    | Routes that remain accessible when an entity's subscription is inactive.
    |
    */

    'exempt_route_names' => [
        'dashboard',
        'teams.subscriptions.index',
        'teams.subscriptions.billing-portal',
        'profile.show',
        'personal-settings.show',
        'security.show',
        'logout',
        'verification.verify',
        'verification.notice',
        'stripe.webhook',
        'cashier.webhook',
        'cashier.payment',
    ],

];
