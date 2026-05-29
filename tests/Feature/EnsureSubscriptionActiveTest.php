<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Middleware\EnsureSubscriptionActive;
use Afterburner\Subscriptions\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class EnsureSubscriptionActiveTest extends TestCase
{
    public function test_allows_access_during_trial(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['current_team_id' => $team->id]);

        $route = Route::get('/test-protected', fn () => 'ok')->name('test.protected');
        $request = Request::create('/test-protected', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => $route->bind($request));

        $response = (new EnsureSubscriptionActive)->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_blocks_access_when_subscription_expired(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['current_team_id' => $team->id]);
        $team->update(['trial_ends_at' => now()->subDay()]);

        $route = Route::get('/test-protected', fn () => 'ok')->name('test.protected');
        $request = Request::create('/test-protected', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => $route->bind($request));

        $response = (new EnsureSubscriptionActive)->handle($request, fn () => response('ok'));

        $this->assertTrue($response->isRedirect(route('teams.subscriptions.index', $team)));
    }

    public function test_allows_subscription_routes_when_inactive(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['current_team_id' => $team->id]);
        $team->update(['trial_ends_at' => now()->subDay()]);

        $route = Route::get('/teams/{team}/subscriptions', fn () => 'ok')->name('teams.subscriptions.index');
        $request = Request::create("/teams/{$team->id}/subscriptions", 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => $route->bind($request));

        $response = (new EnsureSubscriptionActive)->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }
}
