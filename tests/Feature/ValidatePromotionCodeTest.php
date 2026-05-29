<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Actions\Stripe\ValidatePromotionCode;
use Afterburner\Subscriptions\Enums\PromotionDuration;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Afterburner\Subscriptions\Tests\TestCase;
use Illuminate\Validation\ValidationException;

class ValidatePromotionCodeTest extends TestCase
{
    public function test_valid_promotion_code_is_returned(): void
    {
        $promotion = SubscriptionPromotionCode::query()->create([
            'code' => 'SAVE20',
            'name' => 'Save 20',
            'percent_off' => 20,
            'duration' => PromotionDuration::Once,
            'stripe_promotion_code_id' => 'promo_test',
            'is_active' => true,
        ]);

        $result = app(ValidatePromotionCode::class)('save20');

        $this->assertTrue($result->is($promotion));
    }

    public function test_expired_promotion_is_rejected(): void
    {
        SubscriptionPromotionCode::query()->create([
            'code' => 'OLD',
            'name' => 'Old promo',
            'percent_off' => 10,
            'duration' => PromotionDuration::Once,
            'stripe_promotion_code_id' => 'promo_old',
            'is_active' => true,
            'redeem_by' => now()->subDay(),
        ]);

        $this->expectException(ValidationException::class);

        app(ValidatePromotionCode::class)('OLD');
    }

    public function test_redeemable_scope_excludes_inactive_and_expired_promotions(): void
    {
        SubscriptionPromotionCode::query()->create([
            'code' => 'INACTIVE',
            'name' => 'Inactive',
            'percent_off' => 10,
            'duration' => PromotionDuration::Once,
            'stripe_promotion_code_id' => 'promo_inactive',
            'is_active' => false,
        ]);

        SubscriptionPromotionCode::query()->create([
            'code' => 'EXPIRED',
            'name' => 'Expired',
            'percent_off' => 10,
            'duration' => PromotionDuration::Once,
            'stripe_promotion_code_id' => 'promo_expired',
            'is_active' => true,
            'redeem_by' => now()->subDay(),
        ]);

        SubscriptionPromotionCode::query()->create([
            'code' => 'ACTIVE',
            'name' => 'Active',
            'percent_off' => 10,
            'duration' => PromotionDuration::Once,
            'stripe_promotion_code_id' => 'promo_active',
            'is_active' => true,
        ]);

        $redeemable = SubscriptionPromotionCode::query()->redeemable()->pluck('code');

        $this->assertEquals(['ACTIVE'], $redeemable->all());
    }

    public function test_plan_restricted_promotion_is_rejected_for_other_plans(): void
    {
        $planA = SubscriptionPlan::query()->create([
            'name' => 'A',
            'slug' => 'plan-a',
            'description' => 'Plan A',
            'monthly_price_cents' => 1000,
            'annual_price_cents' => 10000,
        ]);

        $planB = SubscriptionPlan::query()->create([
            'name' => 'B',
            'slug' => 'plan-b',
            'description' => 'Plan B',
            'monthly_price_cents' => 2000,
            'annual_price_cents' => 20000,
        ]);

        SubscriptionPromotionCode::query()->create([
            'code' => 'PLANA',
            'name' => 'Plan A only',
            'percent_off' => 15,
            'duration' => PromotionDuration::Once,
            'stripe_promotion_code_id' => 'promo_plana',
            'subscription_plan_id' => $planA->id,
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(ValidatePromotionCode::class)('PLANA', $planB);
    }
}
