<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\PlanSubscriberStats;
use Afterburner\Subscriptions\Tests\TestCase;
use App\Models\Team;

class PlanSubscriberStatsTest extends TestCase
{
    public function test_counts_entities_by_subscription_status_per_plan(): void
    {
        $planA = SubscriptionPlan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'monthly_price_cents' => 1000,
            'annual_price_cents' => 10000,
            'is_active' => true,
        ]);

        $planB = SubscriptionPlan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'monthly_price_cents' => 2000,
            'annual_price_cents' => 20000,
            'is_active' => true,
        ]);

        [$user, $trialTeam] = $this->createTeamWithUser();
        $trialTeam->update([
            'subscription_plan_id' => $planA->id,
            'trial_ends_at' => now()->addDays(14),
        ]);

        $activeTeam = Team::query()->create([
            'name' => 'Active Entity',
            'user_id' => $user->id,
            'subscription_plan_id' => $planA->id,
            'trial_ends_at' => now()->subDay(),
        ]);
        $activeTeam->users()->attach($user);
        $activeTeam->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_active_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
        ]);

        $inactiveTeam = Team::query()->create([
            'name' => 'Inactive Entity',
            'user_id' => $user->id,
            'subscription_plan_id' => $planA->id,
            'trial_ends_at' => now()->subDay(),
        ]);
        $inactiveTeam->users()->attach($user);

        $proTeam = Team::query()->create([
            'name' => 'Pro Entity',
            'user_id' => $user->id,
            'subscription_plan_id' => $planB->id,
            'trial_ends_at' => now()->addDays(7),
        ]);
        $proTeam->users()->attach($user);

        $stats = PlanSubscriberStats::forPlans(collect([$planA, $planB]));

        $this->assertSame(3, $stats[$planA->id]['total']);
        $this->assertCount(3, $stats[$planA->id]['statuses']);
        $this->assertSame('Active', $stats[$planA->id]['statuses'][0]['label']);
        $this->assertSame(1, $stats[$planA->id]['statuses'][0]['count']);
        $this->assertSame('Trial', $stats[$planA->id]['statuses'][1]['label']);
        $this->assertSame(1, $stats[$planA->id]['statuses'][1]['count']);
        $this->assertSame('Inactive', $stats[$planA->id]['statuses'][2]['label']);
        $this->assertSame(1, $stats[$planA->id]['statuses'][2]['count']);

        $this->assertSame(1, $stats[$planB->id]['total']);
        $this->assertSame('Trial', $stats[$planB->id]['statuses'][0]['label']);
    }

    public function test_plan_with_no_subscribers_returns_empty_stats(): void
    {
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Empty',
            'slug' => 'empty',
            'monthly_price_cents' => 1000,
            'annual_price_cents' => 10000,
            'is_active' => true,
        ]);

        $stats = PlanSubscriberStats::forPlans(collect([$plan]));

        $this->assertSame(0, $stats[$plan->id]['total']);
        $this->assertSame([], $stats[$plan->id]['statuses']);
    }
}
