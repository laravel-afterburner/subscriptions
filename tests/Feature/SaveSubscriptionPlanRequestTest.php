<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Http\Requests\SaveSubscriptionPlanRequest;
use Afterburner\Subscriptions\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class SaveSubscriptionPlanRequestTest extends TestCase
{
    public function test_description_is_required_for_stripe_sync(): void
    {
        $validator = Validator::make([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'description' => '',
            'currency' => 'usd',
            'monthly_price_cents' => 2900,
            'annual_price_cents' => 29000,
            'trial_days' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ], SaveSubscriptionPlanRequest::rulesFor(), SaveSubscriptionPlanRequest::validationMessages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }

    public function test_prices_must_meet_stripe_minimum(): void
    {
        $validator = Validator::make([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'description' => 'Full access to the platform.',
            'currency' => 'usd',
            'monthly_price_cents' => 10,
            'annual_price_cents' => 10,
            'trial_days' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ], SaveSubscriptionPlanRequest::rulesFor(), SaveSubscriptionPlanRequest::validationMessages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('monthly_price_cents', $validator->errors()->toArray());
        $this->assertArrayHasKey('annual_price_cents', $validator->errors()->toArray());
    }
}
