<?php

namespace Afterburner\Subscriptions\Livewire\Admin\SubscriptionPromotions;

use Afterburner\Subscriptions\Actions\Stripe\SyncPromotionCodeToStripe;
use Afterburner\Subscriptions\Enums\PromotionDuration;
use Afterburner\Subscriptions\Http\Requests\SaveSubscriptionPromotionRequest;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Illuminate\Support\Str;
use Livewire\Component;

class Create extends Component
{
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

    public function updatedCode(string $value): void
    {
        $this->code = Str::upper(Str::slug($value, ''));
    }

    public function save(): void
    {
        $this->authorize('create', SubscriptionPromotionCode::class);

        $validated = $this->validate(
            SaveSubscriptionPromotionRequest::formRulesFor(),
            SaveSubscriptionPromotionRequest::formValidationMessages()
        );

        $validated['code'] = Str::upper($validated['code']);
        $validated['duration'] = PromotionDuration::from($validated['duration']);

        $promotion = SubscriptionPromotionCode::query()->create(
            SaveSubscriptionPromotionRequest::toPromotionAttributes($validated)
        );

        app(SyncPromotionCodeToStripe::class)($promotion);

        session()->flash('flash', [
            'bannerStyle' => 'success',
            'banner' => 'Promotion code created and synced to Stripe.',
        ]);

        $this->redirectRoute('admin.subscription-promotions.show', $promotion);
    }

    public function render()
    {
        return view('afterburner-subscriptions::admin.subscription-promotions.livewire.create', [
            'plans' => SubscriptionPlan::query()->orderBy('name')->get(),
            'durations' => PromotionDuration::cases(),
        ]);
    }
}
