<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Tests\TestCase;

class SubscriptionPlanPricingTest extends TestCase
{
    public function test_annual_savings_percent_is_calculated(): void
    {
        $plan = new SubscriptionPlan([
            'monthly_price_cents' => 1000,
            'annual_price_cents' => 10000,
        ]);

        $this->assertSame(17, $plan->annualSavingsPercent());
    }

    public function test_annual_savings_percent_is_null_when_no_savings(): void
    {
        $plan = new SubscriptionPlan([
            'monthly_price_cents' => 1000,
            'annual_price_cents' => 12000,
        ]);

        $this->assertNull($plan->annualSavingsPercent());
    }
}
