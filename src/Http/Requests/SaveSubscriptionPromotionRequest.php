<?php

namespace Afterburner\Subscriptions\Http\Requests;

use Afterburner\Subscriptions\Enums\PromotionDuration;
use Afterburner\Subscriptions\Support\PlanPriceInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class SaveSubscriptionPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $promotionId = $this->route('promotion')?->id;

        return static::rulesFor($promotionId);
    }

    /**
     * @return array<string, mixed>
     */
    public static function formRulesFor(?int $promotionId = null): array
    {
        $minAmount = PlanPriceInput::minimumPriceDollars();

        return [
            'code' => [
                'required',
                'string',
                'max:40',
                'alpha_dash',
                Rule::unique('subscription_promotion_codes', 'code')->ignore($promotionId),
            ],
            'name' => ['required', 'string', 'max:250'],
            'percent_off' => ['nullable', 'integer', 'min:1', 'max:100', 'required_without:amount_off'],
            'amount_off' => ['nullable', 'numeric', 'decimal:0,2', 'min:'.$minAmount, 'required_without:percent_off'],
            'duration' => ['required', new Enum(PromotionDuration::class)],
            'duration_in_months' => [
                'nullable',
                'integer',
                'min:1',
                'max:36',
                'required_if:duration,'.PromotionDuration::Repeating->value,
            ],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'redeem_by' => ['nullable', 'date'],
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function toPromotionAttributes(array $validated): array
    {
        if (array_key_exists('amount_off', $validated) && $validated['amount_off'] !== null && $validated['amount_off'] !== '') {
            $validated['amount_off_cents'] = PlanPriceInput::dollarsToCents($validated['amount_off']);
        } else {
            $validated['amount_off_cents'] = null;
        }

        unset($validated['amount_off']);

        return $validated;
    }

    public static function rulesFor(?int $promotionId = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:40',
                'alpha_dash',
                Rule::unique('subscription_promotion_codes', 'code')->ignore($promotionId),
            ],
            'name' => ['required', 'string', 'max:250'],
            'percent_off' => ['nullable', 'integer', 'min:1', 'max:100', 'required_without:amount_off_cents'],
            'amount_off_cents' => ['nullable', 'integer', 'min:'.(int) (PlanPriceInput::minimumPriceDollars() * 100), 'required_without:percent_off'],
            'duration' => ['required', new Enum(PromotionDuration::class)],
            'duration_in_months' => [
                'nullable',
                'integer',
                'min:1',
                'max:36',
                'required_if:duration,'.PromotionDuration::Repeating->value,
            ],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'redeem_by' => ['nullable', 'date'],
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function validationMessages(): array
    {
        return [
            'percent_off.required_without' => 'Enter either a percentage or fixed amount discount.',
            'amount_off_cents.required_without' => 'Enter either a percentage or fixed amount discount.',
            'duration_in_months.required_if' => 'Duration in months is required for repeating coupons.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function formValidationMessages(): array
    {
        $minAmount = number_format(PlanPriceInput::minimumPriceDollars(), 2);

        return [
            'percent_off.required_without' => 'Enter either a percentage or fixed amount discount.',
            'amount_off.required_without' => 'Enter either a percentage or fixed amount discount.',
            'amount_off.min' => "Amount off must be at least {$minAmount} (Stripe minimum charge).",
            'duration_in_months.required_if' => 'Duration in months is required for repeating coupons.',
        ];
    }
}
