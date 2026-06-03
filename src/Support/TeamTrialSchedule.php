<?php

namespace Afterburner\Subscriptions\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class TeamTrialSchedule
{
    public const EXTEND_FROM_TODAY = 'from_today';

    public const EXTEND_FROM_CURRENT_END = 'from_current_end';

    /**
     * @param  array<int, int>  $allowedPresets
     */
    public static function resolveEndsAt(
        ?CarbonInterface $currentTrialEndsAt,
        string $extendMode,
        ?int $presetDays,
        ?string $customDate,
        array $allowedPresets,
        string $timezone,
    ): CarbonInterface {
        if ($customDate !== null && $customDate !== '') {
            $endsAt = Carbon::parse($customDate, $timezone)->endOfDay();

            if ($endsAt->lte(now($timezone))) {
                throw new InvalidArgumentException('Trial end date must be in the future.');
            }

            return $endsAt;
        }

        if ($presetDays === null || ! in_array($presetDays, $allowedPresets, true)) {
            throw new InvalidArgumentException('Select a valid trial duration.');
        }

        $base = match ($extendMode) {
            self::EXTEND_FROM_CURRENT_END => self::baseForExtension($currentTrialEndsAt, $timezone),
            self::EXTEND_FROM_TODAY => now($timezone),
            default => throw new InvalidArgumentException('Invalid extend mode.'),
        };

        return static::endOfTrialDay($base->copy()->addDays($presetDays), $timezone);
    }

    protected static function baseForExtension(?CarbonInterface $currentTrialEndsAt, string $timezone): CarbonInterface
    {
        if ($currentTrialEndsAt !== null && $currentTrialEndsAt->isFuture()) {
            return $currentTrialEndsAt->copy()->timezone($timezone);
        }

        return now($timezone);
    }

    protected static function endOfTrialDay(CarbonInterface $moment, string $timezone): CarbonInterface
    {
        return $moment->copy()->timezone($timezone)->endOfDay();
    }
}
