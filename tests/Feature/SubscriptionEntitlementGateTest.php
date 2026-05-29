<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Middleware\EnsureEntitlement;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\SubscriptionEntitlementGate;
use Afterburner\Subscriptions\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SubscriptionEntitlementGateTest extends TestCase
{
    public function test_allows_when_subscriptions_disabled(): void
    {
        config(['afterburner-subscriptions.enabled' => false]);

        [, $team] = $this->createTeamWithUser();
        $team->update(['trial_ends_at' => now()->subDay()]);

        $this->assertTrue(SubscriptionEntitlementGate::allows($team, 'documents'));
    }

    public function test_allows_during_full_access_trial(): void
    {
        [, $team] = $this->createTeamWithUser();

        $this->assertTrue(SubscriptionEntitlementGate::allows($team, 'documents'));
        $this->assertFalse($team->hasEntitlement('documents'));
    }

    public function test_denies_feature_when_trial_expired_and_not_on_plan(): void
    {
        [, $team] = $this->createTeamWithUser();
        $team->update(['trial_ends_at' => now()->subDay()]);

        $this->assertFalse(SubscriptionEntitlementGate::allows($team, 'documents'));
    }

    public function test_allows_feature_when_plan_includes_slug(): void
    {
        [, $team] = $this->createTeamWithUser();
        $team->update(['trial_ends_at' => now()->subDay()]);

        $plan = SubscriptionPlan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Pro plan',
            'monthly_price_cents' => 1000,
            'annual_price_cents' => 10000,
            'features' => [
                'features' => ['documents'],
            ],
        ]);

        $team->assignPlan($plan);

        $this->assertTrue(SubscriptionEntitlementGate::allows($team, 'documents'));
        $this->assertFalse(SubscriptionEntitlementGate::allows($team, 'voting'));
    }

    public function test_within_limit_bypasses_during_trial(): void
    {
        [, $team] = $this->createTeamWithUser();

        $plan = SubscriptionPlan::query()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Starter plan',
            'monthly_price_cents' => 500,
            'annual_price_cents' => 5000,
            'features' => [
                'max_users_per_team' => 5,
            ],
        ]);

        $team->assignPlan($plan);

        $this->assertTrue(SubscriptionEntitlementGate::withinLimit($team, 'max_users_per_team', 100));
    }

    public function test_within_limit_enforced_after_trial(): void
    {
        [, $team] = $this->createTeamWithUser();
        $team->update(['trial_ends_at' => now()->subDay()]);

        $plan = SubscriptionPlan::query()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Starter plan',
            'monthly_price_cents' => 500,
            'annual_price_cents' => 5000,
            'features' => [
                'max_users_per_team' => 5,
            ],
        ]);

        $team->assignPlan($plan);

        $this->assertTrue(SubscriptionEntitlementGate::withinLimit($team, 'max_users_per_team', 5));
        $this->assertFalse(SubscriptionEntitlementGate::withinLimit($team, 'max_users_per_team', 6));
    }

    public function test_team_can_access_entitlement_helper_matches_gate(): void
    {
        [, $team] = $this->createTeamWithUser();
        $team->update(['trial_ends_at' => now()->subDay()]);

        $this->assertSame(
            SubscriptionEntitlementGate::allows($team, 'documents'),
            $team->canAccessEntitlement('documents')
        );
    }

    public function test_middleware_allows_during_trial(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['current_team_id' => $team->id]);

        Route::get('/test-documents', fn () => 'ok')
            ->middleware(EnsureEntitlement::class.':documents');

        $request = Request::create('/test-documents', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => Route::getRoutes()->match($request));

        $response = (new EnsureEntitlement)->handle($request, fn () => response('ok'), 'documents');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_middleware_blocks_when_not_entitled(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['current_team_id' => $team->id]);
        $team->update(['trial_ends_at' => now()->subDay()]);

        $request = Request::create('/test-documents', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        (new EnsureEntitlement)->handle($request, fn () => response('ok'), 'documents');
    }
}
