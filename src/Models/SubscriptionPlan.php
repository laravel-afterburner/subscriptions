<?php

namespace Afterburner\Subscriptions\Models;

use Afterburner\Subscriptions\Enums\BillingInterval;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'currency',
        'stripe_product_id',
        'monthly_price_cents',
        'annual_price_cents',
        'stripe_price_id_monthly',
        'stripe_price_id_annual',
        'trial_days',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price_cents' => 'integer',
            'annual_price_cents' => 'integer',
            'trial_days' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function stripePriceIdFor(BillingInterval|string $interval): ?string
    {
        $interval = $interval instanceof BillingInterval
            ? $interval
            : BillingInterval::from($interval);

        return match ($interval) {
            BillingInterval::Monthly => $this->stripe_price_id_monthly,
            BillingInterval::Annual => $this->stripe_price_id_annual,
        };
    }

    public function priceCentsFor(BillingInterval|string $interval): int
    {
        $interval = $interval instanceof BillingInterval
            ? $interval
            : BillingInterval::from($interval);

        return match ($interval) {
            BillingInterval::Monthly => $this->monthly_price_cents,
            BillingInterval::Annual => $this->annual_price_cents,
        };
    }

    public function currencyCode(): string
    {
        return strtolower($this->currency ?? config('afterburner-subscriptions.currency', 'usd'));
    }

    public function formattedPrice(BillingInterval|string $interval): string
    {
        $cents = $this->priceCentsFor($interval);
        $currency = strtoupper($this->currencyCode());

        return number_format($cents / 100, 2).' '.$currency;
    }

    public function annualSavingsPercent(): ?int
    {
        $annualizedMonthly = $this->monthly_price_cents * 12;

        if ($annualizedMonthly <= 0 || $this->annual_price_cents >= $annualizedMonthly) {
            return null;
        }

        return (int) round((1 - $this->annual_price_cents / $annualizedMonthly) * 100);
    }

    public function currencySymbol(): string
    {
        return match ($this->currencyCode()) {
            'usd' => '$',
            'aud' => 'A$',
            'cad' => 'C$',
            'gbp' => '£',
            'eur' => '€',
            default => strtoupper($this->currencyCode()).' ',
        };
    }
}
