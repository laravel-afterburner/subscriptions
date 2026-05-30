# Afterburner Subscriptions Package — Build Instructions

Handoff document for building `laravel-afterburner/subscriptions`. Mirror `afterburner-documents` and `afterburner-voting` for structure, service provider patterns, permissions, navigation, install command, and Testbench setup.

Read `../afterburner-documents/AFTERBURNER_PACKAGE_PLAN.md` § Subscriptions for original platform plan.

---

## 1. Project context

**Goal:** Entity-scoped Stripe subscription billing for Afterburner SaaS apps. Each **entity** (`App\Models\Team`) is the Stripe customer. Platform operators manage plans; entity admins subscribe and pay.

**Host app:** `laravel-afterburner/jetstream` template. Entities are the multi-tenant boundary (Jetstream `Team` model).

**Primary consumer:** Strata app (`../strata`).

**Reference packages:** `afterburner-documents` (canonical), `afterburner-voting` (permissions, SystemSettings, TeamNavigation).

**Out of scope:** Strata levy collection from lot owners (Stripe Connect), usage metering (Phase 4 reserved flag only).

---

## 2. Locked product decisions

| Topic | Decision |
|-------|----------|
| Package name | `laravel-afterburner/subscriptions` |
| Namespace | `Afterburner\Subscriptions\` |
| Billable model | **Entity** (`App\Models\Team`) |
| Plan storage | DB `subscription_plans` + Stripe Product/Price sync |
| Intervals | Monthly and annual (annual discounted) |
| Trial | **30 days auto** on every new entity |
| Billing managers | Entity **owner**, **president**, **treasurer** (`manage_billing` permission) |
| On expiry / non-payment | **Hard block** entire app for that entity |
| Notifications | Email owner, president, treasurer on billing issues (failed payment, past due, pending cancellation, trial ending) |
| Receipts | Stripe/Cashier invoices (download links in UI) |
| Entity nav | Entity menu → **Subscriptions** (under Announcements) |
| Admin nav | Profile → System Administration → **Subscription Plans** (with Impersonate User) |

---

## 3. Package metadata

| Field | Value |
|-------|-------|
| Composer name | `laravel-afterburner/subscriptions` |
| PHP | `^8.2` |
| Requires | `laravel/framework ^11`, `laravel-afterburner/jetstream ^1.0\|dev-master`, `laravel/cashier ^15`, `livewire/livewire ^3.5` |
| Provider | `Afterburner\Subscriptions\Providers\SubscriptionsServiceProvider` |
| Install command | `afterburner:subscriptions:install` |
| Publish tags | `afterburner-subscriptions-config`, `afterburner-subscriptions-migrations`, `afterburner-subscriptions-assets` |
| Env prefix | `AFTERBURNER_SUBSCRIPTIONS_*` + standard `STRIPE_*` |

---

## 4. Directory structure

```
afterburner-subscriptions/
├── INSTRUCTIONS.md          ← this file
├── README.md
├── composer.json
├── phpunit.xml
├── config/afterburner-subscriptions.php
├── database/migrations/
├── routes/web.php
├── resources/views/
│   ├── subscriptions/
│   │   ├── index.blade.php
│   │   └── livewire/manager.blade.php
│   └── admin/subscription-plans/
│       ├── index.blade.php
│       └── livewire/
│           ├── index.blade.php
│           ├── create.blade.php
│           └── edit.blade.php
└── src/
    ├── Actions/Stripe/
    │   ├── CreateCheckoutSession.php
    │   ├── SyncSubscriptionPlanToStripe.php
    │   ├── HandleWebhookEvent.php
    │   └── StartTeamTrial.php
    ├── Concerns/HasSubscriptions.php
    ├── Console/Commands/InstallCommand.php
    ├── Database/Seeders/
    │   ├── SubscriptionsPermissionsSeeder.php
    │   └── Concerns/AssignsPermissionsToTeamOwners.php
    ├── Enums/BillingInterval.php
    ├── Enums/SubscriptionPlanStatus.php
    ├── Events/SubscriptionPaymentFailed.php
    ├── Http/Controllers/
    │   ├── SubscriptionPlansAdminController.php
    │   ├── TeamSubscriptionsController.php
    │   └── WebhookController.php
    ├── Listeners/
    │   ├── SendBillingNotification.php
    │   └── StartTrialOnTeamCreated.php
    ├── Livewire/
    │   ├── Admin/SubscriptionPlans/{Index,Create,Edit}.php
    │   └── Teams/SubscriptionManager.php
    ├── Middleware/EnsureSubscriptionActive.php
    ├── Models/SubscriptionPlan.php
    ├── Notifications/
    │   ├── BillingIssueNotification.php
    │   ├── SubscriptionCancelledNotification.php
    │   └── TrialEndingNotification.php
    ├── Policies/SubscriptionPlanPolicy.php
    ├── Providers/SubscriptionsServiceProvider.php
    └── Support/
        ├── BillingRecipients.php
        ├── SubscriptionPermissionDefinitions.php
        ├── SubscriptionStatus.php
        └── TeamPermissionGate.php
