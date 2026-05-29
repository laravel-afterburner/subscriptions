<?php

namespace Afterburner\Subscriptions\Support;

use Afterburner\Subscriptions\Enums\BillingInterval;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Subscription;

class SubscriptionSummary
{
    public function __construct(protected Model $team) {}

    public static function forTeam(Model $team): self
    {
        return new self($team);
    }

    public function statusLabel(): string
    {
        return SubscriptionStatus::forTeam($this->team)->statusLabel();
    }

    public function statusBadgeClasses(): string
    {
        return self::badgeClassesForStatusLabel($this->statusLabel());
    }

    public static function badgeClassesForStatusLabel(string $label): string
    {
        return match ($label) {
            'Trial' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
            'Active' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
            'Past due' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
            'Cancelled (grace period)' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        };
    }

    public function plan(): ?SubscriptionPlan
    {
        $plan = $this->team->subscriptionPlan;

        return $plan instanceof SubscriptionPlan ? $plan : null;
    }

    public function subscription(): ?Subscription
    {
        if (! method_exists($this->team, 'subscription')) {
            return null;
        }

        $subscription = $this->team->subscription();

        return $subscription instanceof Subscription ? $subscription : null;
    }

    public function billingInterval(): ?BillingInterval
    {
        $plan = $this->plan();
        $subscription = $this->subscription();
        $stripePrice = $subscription?->stripe_price;

        if (! $plan || ! $stripePrice) {
            return null;
        }

        if ($stripePrice === $plan->stripe_price_id_annual) {
            return BillingInterval::Annual;
        }

        if ($stripePrice === $plan->stripe_price_id_monthly) {
            return BillingInterval::Monthly;
        }

        return null;
    }

    public function billingIntervalLabel(): ?string
    {
        return $this->billingInterval()?->label();
    }

    public function billingAmountLabel(): ?string
    {
        $plan = $this->plan();
        $interval = $this->billingInterval();

        if (! $plan || ! $interval) {
            return null;
        }

        return $plan->formattedPrice($interval);
    }

    public function isOnTrial(): bool
    {
        if ($this->team->onGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription();

        return $subscription?->onTrial() ?? false;
    }

    public function trialEndsAt(): ?CarbonInterface
    {
        $subscription = $this->subscription();

        if ($subscription?->onTrial() && $subscription->trial_ends_at) {
            return $subscription->trial_ends_at;
        }

        if ($this->team->onGenericTrial() && $this->team->trial_ends_at) {
            return $this->team->trial_ends_at;
        }

        return null;
    }

    public function trialDaysRemaining(): ?int
    {
        $trialEndsAt = $this->trialEndsAt();

        if (! $trialEndsAt || ! $trialEndsAt->isFuture()) {
            return null;
        }

        return (int) now()->diffInDays($trialEndsAt, false);
    }

    public function accessEndsAt(): ?CarbonInterface
    {
        $subscription = $this->subscription();

        if ($subscription?->onGracePeriod() && $subscription->ends_at) {
            return $subscription->ends_at;
        }

        return null;
    }

    public function memberSince(): ?CarbonInterface
    {
        return $this->subscription()?->created_at;
    }

    public function hasPaymentMethod(): bool
    {
        return filled($this->team->pm_type) && filled($this->team->pm_last_four);
    }

    public function paymentMethodLabel(): ?string
    {
        if (! $this->hasPaymentMethod()) {
            return null;
        }

        return ucfirst($this->team->pm_type).' ···· '.$this->team->pm_last_four;
    }

    public function billingEmail(): ?string
    {
        $email = $this->team->billing_email ?? null;

        return filled($email) ? $email : null;
    }

    public function hasStripeCustomer(): bool
    {
        return filled($this->team->stripe_id);
    }

    public function timezone(): string
    {
        return $this->team->timezone ?? config('app.timezone');
    }

    public function formatDate(?CarbonInterface $date, string $format = 'M j, Y g:i A'): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->timezone($this->timezone())->format($format);
    }

    /**
     * @return array<int, array{label: string, value: string, hint: ?string}>
     */
    public function highlightStats(): array
    {
        $stats = [];

        if ($this->billingIntervalLabel() && $this->billingAmountLabel()) {
            $stats[] = [
                'label' => 'Billing',
                'value' => $this->billingAmountLabel(),
                'hint' => $this->billingIntervalLabel().' subscription',
            ];
        }

        if ($this->isOnTrial() && $this->trialEndsAt()) {
            $daysRemaining = $this->trialDaysRemaining();
            $stats[] = [
                'label' => 'Trial ends',
                'value' => $this->formatDate($this->trialEndsAt(), 'M j, Y'),
                'hint' => $daysRemaining !== null
                    ? ($daysRemaining === 0 ? 'Ends today' : $daysRemaining.' day'.($daysRemaining === 1 ? '' : 's').' remaining')
                    : null,
            ];
        } elseif ($this->accessEndsAt()) {
            $stats[] = [
                'label' => 'Access until',
                'value' => $this->formatDate($this->accessEndsAt(), 'M j, Y'),
                'hint' => 'Subscription cancelled',
            ];
        } elseif ($this->memberSince()) {
            $stats[] = [
                'label' => 'Member since',
                'value' => $this->formatDate($this->memberSince(), 'M j, Y'),
                'hint' => 'Active subscription',
            ];
        }

        if ($this->billingEmail()) {
            $stats[] = [
                'label' => 'Billing email',
                'value' => $this->billingEmail(),
                'hint' => 'Receipts and billing notices',
            ];
        } elseif ($this->hasStripeCustomer()) {
            $stats[] = [
                'label' => 'Stripe customer',
                'value' => 'Connected',
                'hint' => 'Billing profile on file',
            ];
        }

        return $stats;
    }
}
