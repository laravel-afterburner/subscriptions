<?php

namespace Afterburner\Subscriptions\Support;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class TeamTrialDisplay
{
    public static function teamTimezone(Model $team): string
    {
        return filled($team->timezone ?? null)
            ? (string) $team->timezone
            : (string) config('app.timezone');
    }

    public static function formatTrialEndsAt(?CarbonInterface $endsAt, Model $team, string $format = 'date'): string
    {
        if ($endsAt === null) {
            return '';
        }

        $localized = $endsAt->copy()->timezone(static::teamTimezone($team));

        if (function_exists('format_date_superscript')) {
            return format_date_superscript($localized, $format);
        }

        return $localized->format('F j, Y');
    }

    public static function minCustomTrialDate(Model $team): string
    {
        return now(static::teamTimezone($team))->addDay()->toDateString();
    }
}