```

---

## 5. Data model

### 5.1 subscription_plans

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | Display name |
| slug | string unique | URL-safe identifier |
| description | text nullable | |
| stripe_product_id | string nullable | Synced from Stripe |
| monthly_price_cents | unsigned int | |
| annual_price_cents | unsigned int | Cheaper than 12× monthly |
| stripe_price_id_monthly | string nullable | |
| stripe_price_id_annual | string nullable | |
| trial_days | unsigned int default 30 | Overridable per plan; new entities use config default |
| features | json nullable | Future entitlements |
| is_active | boolean default true | |
| sort_order | integer default 0 | |
| timestamps | | |

### 5.2 `teams` table (entity / Cashier + package columns)

Publish Cashier migrations. Package migration adds:

| Column | Type | Notes |
|--------|------|-------|
| stripe_id | string nullable | Cashier |
| pm_type | string nullable | Cashier |
| pm_last_four | string nullable | Cashier |
| trial_ends_at | timestamp nullable | Cashier — auto-set on entity create |
| subscription_plan_id | FK nullable | Current selected plan (may differ from Stripe during checkout) |
| billing_email | string nullable | Optional override |

Cashier owns `subscriptions` and `subscription_items` tables (billable morph on entity / `Team` model).

### 5.3 Indexes

- `subscription_plans`: `(is_active, sort_order)`
- Verify Cashier indexes on `subscriptions(billable_type, billable_id)`

---

## 6. Permissions

Seed via `SubscriptionsPermissionsSeeder` + `PackageSeederRegistry` when available.

| Slug | Description |
|------|-------------|
| `manage_billing` | Subscribe, change plan, update payment method, cancel |
| `view_billing` | View plan status and invoice history |

**Assign `manage_billing` + `view_billing` to:** entity owner (via `AssignsPermissionsToTeamOwners`), and host role templates for `president`, `treasurer`, `team_lead`, `owner_manager`, etc.

Config `billing_role_slugs` lists role slugs whose members receive notification emails (default: `president`, `treasurer`). Entity owner always included.

---

## 7. Routes

All entity subscription routes verify `$team->id` in policies.

```php
// Entity subscription manager (entity menu)
GET  /teams/{team}/subscriptions                    teams.subscriptions.index

// System admin (profile menu)
GET  /admin/subscription-plans                      admin.subscription-plans.index
GET  /admin/subscription-plans/create               admin.subscription-plans.create
GET  /admin/subscription-plans/{plan}/edit          admin.subscription-plans.edit

// Stripe (no auth)
POST /stripe/webhook                                stripe.webhook

