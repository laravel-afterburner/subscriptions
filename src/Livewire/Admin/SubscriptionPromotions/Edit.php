<?php

namespace Afterburner\Subscriptions\Livewire\Admin\SubscriptionPromotions;

use Afterburner\Subscriptions\Actions\Stripe\SyncPromotionCodeToStripe;
use Afterburner\Subscriptions\Enums\PromotionDuration;
use Afterburner\Subscriptions\Http\Requests\SaveSubscriptionPromotionRequest;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Afterburner\Subscriptions\Support\PlanPriceInput;
use Livewire\Component;

class Edit extends Component
{
    public SubscriptionPromotionCode $promotion;

    public string $code = '';

    public string $name = '';

    public ?int $percent_off = null;

    public string $amount_off = '';

    public string $duration = 'once';

    public ?int $duration_in_months = null;

    public ?int $max_redemptions = null;

    public ?string $redeem_by = null;

    public ?int $subscription_plan_id = null;

    public bool $is_active = true;

    public function mount(SubscriptionPromotionCode $promotion): void
    {
        $this->authorize('update', $promotion);

        $this->promotion = $promotion;
        $this->code = $promotion->code;
        $this->name = $promotion->name;
        $this->percent_off = $promotion->percent_off;
        $this->amount_off = $promotion->amount_off_cents !== null
            ? PlanPriceInput::centsToDollars($promotion->amount_off_cents)
            : '';
        $this->duration = $promotion->duration instanceof PromotionDuration
            ? $promotion->duration->value
            : $promotion->duration;
        $this->duration_in_months = $promotion->duration_in_months;
        $this->max_redemptions = $promotion->max_redemptions;
        $this->redeem_by = $promotion->redeem_by?->format('Y-m-d');
        $this->subscription_plan_id = $promotion->subscription_plan_id;
        $this->is_active = $promotion->is_active;
    }

    public function save(): void
    {
        $this->authorize('update', $this->promotion);

        $validated = $this->validate(
            SaveSubscriptionPromotionRequest::formRulesFor($this->promotion->id),
            SaveSubscriptionPromotionRequest::formValidationMessages()
        );

        $validated['code'] = strtoupper($validated['code']);
        $validated['duration'] = PromotionDuration::from($validated['duration']);

        $this->promotion->update(
            SaveSubscriptionPromotionRequest::toPromotionAttributes($validated)
        );

        app(SyncPromotionCodeToStripe::class)($this->promotion->fresh());

        session()->flash('flash', [
            'bannerStyle' => 'success',
            'banner' => 'Promotion code updated and synced to Stripe.',
        ]);

        $this->redirectRoute('admin.subscription-promotions.show', $this->promotion);
    }

    public function render()
    {
        return view('afterburner-subscriptions::admin.subscription-promotions.livewire.edit', [
            'plans' => SubscriptionPlan::query()->orderBy('name')->get(),
            'durations' => PromotionDuration::cases(),
        ]);
    }
}
