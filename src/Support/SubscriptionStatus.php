<?php

namespace Afterburner\Subscriptions\Support;

use Afterburner\Subscriptions\Concerns\HasSubscriptions;
use Illuminate\Database\Eloquent\Model;

class SubscriptionStatus
{
    public function __construct(protected Model $team) {}

    public static function forTeam(Model $team): self
    {
        return new self($team);
    }

    public function isActive(): bool
    {
        if (! config('afterburner-subscriptions.enabled', true)) {
            return true;
        }

        if (! $this->teamHasSubscriptionSupport()) {
            return true;
        }

        if ($this->team->onGenericTrial()) {
            return true;
        }

        if (! $this->subscriptionsTableExists()) {
            return false;
        }

        if (method_exists($this->team, 'subscribed') && $this->team->subscribed()) {
            return true;
        }

        if (method_exists($this->team, 'subscription')) {
            $subscription = $this->team->subscription();

            if ($subscription && ! $subscription->ended()) {
                return true;
            }
        }

        return false;
    }

    protected function subscriptionsTableExists(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('subscriptions');
    }

    public function isBlocked(): bool
    {
        return ! $this->isActive();
    }

    public function statusLabel(): string
    {
        if ($this->team->onGenericTrial()) {
            return 'Trial';
        }

        if (method_exists($this->team, 'subscription')) {
            $subscription = $this->team->subscription();

            if ($subscription?->onGracePeriod()) {
                return 'Cancelled (grace period)';
            }

            if ($subscription?->pastDue()) {
                return 'Past due';
            }

            if ($subscription && ! $subscription->ended()) {
                return 'Active';
            }
        }

        return 'Inactive';
    }

    protected function teamHasSubscriptionSupport(): bool
    {
        return in_array(HasSubscriptions::class, class_uses_recursive($this->team), true);
    }
}