// Cashier billing portal redirect (optional MVP)
GET  /teams/{team}/subscriptions/billing-portal     teams.subscriptions.billing-portal
```

Middleware:

- Entity subscription routes: `web`, `auth`, `verified`, `can:view,team`
- Admin routes: `web`, `auth`, `verified`, `system.admin`
- App-wide (host registers): `EnsureSubscriptionActive` on authenticated entity routes

---

## 8. Navigation (requires core template hooks)

### 8.1 Entity menu — Subscriptions under Announcements

Package registers via `TeamNavigation::register()` in service provider:

```php
TeamNavigation::register([
    'label' => 'Subscriptions',
    'route' => 'teams.subscriptions.index',
    'order' => 15,
    'permission' => fn ($user) => $user?->can('viewBilling', $user->currentTeam) ?? false,
]);
```

**Core change required:** In `afterburner/resources/views/navigation-menu.blade.php`, render `TeamNavigation::items()` **after Announcements, before System Settings** (currently renders after System Settings).

### 8.2 Profile menu — Subscription Plans for system admins

Package registers via `SystemAdminNavigation::register()` (new core class, mirror `TeamNavigation`):

```php
SystemAdminNavigation::register([
    'label' => 'Subscription Plans',
    'route' => 'admin.subscription-plans.index',
    'order' => 10,
]);
```

**Core change required:** Add `App\Support\SystemAdminNavigation` and foreach in profile dropdown System Administration section (after Impersonate User, before Audit Logs).

---

## 9. Key flows

### 9.1 New entity → auto trial

1. Host fires `App\Events\TeamCreated` on entity create.
2. Listener `StartTrialOnTeamCreated` calls `StartTeamTrial` action.
3. Sets `trial_ends_at = now()->addDays(config('afterburner-subscriptions.default_trial_days', 30))`.

### 9.2 Subscribe / change plan

1. User with `manage_billing` opens Subscriptions page.
2. Selects plan + interval (monthly/annual).
3. `CreateCheckoutSession` action → Stripe Checkout → success webhook updates subscription.
4. Redirect back to entity subscriptions page.

### 9.3 Billing failure / cancellation

1. Stripe webhook → `HandleWebhookEvent`.
2. Dispatches `SubscriptionPaymentFailed` or similar event.
3. `SendBillingNotification` resolves recipients via `BillingRecipients` (owner + president + treasurer).
4. Sends `BillingIssueNotification` email.
5. When subscription inactive and trial expired → `EnsureSubscriptionActive` blocks app.

### 9.4 Hard block

`EnsureSubscriptionActive` middleware:

- Skip for: subscription routes, billing portal, logout, profile, webhook
- Skip if: `!config('afterburner-subscriptions.enabled')`
- Skip if: entity `hasActiveSubscription()` (trial or paid)
- Else: redirect to `teams.subscriptions.index` with error flash

### 9.5 Add-on entitlement gating

`SubscriptionEntitlementGate` is the shared API for add-on packages (documents, voting, etc.):

- `allows($team, $featureSlug)` — feature included in plan (or bypassed)
- `withinLimit($team, $key, $current)` — numeric limit check (or bypassed)

Bypasses (always allow): subscriptions disabled, team lacks `HasSubscriptions`, or entity on full-access trial (`trial_full_access` config, default true).

Add-on packages use a **soft dependency** — no Composer require on subscriptions. Enforce at routes (`subscription.entitlement:slug` middleware alias), policies (`teamEntitlement` gate), navigation, and Livewire/actions.

Host registers slugs in `known_feature_slugs`. Trial is full product access (no card upfront); entitlements enforce after trial based on subscribed plan.

---

## 10. Actions (all mutations)

| Action | Purpose |
|--------|---------|
| `StartTeamTrial` | Set trial_ends_at on new entity |
| `CreateCheckoutSession` | Stripe Checkout for subscribe/switch plan |
| `SyncSubscriptionPlanToStripe` | Create/update Product + Prices in Stripe |
| `HandleWebhookEvent` | Process Cashier/Stripe webhook payloads |

Keep controllers and Livewire thin — authorize, validate, delegate to Actions.

---

## 11. Livewire components

| Component | Alias | Purpose |
|-----------|-------|---------|
| `Teams\SubscriptionManager` | `subscriptions.manager` | Current plan, subscribe, invoices, portal link |
| `Admin\SubscriptionPlans\Index` | `subscriptions.admin.plans.index` | List plans |
| `Admin\SubscriptionPlans\Create` | `subscriptions.admin.plans.create` | Create plan + sync Stripe |
| `Admin\SubscriptionPlans\Edit` | `subscriptions.admin.plans.edit` | Edit plan + re-sync |

UI buttons: mirror host Jetstream — `<x-button>`, `<x-secondary-button>`, `<x-danger-button>`, `no-spinner` on Livewire actions.

---

## 12. Notifications schedule (MVP)

| Trigger | Notification | Recipients |
|---------|--------------|------------|
| `invoice.payment_failed` | BillingIssueNotification | BillingRecipients |
| `customer.subscription.deleted` (pending cancel) | SubscriptionCancelledNotification | BillingRecipients |
| Trial ending (7 days, 1 day) | TrialEndingNotification | BillingRecipients |
| Scheduled command daily | TrialEndingNotification | BillingRecipients |

Implement `afterburner:subscriptions:notify-trial-ending` scheduled daily in service provider.

---

## 13. Install command steps

`php artisan afterburner:subscriptions:install`:

1. Publish config (`afterburner-subscriptions-config`)
2. Publish views (`afterburner-subscriptions-assets`)
3. Publish Cashier migrations (`cashier-migrations`)
4. Append env vars to `.env` / `.env.example`
5. Prompt migrate
6. Prompt seed permissions
7. Print next steps:
   - Add `HasSubscriptions` to `App\Models\Team`
   - Register `EnsureSubscriptionActive` middleware on host routes
   - Set Stripe keys
   - Configure Stripe webhook → `/stripe/webhook`
   - Apply core navigation template updates

---

## 14. Host app integration checklist

- [ ] `composer require laravel-afterburner/subscriptions`
- [ ] `php artisan afterburner:subscriptions:install`
- [ ] Add `HasSubscriptions` trait to `App\Models\Team`
- [ ] Add `manage_billing` / `view_billing` to role templates (president, treasurer, team_lead)
- [ ] Apply core `TeamNavigation` + `SystemAdminNavigation` template updates
- [ ] Register `EnsureSubscriptionActive` in `bootstrap/app.php` or route group
- [ ] Stripe Dashboard: webhook events (`customer.subscription.*`, `invoice.*`, `checkout.session.completed`)
- [ ] Register in `afterburner-installer` PackageRegistry when stable

---

## 15. Testing (Testbench)

Fixtures: `tests/Fixtures/Models/{User,Team}.php`, auth + permission migrations.

Feature tests (MVP):

- [ ] `SubscriptionPlanPolicyTest` — system admin only for admin routes
- [ ] `TeamSubscriptionAccessTest` — manage_billing gates checkout
- [ ] `EnsureSubscriptionActiveTest` — blocks when expired, allows during trial
- [ ] `StartTeamTrialTest` — entity created sets trial_ends_at
- [ ] `BillingRecipientsTest` — resolves owner + role members

Mock Stripe in tests; do not hit live API.

---

## 16. Implementation phases

### Phase 1 — Skeleton (current)

- [x] Directory + INSTRUCTIONS.md
- [x] composer.json, config, migrations, provider, install command
- [x] Models, HasSubscriptions, permissions seeder
- [x] Routes, middleware, Livewire + views
- [x] Core navigation hooks (SystemAdminNavigation + TeamNavigation placement)
- [x] Basic tests (8 passing)

### Phase 2 — Stripe integration

- [x] SyncSubscriptionPlanToStripe action (real API)
- [x] CreateCheckoutSession + billing portal
- [x] WebhookController + HandleWebhookEvent
- [x] Invoice list in SubscriptionManager

### Phase 3 — Notifications + hard block polish

- [x] Billing notifications + trial ending command
- [x] EnsureSubscriptionActive wired in host
- [x] Audit log listener (`LogSubscriptionAudit`)
- [x] Installer registry + Strata composer path repo

### Phase 4 — Entitlements + promotions

- [x] `PlanEntitlements` resolver + `HasSubscriptions` helpers
- [x] `SubscriptionEntitlementGate` + `EnsureEntitlement` middleware + `teamEntitlement` gate
- [x] Full-access trial (`trial_full_access` config)
- [x] Plan entitlements editor on admin create/edit
- [x] Promotion codes (`subscription_promotion_codes`) + Stripe sync
- [x] Admin UI at `/admin/subscription-promotions`
- [x] Optional promo code on entity checkout (`withPromotionCode`)
- [ ] Usage-based billing (config flag only; not implemented)
- [ ] Strata levy collection (separate package)

---

## 17. Critical invariants

1. **Entity is the billable customer** — not User (implemented as `App\Models\Team`).
2. **All entity subscription routes** verify entity scope in policies.
3. **Permissions gate capability** — `manage_billing` for mutations; notifications go to configured roles + owner.
4. **Mutations through Actions** — no fat Livewire.
5. **Stripe is source of truth for payment state** — DB plans are catalog; Cashier subscription record is runtime state.
6. **Hard block on inactive subscription** — except subscription/billing routes.
7. **Entitlements gate add-on features** — via `SubscriptionEntitlementGate`; full access during trial, plan-based after.

---

## 18. Strata local refresh (path repo `../strata`)

Published views under `resources/views/vendor/afterburner-*` override package defaults. Republish **all** Afterburner package views (not only subscriptions):

```bash
cd ../strata
php artisan afterburner:publish --force
```

**Views only** (agent shorthand: `repeat`):

```bash
php artisan afterburner:publish --force
php artisan view:clear
```

**Full refresh** (agent shorthand: `repeat full`):

```bash
composer update laravel-afterburner/subscriptions --no-interaction
php artisan afterburner:publish --force
php artisan migrate --force
php artisan view:clear
php artisan optimize:clear
php artisan config:clear
```

Do not run `vendor:publish --tag=afterburner-subscriptions-config --force` unless new config keys need a careful merge into Strata’s published config. Publish does not delete stale files under `resources/views/vendor/` — remove orphans if `diff` reports extras.

---

## 19. Environment variables

```env
AFTERBURNER_SUBSCRIPTIONS_ENABLED=true
AFTERBURNER_SUBSCRIPTIONS_DEFAULT_TRIAL_DAYS=30
AFTERBURNER_SUBSCRIPTIONS_CURRENCY=usd
AFTERBURNER_SUBSCRIPTIONS_BILLING_ROLE_SLUGS=president,treasurer
AFTERBURNER_SUBSCRIPTIONS_TRIAL_FULL_ACCESS=true

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
CASHIER_CURRENCY=usd
CASHIER_CURRENCY_LOCALE=en
```

---

## 20. File creation order (for agents)

1. `composer.json`, `phpunit.xml`, `README.md`
2. `config/afterburner-subscriptions.php`
3. Migrations: `subscription_plans`, `add_subscription_fields_to_teams_table`
4. `SubscriptionPlan` model, `HasSubscriptions` trait
5. Support: permissions, TeamPermissionGate, BillingRecipients, SubscriptionStatus
6. `SubscriptionsServiceProvider` (register everything)
7. `InstallCommand`
8. Routes + controllers
9. Middleware + policies
10. Actions (stubs → implement)
11. Livewire + views
12. Notifications + listeners
13. Tests
14. Core template: `SystemAdminNavigation`, navigation-menu placement

Follow `afterburner-voting` and `afterburner-documents` exactly for code style, Pint, and Testbench patterns.
