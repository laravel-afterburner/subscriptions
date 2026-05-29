<?php

namespace Afterburner\Subscriptions\Support;

use Afterburner\Subscriptions\Concerns\HasSubscriptions;
use Illuminate\Database\Eloquent\Model;

class SubscriptionEntitlementGate
{
    public static function subscriptionsEnabled(): bool
    {
        return (bool) config('afterburner-subscriptions.enabled', true);
    }

    public static function teamHasSubscriptionSupport(Model $team): bool
    {
        return in_array(HasSubscriptions::class, class_uses_recursive($team), true);
    }

    public static function teamOnFullAccessTrial(Model $team): bool
    {
        if (! static::teamHasSubscriptionSupport($team)) {
            return false;
        }

        if (! config('afterburner-subscriptions.trial_full_access', true)) {
            return false;
        }

        return method_exists($team, 'onGenericTrial') && $team->onGenericTrial();
    }

    public static function isEnforcingEntitlements(Model $team): bool
    {
        return static::subscriptionsEnabled()
            && static::teamHasSubscriptionSupport($team)
            && ! static::teamOnFullAccessTrial($team);
    }

    public static function allows(Model $team, string $featureSlug): bool
    {
        if (! static::subscriptionsEnabled()) {
            return true;
        }

        if (! static::teamHasSubscriptionSupport($team)) {
            return true;
        }

        if (static::teamOnFullAccessTrial($team)) {
            return true;
        }

        return method_exists($team, 'hasEntitlement')
            && $team->hasEntitlement($featureSlug);
    }

    public static function withinLimit(Model $team, string $key, int $current): bool
    {
        if (! static::subscriptionsEnabled()) {
            return true;
        }

        if (! static::teamHasSubscriptionSupport($team)) {
            return true;
        }

        if (static::teamOnFullAccessTrial($team)) {
            return true;
        }

        return method_exists($team, 'withinEntitlementLimit')
            && $team->withinEntitlementLimit($key, $current);
    }
}
