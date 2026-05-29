<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Http\Requests\SaveSubscriptionPlanRequest;
use Afterburner\Subscriptions\Support\PlanPriceInput;
use Afterburner\Subscriptions\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class PlanPriceInputTest extends TestCase
{
    public function test_dollars_are_converted_to_cents(): void
    {
        $this->assertSame(2900, PlanPriceInput::dollarsToCents('29.00'));
        $this->assertSame(2999, PlanPriceInput::dollarsToCents('29.99'));
        $this->assertSame(50, PlanPriceInput::dollarsToCents('0.50'));
    }

    public function test_cents_are_converted_to_dollars_for_form_display(): void
    {
        $this->assertSame('29.00', PlanPriceInput::centsToDollars(2900));
        $this->assertSame('29.99', PlanPriceInput::centsToDollars(2999));
    }

    public function test_form_validation_converts_prices_to_cents(): void
    {
        $validated = Validator::make([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'description' => 'Full access to the platform.',
            'currency' => 'cad',
            'monthly_price' => '39.99',
            'annual_price' => '399.00',
            'trial_days' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ], SaveSubscriptionPlanRequest::formRulesFor(), SaveSubscriptionPlanRequest::formValidationMessages())->validate();

        $attributes = SaveSubscriptionPlanRequest::toPlanAttributes($validated);

        $this->assertSame(3999, $attributes['monthly_price_cents']);
        $this->assertSame(39900, $attributes['annual_price_cents']);
        $this->assertArrayNotHasKey('monthly_price', $attributes);
    }

    public function test_form_prices_must_meet_stripe_minimum(): void
    {
        $validator = Validator::make([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'description' => 'Full access to the platform.',
            'currency' => 'usd',
            'monthly_price' => '0.10',
            'annual_price' => '0.10',
            'trial_days' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ], SaveSubscriptionPlanRequest::formRulesFor(), SaveSubscriptionPlanRequest::formValidationMessages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('monthly_price', $validator->errors()->toArray());
        $this->assertArrayHasKey('annual_price', $validator->errors()->toArray());
    }
}
