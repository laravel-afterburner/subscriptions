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
    ): CarbonInterface {
        if ($customDate !== null && $customDate !== '') {
            $endsAt = Carbon::parse($customDate)->endOfDay();

            if ($endsAt->isPast()) {
                throw new InvalidArgumentException('Trial end date must be in the future.');
            }

            return $endsAt;
        }

        if ($presetDays === null || ! in_array($presetDays, $allowedPresets, true)) {
            throw new InvalidArgumentException('Select a valid trial duration.');
        }

        $base = match ($extendMode) {
            self::EXTEND_FROM_CURRENT_END => self::baseForExtension($currentTrialEndsAt),
            self::EXTEND_FROM_TODAY => now(),
            default => throw new InvalidArgumentException('Invalid extend mode.'),
        };

        return $base->copy()->addDays($presetDays);
    }

    protected static function baseForExtension(?CarbonInterface $currentTrialEndsAt): CarbonInterface
    {
        if ($currentTrialEndsAt !== null && $currentTrialEndsAt->isFuture()) {
            return $currentTrialEndsAt;
        }

        return now();
    }
}
