<?php

namespace Afterburner\Subscriptions\Middleware;

use Afterburner\Subscriptions\Support\SubscriptionStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('afterburner-subscriptions.enabled', true)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $exemptRoutes = config('afterburner-subscriptions.exempt_route_names', []);

        if ($routeName && in_array($routeName, $exemptRoutes, true)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! $user->currentTeam) {
            return $next($request);
        }

        $team = $user->currentTeam;

        if (SubscriptionStatus::forTeam($team)->isActive()) {
            return $next($request);
        }

        if ($routeName === 'teams.subscriptions.index') {
            return $next($request);
        }

        return redirect()
            ->route('teams.subscriptions.index', $team)
            ->with('error', 'Your subscription is inactive. Please update billing to continue using the application.');
    }
}
