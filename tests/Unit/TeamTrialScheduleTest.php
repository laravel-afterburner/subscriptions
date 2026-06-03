<?php

namespace Afterburner\Subscriptions\Tests\Unit;

use Afterburner\Subscriptions\Support\TeamTrialSchedule;
use Afterburner\Subscriptions\Tests\TestCase;
use InvalidArgumentException;

class TeamTrialScheduleTest extends TestCase
{
    protected string $timezone = 'America/Toronto';

    public function test_from_today_ends_at_midnight_in_team_timezone(): void
    {
        $endsAt = TeamTrialSchedule::resolveEndsAt(
            null,
            TeamTrialSchedule::EXTEND_FROM_TODAY,
            30,
            null,
            [30, 60, 90],
            $this->timezone,
        );

        $localized = $endsAt->copy()->timezone($this->timezone);

        $this->assertTrue($endsAt->isFuture());
        $this->assertTrue($localized->isEndOfDay());
        $this->assertGreaterThanOrEqual(29, (int) now($this->timezone)->startOfDay()->diffInDays($localized->startOfDay(), false));
        $this->assertLessThanOrEqual(31, (int) now($this->timezone)->startOfDay()->diffInDays($localized->startOfDay(), false));
    }

    public function test_from_current_end_extends_active_trial(): void
    {
        $currentEnd = now($this->timezone)->addDays(10)->endOfDay();

        $endsAt = TeamTrialSchedule::resolveEndsAt(
            $currentEnd,
            TeamTrialSchedule::EXTEND_FROM_CURRENT_END,
            30,
            null,
            [30, 60, 90],
            $this->timezone,
        );

        $localized = $endsAt->copy()->timezone($this->timezone);

        $this->assertTrue($localized->isEndOfDay());
        $this->assertSame(30, (int) $currentEnd->diffInDays($endsAt, false));
    }

    public function test_from_current_end_starts_from_now_when_expired(): void
    {
        $endsAt = TeamTrialSchedule::resolveEndsAt(
            now($this->timezone)->subDay()->endOfDay(),
            TeamTrialSchedule::EXTEND_FROM_CURRENT_END,
            30,
            null,
            [30, 60, 90],
            $this->timezone,
        );

        $localized = $endsAt->copy()->timezone($this->timezone);

        $this->assertTrue($localized->isEndOfDay());
        $this->assertGreaterThanOrEqual(29, (int) now($this->timezone)->startOfDay()->diffInDays($localized->startOfDay(), false));
        $this->assertLessThanOrEqual(31, (int) now($this->timezone)->startOfDay()->diffInDays($localized->startOfDay(), false));
    }

    public function test_custom_date_uses_end_of_day_in_team_timezone(): void
    {
        $date = now($this->timezone)->addDays(14)->toDateString();

        $endsAt = TeamTrialSchedule::resolveEndsAt(
            null,
            TeamTrialSchedule::EXTEND_FROM_TODAY,
            null,
            $date,
            [30, 60, 90],
            $this->timezone,
        );

        $localized = $endsAt->copy()->timezone($this->timezone);

        $this->assertSame($date, $localized->toDateString());
        $this->assertTrue($localized->isEndOfDay());
    }

    public function test_rejects_past_custom_date_in_team_timezone(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TeamTrialSchedule::resolveEndsAt(
            null,
            TeamTrialSchedule::EXTEND_FROM_TODAY,
            null,
            now($this->timezone)->subDay()->toDateString(),
            [30, 60, 90],
            $this->timezone,
        );
    }
}
