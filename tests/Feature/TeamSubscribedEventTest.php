<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Actions\Stripe\HandleWebhookEvent;
use Afterburner\Subscriptions\Events\TeamSubscribed;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Events\WebhookReceived;

class TeamSubscribedEventTest extends TestCase
{
    public function test_checkout_completed_dispatches_team_subscribed_event(): void
    {
        Event::fake([TeamSubscribed::class]);

        $plan = SubscriptionPlan::query()->create([
            'name' => 'Growth',
            'slug' => 'growth',
            'description' => 'Growth plan',
            'monthly_price_cents' => 3000,
            'annual_price_cents' => 30000,
        ]);

        [, $team] = $this->createTeamWithUser();

        app(HandleWebhookEvent::class)(new WebhookReceived([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'metadata' => [
                        'team_id' => (string) $team->id,
                        'subscription_plan_id' => (string) $plan->id,
                        'billing_interval' => 'month',
                    ],
                ],
            ],
        ]));

        Event::assertDispatched(TeamSubscribed::class, function (TeamSubscribed $event) use ($team, $plan) {
            return $event->team->is($team)
                && $event->plan->is($plan)
                && $event->checkoutMetadata['billing_interval'] === 'month';
        });

        $this->assertSame($plan->id, $team->fresh()->subscription_plan_id);
    }
}
