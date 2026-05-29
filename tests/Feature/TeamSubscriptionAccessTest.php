<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

class TeamSubscriptionAccessTest extends TestCase
{
    public function test_user_with_manage_billing_can_view_and_manage_billing(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_billing', 'view_billing']);

        $this->assertTrue(Gate::forUser($user)->allows('viewBilling', $team));
        $this->assertTrue(Gate::forUser($user)->allows('manageBilling', $team));
    }

    public function test_user_without_billing_permission_cannot_view_billing(): void
    {
        [, $team] = $this->createTeamWithUser(['manage_billing', 'view_billing']);
        $member = $this->createTeamMember($team);

        $this->assertFalse(Gate::forUser($member)->allows('viewBilling', $team));
        $this->assertFalse(Gate::forUser($member)->allows('manageBilling', $team));
    }

    public function test_manage_billing_gates_checkout_action(): void
    {
        [, $team] = $this->createTeamWithUser(['manage_billing', 'view_billing']);
        $member = $this->createTeamMember($team, ['view_billing']);

        $this->assertTrue(Gate::forUser($member)->allows('viewBilling', $team));
        $this->assertFalse(Gate::forUser($member)->allows('manageBilling', $team));
    }
}
