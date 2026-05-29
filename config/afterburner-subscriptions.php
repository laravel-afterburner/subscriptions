<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscriptions Enabled
    |--------------------------------------------------------------------------
    */

    'enabled' => env('AFTERBURNER_SUBSCRIPTIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Trial Days
    |--------------------------------------------------------------------------
    |
    | Applied automatically when a new entity is created.
    |
    */

    'default_trial_days' => (int) env('AFTERBURNER_SUBSCRIPTIONS_DEFAULT_TRIAL_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    'currency' => env('AFTERBURNER_SUBSCRIPTIONS_CURRENCY', 'usd'),

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

    'minimum_price_cents' => (int) env('AFTERBURNER_SUBSCRIPTIONS_MINIMUM_PRICE_CENTS', 50),

    /*
    |--------------------------------------------------------------------------
    | Billing Role Slugs
    |--------------------------------------------------------------------------
    |
    | Entity members with these role slugs receive billing notification emails
    | in addition to the entity owner.
    |
    */

    'billing_role_slugs' => array_filter(array_map(
        'trim',
        explode(',', env('AFTERBURNER_SUBSCRIPTIONS_BILLING_ROLE_SLUGS', 'president,treasurer'))
    )),

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

    'trial_full_access' => env('AFTERBURNER_SUBSCRIPTIONS_TRIAL_FULL_ACCESS', true),

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
    | Promotions
    |--------------------------------------------------------------------------
    */

    'promotions_enabled' => env('AFTERBURNER_SUBSCRIPTIONS_PROMOTIONS_ENABLED', true),

    'allow_checkout_promotion_codes' => env('AFTERBURNER_SUBSCRIPTIONS_ALLOW_CHECKOUT_PROMO_CODES', true),

    /*
    |--------------------------------------------------------------------------
    | Usage-Based Billing
    |--------------------------------------------------------------------------
    |
    | Reserved for a future release. Stripe metered billing is not implemented.
    |
    */

    'usage_billing_enabled' => env('AFTERBURNER_SUBSCRIPTIONS_USAGE_BILLING_ENABLED', false),

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
        'teams.subscriptions.index',
        'teams.subscriptions.billing-portal',
        'profile.show',
        'logout',
        'verification.verify',
        'verification.notice',
        'stripe.webhook',
        'cashier.webhook',
        'cashier.payment',
    ],

];
