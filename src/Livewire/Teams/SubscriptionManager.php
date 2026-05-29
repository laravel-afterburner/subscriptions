<?php

namespace Afterburner\Subscriptions\Livewire\Teams;

use Afterburner\Subscriptions\Actions\Stripe\CreateCheckoutSession;
use Afterburner\Subscriptions\Enums\BillingInterval;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Afterburner\Subscriptions\Support\PlanEntitlements;
use Afterburner\Subscriptions\Support\SubscriptionStatus;
use Afterburner\Subscriptions\Support\SubscriptionSummary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Stripe\Exception\InvalidRequestException;

class SubscriptionManager extends Component
{
    public Model $team;

    public ?string $searchQuery = null;

    public string $promotionCode = '';

    protected $queryString = [
        'searchQuery' => ['except' => ''],
    ];

    public function mount(Model $team): void
    {
        $this->team = $team;
    }

    public function subscribe(int $planId, string $interval): mixed
    {
        $this->authorize('manageBilling', $this->team);

        $plan = SubscriptionPlan::query()
            ->where('is_active', true)
            ->findOrFail($planId);

        $checkout = app(CreateCheckoutSession::class)(
            $this->team,
            $plan,
            BillingInterval::from($interval),
            $this->promotionCode !== '' ? $this->promotionCode : null
        );

        return redirect()->away($checkout->url);
    }

    public function openBillingPortal(): mixed
    {
        $this->authorize('manageBilling', $this->team);

        return redirect()->route('teams.subscriptions.billing-portal', $this->team);
    }

    public function render()
    {
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $status = SubscriptionStatus::forTeam($this->team);
        $invoices = collect();

        if (method_exists($this->team, 'invoices') && $this->team->stripe_id) {
            try {
                $invoices = collect($this->team->invoices());

                if ($this->searchQuery) {
                    $search = strtolower($this->searchQuery);
                    $timezone = $this->team->timezone ?? config('app.timezone');

                    $invoices = $invoices->filter(function ($invoice) use ($search, $timezone) {
                        $haystack = strtolower(implode(' ', array_filter([
                            $invoice->date()->timezone($timezone)->format('M j, Y'),
                            $invoice->number ?? '',
                            $invoice->total(),
                            $invoice->isPaid() ? 'paid' : 'unpaid',
                        ])));

                        return str_contains($haystack, $search);
                    })->values();
                }
            } catch (InvalidRequestException $exception) {
                Log::warning('Unable to load Stripe invoices for team.', [
                    'team_id' => $this->team->getKey(),
                    'stripe_id' => $this->team->stripe_id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return view('afterburner-subscriptions::subscriptions.livewire.manager', [
            'plans' => $plans,
            'statusLabel' => $status->statusLabel(),
            'isActive' => $status->isActive(),
            'summary' => SubscriptionSummary::forTeam($this->team),
            'invoices' => $invoices,
            'canManage' => auth()->user()?->can('manageBilling', $this->team) ?? false,
            'entitlements' => PlanEntitlements::forTeam($this->team),
            'promotionsEnabled' => config('afterburner-subscriptions.promotions_enabled', true),
            'hasActivePromotionCodes' => SubscriptionPromotionCode::query()->redeemable()->exists(),
        ]);
    }
}
