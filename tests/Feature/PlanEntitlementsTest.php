<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\PlanEntitlements;
use Afterburner\Subscriptions\Tests\TestCase;
use App\Models\Team;

class PlanEntitlementsTest extends TestCase
{
    public function test_plan_entitlements_merge_template_and_plan_features(): void
    {
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Pro plan',
            'monthly_price_cents' => 1000,
            'annual_price_cents' => 10000,
            'features' => [
                'max_users_per_team' => 25,
                'features' => ['documents', 'voting'],
            ],
        ]);

        $entitlements = PlanEntitlements::forPlan($plan);

        $this->assertSame(25, $entitlements->limit('max_users_per_team'));
        $this->assertNull($entitlements->limit('max_storage_gb'));
        $this->assertTrue($entitlements->hasFeature('documents'));
        $this->assertFalse($entitlements->hasFeature('meetings'));
    }

    public function test_team_entitlements_respect_limits(): void
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

        $this->assertTrue($team->withinEntitlementLimit('max_users_per_team', 5));
        $this->assertFalse($team->withinEntitlementLimit('max_users_per_team', 6));
        $this->assertTrue($team->hasEntitlement('documents') === false);
    }
}
