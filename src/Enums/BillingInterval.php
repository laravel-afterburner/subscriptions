<?php

namespace Afterburner\Subscriptions\Enums;

enum BillingInterval: string
{
    case Monthly = 'month';
    case Annual = 'year';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Annual => 'Annual',
        };
    }
}
