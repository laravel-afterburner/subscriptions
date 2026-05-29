# Afterburner Subscriptions Package

Stripe subscription billing for Laravel Afterburner Jetstream. Each entity is the billable customer.

## Installation

### Local Development Setup

For local development, add the package as a path repository:

```bash
composer config repositories.afterburner-subscriptions path ../afterburner-subscriptions
composer require laravel-afterburner/subscriptions:@dev
```

### Quick Install

```bash
composer require laravel-afterburner/subscriptions
php artisan afterburner:subscriptions:install
```

Add `Afterburner\Subscriptions\Concerns\HasSubscriptions` to `App\Models\Team`.

Register `EnsureSubscriptionActive` on authenticated web routes (middleware alias: `subscription.active`).

## Documentation

See [INSTRUCTIONS.md](INSTRUCTIONS.md) for the full build specification and host integration checklist.

## Features

- 30-day auto trial on new entities (full product access during trial; no card required)
- Monthly and annual plans (admin-managed, Stripe-synced)
- Plan entitlements (limits + feature slugs) via `PlanEntitlements` / `$team->entitlements()`
- `SubscriptionEntitlementGate` for add-on packages to gate features by plan
- Promotion codes (admin-managed, Stripe-synced, optional at checkout)
- Entity subscription manager with Stripe Checkout and billing portal
- Invoice history via Cashier
- Hard app block when subscription inactive
- Billing notification emails to owner, president, and treasurer
- Audit log entries for subscribe, payment failed, and cancellation (when host `AuditService` is available)

## Entitlement gating for add-on packages

Add-on packages (documents, voting, etc.) should **not** hard-require this package. Use `SubscriptionEntitlementGate` at routes, policies, navigation, and mutation points:

```php
use Afterburner\Subscriptions\Support\SubscriptionEntitlementGate;

// Feature slug check (allows during trial, when disabled, or when not installed)
SubscriptionEntitlementGate::allows($team, 'documents');

// Numeric limit check
SubscriptionEntitlementGate::withinLimit($team, 'max_storage_gb', $usedGb);
```

On teams using `HasSubscriptions`, convenience methods are also available:

```php
$team->canAccessEntitlement('documents');
$team->withinAccessibleEntitlementLimit('max_storage_gb', $usedGb);
```

Route middleware (alias registered by this package):

```php
Route::middleware('subscription.entitlement:documents')->group(...);
```

Laravel gate for policies:

```php
$user->can('teamEntitlement', [$team, 'documents']);
```

Register each add-on's slug in the host `config/afterburner-subscriptions.php` → `known_feature_slugs` so admins can assign features to plans.

**Trial behavior:** With `trial_full_access` enabled (default), entities on a generic trial bypass entitlement checks so they can explore all features. After trial, access is enforced by the subscribed plan.

**App-wide block:** `EnsureSubscriptionActive` (host registers) blocks the entire app when billing is inactive. Entitlement checks are per-feature and complementary.

## License

MIT License
