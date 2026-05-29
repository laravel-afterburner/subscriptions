<?php

namespace Afterburner\Subscriptions\Support;

class PlanPriceInput
{
    public static function dollarsToCents(float|string|null $dollars): int
    {
        return (int) round(((float) $dollars) * 100);
    }

    public static function centsToDollars(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    public static function minimumPriceDollars(): float
    {
        return (int) config('afterburner-subscriptions.minimum_price_cents', 50) / 100;
    }
}
