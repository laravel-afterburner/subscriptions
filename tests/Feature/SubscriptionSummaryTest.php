<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Enums\BillingInterval;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\SubscriptionSummary;
use Afterburner\Subscriptions\Tests\TestCase;
use App\Models\Team;

class SubscriptionSummaryTest extends TestCase
{
    public function test_trial_team_has_trial_badge_and_days_remaining(): void
    {
        [, $team] = $this->createTeamWithUser();
        $team->update(['trial_ends_at' => now()->addDays(10)]);

        $summary = SubscriptionSummary::forTeam($team->fresh());

        $this->assertSame('Trial', $summary->statusLabel());
        $this->assertTrue($summary->isOnTrial());
        $this->assertGreaterThanOrEqual(9, $summary->trialDaysRemaining());
        $this->assertLessThanOrEqual(10, $summary->trialDaysRemaining());
        $this->assertStringContainsString('blue', $summary->statusBadgeClasses());
    }

    public function test_payment_method_label_is_formatted(): void
    {
        [, $team] = $this->createTeamWithUser();
        $team->forceFill([
            'pm_type' => 'visa',
            'pm_last_four' => '4242',
        ])->save();

        $summary = SubscriptionSummary::forTeam($team->fresh());

        $this->assertTrue($summary->hasPaymentMethod());
        $this->assertSame('Visa ···· 4242', $summary->paymentMethodLabel());
    }

    public function test_billing_interval_detected_from_stripe_price(): void
    {
        [, $team] = $this->createTeamWithUser();

        $plan = SubscriptionPlan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'monthly_price_cents' => 1000,
            'annual_price_cents' => 10000,
            'stripe_price_id_monthly' => 'price_monthly_test',
            'stripe_price_id_annual' => 'price_annual_test',
            'is_active' => true,
        ]);

        $team->update(['subscription_plan_id' => $plan->id]);

        $team->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_annual_test',
        ]);

        $summary = SubscriptionSummary::forTeam($team->fresh()->load('subscriptionPlan'));

        $this->assertSame(BillingInterval::Annual, $summary->billingInterval());
        $this->assertSame('Annual', $summary->billingIntervalLabel());
        $this->assertSame('100.00 USD', $summary->billingAmountLabel());
    }
}
