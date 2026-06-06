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

        $route = Route::get(entity_path('{team}/subscriptions'), fn () => 'ok')->name('teams.subscriptions.index');
        $request = Request::create(entity_path("{$team->id}/subscriptions"), 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => $route->bind($request));

        $response = (new EnsureSubscriptionActive)->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_allows_system_admin_when_subscription_inactive(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update([
            'current_team_id' => $team->id,
            'is_system_admin' => true,
        ]);
        $team->update(['trial_ends_at' => now()->subDay()]);

        $route = Route::get('/test-protected', fn () => 'ok')->name('test.protected');
        $request = Request::create('/test-protected', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => $route->bind($request));

        $response = (new EnsureSubscriptionActive)->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_allows_dashboard_when_subscription_inactive(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['current_team_id' => $team->id]);
        $team->update(['trial_ends_at' => now()->subDay()]);

        $route = Route::get('/dashboard', fn () => 'ok')->name('dashboard');
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => $route->bind($request));

        $response = (new EnsureSubscriptionActive)->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }
}
