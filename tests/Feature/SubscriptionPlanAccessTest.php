<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Livewire\Admin\SubscriptionPlans\Show;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\TeamPermissionGate;
use Afterburner\Subscriptions\Tests\TestCase;
use Livewire\Livewire;

class SubscriptionPlanAccessTest extends TestCase
{
    public function test_system_admin_can_manage_subscription_plans(): void
    {
        [$user] = $this->createTeamWithUser();
        $user->update(['is_system_admin' => true]);

        $this->assertTrue($user->can('viewAny', SubscriptionPlan::class));
        $this->assertTrue($user->can('create', SubscriptionPlan::class));
    }

    public function test_non_admin_cannot_manage_subscription_plans(): void
    {
        [$user] = $this->createTeamWithUser();

        $this->assertFalse($user->can('viewAny', SubscriptionPlan::class));
        $this->assertFalse($user->can('create', SubscriptionPlan::class));
    }

    public function test_system_admin_can_view_subscription_plan_show_component(): void
    {
        [$user] = $this->createTeamWithUser();
        $user->update(['is_system_admin' => true]);

        $plan = SubscriptionPlan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Pro plan.',
            'currency' => 'usd',
            'monthly_price_cents' => 2900,
            'annual_price_cents' => 29000,
            'trial_days' => 30,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['plan' => $plan])
            ->assertSee('Pro')
            ->assertSee('pro')
            ->assertSee('Edit plan');
    }

    public function test_non_admin_cannot_view_subscription_plan_show_page(): void
    {
        [$user] = $this->createTeamWithUser();

        $plan = SubscriptionPlan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Pro plan.',
            'currency' => 'usd',
            'monthly_price_cents' => 2900,
            'annual_price_cents' => 29000,
            'trial_days' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.subscription-plans.show', $plan))
            ->assertForbidden();
    }

    public function test_team_owner_can_manage_billing_without_explicit_permission(): void
    {
        [$user, $team] = $this->createTeamWithUser([]);

        $this->assertTrue(TeamPermissionGate::allows($user, 'manage_billing', $team));
    }

    public function test_team_member_with_permission_can_view_billing(): void
    {
        [$user, $team] = $this->createTeamWithUser(['view_billing']);

        $this->assertTrue(TeamPermissionGate::allows($user, 'view_billing', $team));
    }

    public function test_subscription_plan_can_be_created_in_database(): void
    {
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => 'Basic plan.',
            'currency' => 'usd',
            'monthly_price_cents' => 2900,
            'annual_price_cents' => 29000,
            'trial_days' => 30,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'basic',
            'monthly_price_cents' => 2900,
        ]);

        $this->assertSame('29.00 USD', $plan->formattedPrice('month'));
    }

    public function test_subscription_plan_uses_plan_currency_for_display(): void
    {
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Canadian',
            'slug' => 'canadian',
            'description' => 'Canadian plan.',
            'currency' => 'cad',
            'monthly_price_cents' => 3900,
            'annual_price_cents' => 39000,
            'trial_days' => 30,
            'is_active' => true,
        ]);

        $this->assertSame('39.00 CAD', $plan->formattedPrice('month'));
        $this->assertSame('C$', $plan->currencySymbol());
    }
}
