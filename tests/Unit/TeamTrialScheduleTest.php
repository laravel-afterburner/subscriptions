<?php

namespace Afterburner\Subscriptions\Tests\Unit;

use Afterburner\Subscriptions\Support\TeamTrialSchedule;
use Afterburner\Subscriptions\Tests\TestCase;
use InvalidArgumentException;

class TeamTrialScheduleTest extends TestCase
{
    public function test_from_today_adds_preset_days(): void
    {
        $endsAt = TeamTrialSchedule::resolveEndsAt(
            null,
            TeamTrialSchedule::EXTEND_FROM_TODAY,
            30,
            null,
            [30, 60, 90],
        );

        $this->assertTrue($endsAt->isFuture());
        $this->assertGreaterThanOrEqual(29, (int) now()->startOfDay()->diffInDays($endsAt->startOfDay(), false));
        $this->assertLessThanOrEqual(31, (int) now()->startOfDay()->diffInDays($endsAt->startOfDay(), false));
    }

    public function test_from_current_end_extends_active_trial(): void
    {
        $currentEnd = now()->addDays(10);

        $endsAt = TeamTrialSchedule::resolveEndsAt(
            $currentEnd,
            TeamTrialSchedule::EXTEND_FROM_CURRENT_END,
            30,
            null,
            [30, 60, 90],
        );

        $this->assertSame(30, (int) $currentEnd->diffInDays($endsAt, false));
    }

    public function test_from_current_end_starts_from_now_when_expired(): void
    {
        $endsAt = TeamTrialSchedule::resolveEndsAt(
            now()->subDay(),
            TeamTrialSchedule::EXTEND_FROM_CURRENT_END,
            30,
            null,
            [30, 60, 90],
        );

        $this->assertGreaterThanOrEqual(29, (int) now()->startOfDay()->diffInDays($endsAt->startOfDay(), false));
        $this->assertLessThanOrEqual(31, (int) now()->startOfDay()->diffInDays($endsAt->startOfDay(), false));
    }

    public function test_custom_date_uses_end_of_day(): void
    {
        $date = now()->addDays(14)->toDateString();

        $endsAt = TeamTrialSchedule::resolveEndsAt(
            null,
            TeamTrialSchedule::EXTEND_FROM_TODAY,
            null,
            $date,
            [30, 60, 90],
        );

        $this->assertSame($date, $endsAt->toDateString());
        $this->assertSame(23, $endsAt->hour);
        $this->assertSame(59, $endsAt->minute);
    }

    public function test_rejects_past_custom_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TeamTrialSchedule::resolveEndsAt(
            null,
            TeamTrialSchedule::EXTEND_FROM_TODAY,
            null,
            now()->subDay()->toDateString(),
            [30, 60, 90],
        );
    }
}
