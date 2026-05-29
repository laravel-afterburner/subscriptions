<?php

namespace Afterburner\Subscriptions\Models;

use Afterburner\Subscriptions\Enums\PromotionDuration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPromotionCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'stripe_coupon_id',
        'stripe_promotion_code_id',
        'percent_off',
        'amount_off_cents',
        'duration',
        'duration_in_months',
        'max_redemptions',
        'redeem_by',
        'subscription_plan_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'percent_off' => 'integer',
            'amount_off_cents' => 'integer',
            'duration' => PromotionDuration::class,
            'duration_in_months' => 'integer',
            'max_redemptions' => 'integer',
            'redeem_by' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * @param  Builder<SubscriptionPromotionCode>  $query
     * @return Builder<SubscriptionPromotionCode>
     */
    public function scopeRedeemable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereNotNull('stripe_promotion_code_id')
            ->where('stripe_promotion_code_id', '!=', '')
            ->where(function (Builder $query): void {
                $query->whereNull('redeem_by')
                    ->orWhere('redeem_by', '>', now());
            });
    }

    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->redeem_by !== null && $this->redeem_by->isPast()) {
            return false;
        }

        if ($this->stripe_promotion_code_id === null || $this->stripe_promotion_code_id === '') {
            return false;
        }

        return true;
    }

    public function appliesToPlan(?SubscriptionPlan $plan): bool
    {
        if ($this->subscription_plan_id === null) {
            return true;
        }

        return $plan !== null && $this->subscription_plan_id === $plan->id;
    }

    public function formattedDiscount(): string
    {
        if ($this->percent_off !== null) {
            return $this->percent_off.'% off';
        }

        if ($this->amount_off_cents !== null) {
            $currency = strtoupper(config('afterburner-subscriptions.currency', 'usd'));

            return number_format($this->amount_off_cents / 100, 2).' '.$currency.' off';
        }

        return 'Discount';
    }
}
