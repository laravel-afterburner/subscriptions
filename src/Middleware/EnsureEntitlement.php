<?php

namespace Afterburner\Subscriptions\Middleware;

use Afterburner\Subscriptions\Support\SubscriptionEntitlementGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEntitlement
{
    public function handle(Request $request, Closure $next, string $featureSlug): Response
    {
        $team = $request->user()?->currentTeam;

        if (! $team || SubscriptionEntitlementGate::allows($team, $featureSlug)) {
            return $next($request);
        }

        abort(403, 'This feature is not included in your subscription plan.');
    }
}
