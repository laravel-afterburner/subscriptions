<?php

namespace Afterburner\Subscriptions\Tests\Unit;

use Afterburner\Subscriptions\Support\SubscriptionRouteAccess;
use Afterburner\Subscriptions\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SubscriptionRouteAccessTest extends TestCase
{
    public function test_dashboard_is_accessible_when_subscription_inactive(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['current_team_id' => $team->id]);
        $team->update(['trial_ends_at' => now()->subDay()]);

        $this->assertTrue(SubscriptionRouteAccess::isRouteAccessible('dashboard', $user));
    }

    public function test_team_routes_are_blocked_when_subscription_inactive(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['current_team_id' => $team->id]);
        $team->update(['trial_ends_at' => now()->subDay()]);

        $this->assertFalse(SubscriptionRouteAccess::isRouteAccessible('teams.finances.index', $user));
        $this->assertTrue(SubscriptionRouteAccess::isRouteAccessible('teams.subscriptions.index', $user));
    }

    public function test_allows_request_for_exempt_dashboard_route(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['current_team_id' => $team->id]);
        $team->update(['trial_ends_at' => now()->subDay()]);

        $route = Route::get('/dashboard', fn () => 'ok')->name('dashboard');
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => $route->bind($request));

        $this->assertTrue(SubscriptionRouteAccess::allowsRequest($request));
    }

    public function test_subscription_is_inactive_for_current_team_after_trial_expires(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['current_team_id' => $team->id]);
        $team->update(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($user);

        $this->assertFalse(SubscriptionRouteAccess::subscriptionIsActiveForCurrentTeam());
    }
}
