<?php

namespace Afterburner\Subscriptions\Tests\Unit;

use Afterburner\Subscriptions\Support\TeamTrialDisplay;
use Afterburner\Subscriptions\Tests\TestCase;

class TeamTrialDisplayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $helpers = dirname(__DIR__, 2).'/../afterburner/app/helpers.php';

        if (is_file($helpers)) {
            require_once $helpers;
        }
    }

    public function test_format_trial_ends_at_uses_team_timezone(): void
    {
        [, $team] = $this->createTeamWithUser();
        $team->update(['timezone' => 'America/Toronto']);

        $endsAt = now('America/Toronto')->addDays(5)->endOfDay();

        $formatted = TeamTrialDisplay::formatTrialEndsAt($endsAt, $team);

        $this->assertStringContainsString((string) $endsAt->timezone('America/Toronto')->format('j'), $formatted);
        $this->assertStringContainsString('<sup>', $formatted);
    }
}
