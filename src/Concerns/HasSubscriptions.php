<?php

namespace Afterburner\Subscriptions\Concerns;

use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\PlanEntitlements;
use Afterburner\Subscriptions\Support\SubscriptionEntitlementGate;
use Afterburner\Subscriptions\Support\SubscriptionStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Cashier\Billable;

trait HasSubscriptions
{
    use Billable;

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function hasActiveSubscription(): bool
    {
        return SubscriptionStatus::forTeam($this)->isActive();
    }

    public function subscriptionBlocked(): bool
    {
        return SubscriptionStatus::forTeam($this)->isBlocked();
    }

    public function onGenericTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function assignPlan(SubscriptionPlan $plan): void
    {
        $this->forceFill([
            'subscription_plan_id' => $plan->id,
        ])->save();
    }

    public function entitlements(): PlanEntitlements
    {
        return PlanEntitlements::forTeam($this);
    }

    public function hasEntitlement(string $featureSlug): bool
    {
        return $this->entitlements()->hasFeature($featureSlug);
    }

    public function canAccessEntitlement(string $featureSlug): bool
    {
        return SubscriptionEntitlementGate::allows($this, $featureSlug);
    }

    public function withinEntitlementLimit(string $key, int $current): bool
    {
        return $this->entitlements()->withinLimit($key, $current);
    }

    public function withinAccessibleEntitlementLimit(string $key, int $current): bool
    {
        return SubscriptionEntitlementGate::withinLimit($this, $key, $current);
    }
}
