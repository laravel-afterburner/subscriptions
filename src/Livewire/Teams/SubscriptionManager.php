<?php

namespace Afterburner\Subscriptions\Livewire\Teams;

use Afterburner\Subscriptions\Actions\Stripe\CreateCheckoutSession;
use Afterburner\Subscriptions\Enums\BillingInterval;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Afterburner\Subscriptions\Support\PlanEntitlements;
use Afterburner\Subscriptions\Support\SubscriptionsPermissions;
use Afterburner\Subscriptions\Support\SubscriptionStatus;
use Afterburner\Subscriptions\Support\SubscriptionSummary;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Stripe\Exception\InvalidRequestException;

class SubscriptionManager extends Component
{
    use WithPagination;

    public Model $team;

    public ?string $searchQuery = null;

    public string $promotionCode = '';

    protected $queryString = [
        'searchQuery' => ['except' => ''],
    ];

    public function mount(Model $team): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->belongsToTeam($team), 403);
        abort_unless(SubscriptionsPermissions::canAccessModule($user, $team), 403);

        $this->team = $team;
    }

    public function updatedSearchQuery(): void
    {
        $this->resetPage('invoicesPage');
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
        $user = auth()->user();
        $sectionOrder = $user instanceof User
            ? SubscriptionsPermissions::visibleSections($user, $this->team)
            : [];
        $visible = array_flip($sectionOrder);
        $show = fn (string $section): bool => isset($visible[$section]);

        $plans = $show(SubscriptionsPermissions::SECTION_PLANS)
            ? SubscriptionPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
            : collect();

        $status = SubscriptionStatus::forTeam($this->team);
        $invoices = collect();

        if ($show(SubscriptionsPermissions::SECTION_INVOICES) && method_exists($this->team, 'invoices') && $this->team->stripe_id) {
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

        $page = LengthAwarePaginator::resolveCurrentPage('invoicesPage');
        $perPage = 10;
        $invoices = new LengthAwarePaginator(
            $invoices->forPage($page, $perPage)->values(),
            $invoices->count(),
            $perPage,
            $page,
            ['pageName' => 'invoicesPage'],
        );

        return view('afterburner-subscriptions::subscriptions.livewire.manager', [
            'sectionOrder' => $sectionOrder,
            'plans' => $plans,
            'team' => $this->team,
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
