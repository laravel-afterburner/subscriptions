<?php

namespace Afterburner\Subscriptions\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class SubscriptionRouteAccess
{
    public static function isEnabled(): bool
    {
        return (bool) config('afterburner-subscriptions.enabled', true);
    }

    public static function allowsRequest(Request $request): bool
    {
        if (! self::isEnabled()) {
            return true;
        }

        return self::isRouteAccessible(
            $request->route()?->getName(),
            $request->user()
        );
    }

    public static function isRouteAccessible(?string $routeName, ?Authenticatable $user = null): bool
    {
        if ($routeName === null) {
            return true;
        }

        if (! self::isEnabled()) {
            return true;
        }

        $user = $user ?? auth()->user();

        if (! $user || ! $user->currentTeam) {
            return ! str_starts_with($routeName, 'teams.');
        }

        if (self::userBypassesSubscriptionGate($user)) {
            return true;
        }

        if (self::subscriptionIsActiveForTeam($user->currentTeam)) {
            return true;
        }

        return self::isExemptRoute($routeName);
    }

    public static function subscriptionIsActiveForCurrentTeam(?Authenticatable $user = null): bool
    {
        if (! self::isEnabled()) {
            return true;
        }

        $user = $user ?? auth()->user();

        if (! $user?->currentTeam) {
            return true;
        }

        if (self::userBypassesSubscriptionGate($user)) {
            return true;
        }

        return self::subscriptionIsActiveForTeam($user->currentTeam);
    }

    public static function subscriptionIsActiveForTeam(mixed $team): bool
    {
        return SubscriptionStatus::forTeam($team)->isActive();
    }

    public static function userBypassesSubscriptionGate(?Authenticatable $user): bool
    {
        return $user !== null
            && method_exists($user, 'isSystemAdmin')
            && $user->isSystemAdmin();
    }

    /**
     * @return list<string>
     */
    protected static function exemptRouteNames(): array
    {
        return config('afterburner-subscriptions.exempt_route_names', []);
    }

    protected static function isExemptRoute(string $routeName): bool
    {
        return in_array($routeName, self::exemptRouteNames(), true);
    }
}
