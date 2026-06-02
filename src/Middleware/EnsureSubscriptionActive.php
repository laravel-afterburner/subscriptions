<?php

namespace Afterburner\Subscriptions\Middleware;

use Afterburner\Subscriptions\Support\SubscriptionRouteAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SubscriptionRouteAccess::allowsRequest($request)) {
            return $next($request);
        }

        $team = $request->user()->currentTeam;

        return redirect()
            ->route('teams.subscriptions.index', $team)
            ->with('error', 'Your subscription is inactive. Please update billing to continue using the application.');
    }
}
