<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Actions\Stripe\StartTeamTrial;
use Afterburner\Subscriptions\Support\SubscriptionStatus;
use Afterburner\Subscriptions\Tests\TestCase;
use App\Models\Team;

class SubscriptionStatusTest extends TestCase
{
    public function test_team_on_trial_is_active(): void
    {
        [, $team] = $this->createTeamWithUser();

        $this->assertTrue(SubscriptionStatus::forTeam($team)->isActive());
    }

    public function test_team_without_trial_or_subscription_is_blocked(): void
    {
        [, $team] = $this->createTeamWithUser();

        $team->update(['trial_ends_at' => now()->subDay()]);

        $this->assertTrue(SubscriptionStatus::forTeam($team->fresh())->isBlocked());
    }

    public function test_start_team_trial_sets_trial_end_date(): void
    {
        [, $team] = $this->createTeamWithUser();
        $team->update(['trial_ends_at' => null]);

        app(StartTeamTrial::class)($team->fresh());

        $team->refresh();

        $this->assertNotNull($team->trial_ends_at);
        $this->assertTrue($team->trial_ends_at->isFuture());
    }
}
