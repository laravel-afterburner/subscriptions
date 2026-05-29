<?php

namespace Afterburner\Subscriptions\Http\Requests;

use Afterburner\Subscriptions\Support\PlanPriceInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $planId = $this->route('plan')?->id;

        return static::rulesFor($planId);
    }

    /**
     * @return array<string, mixed>
     */
    public static function formRulesFor(?int $planId = null): array
    {
        $minPrice = PlanPriceInput::minimumPriceDollars();

        return [
            'name' => ['required', 'string', 'max:250'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('subscription_plans', 'slug')->ignore($planId)],
            'description' => ['required', 'string', 'max:5000'],
            'currency' => ['required', 'string', 'size:3', Rule::in(array_keys(config('afterburner-subscriptions.supported_currencies', ['usd' => 'USD'])))],
            'monthly_price' => ['required', 'numeric', 'decimal:0,2', 'min:'.$minPrice],
            'annual_price' => ['required', 'numeric', 'decimal:0,2', 'min:'.$minPrice],
            'trial_days' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'max_users_per_team' => ['nullable', 'integer', 'min:1'],
            'max_storage_gb' => ['nullable', 'integer', 'min:1'],
            'feature_slugs' => ['nullable', 'array'],
            'feature_slugs.*' => ['string', 'max:100'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function toPlanAttributes(array $validated): array
    {
        $validated['monthly_price_cents'] = PlanPriceInput::dollarsToCents($validated['monthly_price']);
        $validated['annual_price_cents'] = PlanPriceInput::dollarsToCents($validated['annual_price']);
        unset(
            $validated['monthly_price'],
            $validated['annual_price'],
            $validated['max_users_per_team'],
            $validated['max_storage_gb'],
            $validated['feature_slugs'],
        );

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rulesFor(?int $planId = null): array
    {
        $minPrice = static::minimumPriceCents();

        return [
            'name' => ['required', 'string', 'max:250'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('subscription_plans', 'slug')->ignore($planId)],
            'description' => ['required', 'string', 'max:5000'],
            'currency' => ['required', 'string', 'size:3', Rule::in(array_keys(config('afterburner-subscriptions.supported_currencies', ['usd' => 'USD'])))],
            'monthly_price_cents' => ['required', 'integer', 'min:'.$minPrice],
            'annual_price_cents' => ['required', 'integer', 'min:'.$minPrice],
            'trial_days' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.max_users_per_team' => ['nullable', 'integer', 'min:1'],
            'features.max_storage_gb' => ['nullable', 'integer', 'min:1'],
            'features.features' => ['nullable', 'array'],
            'features.features.*' => ['string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function validationMessages(): array
    {
        $minPrice = static::minimumPriceCents();

        return [
            'name.required' => 'Plan name is required for Stripe.',
            'description.required' => 'Description is required. Stripe rejects empty product descriptions.',
            'monthly_price_cents.min' => "Monthly price must be at least {$minPrice} cents (Stripe minimum charge).",
            'annual_price_cents.min' => "Annual price must be at least {$minPrice} cents (Stripe minimum charge).",
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function formValidationMessages(): array
    {
        $minPrice = number_format(PlanPriceInput::minimumPriceDollars(), 2);

        return [
            'name.required' => 'Plan name is required for Stripe.',
            'description.required' => 'Description is required. Stripe rejects empty product descriptions.',
            'monthly_price.min' => "Monthly price must be at least {$minPrice} (Stripe minimum charge).",
            'annual_price.min' => "Annual price must be at least {$minPrice} (Stripe minimum charge).",
        ];
    }

    public static function minimumPriceCents(): int
    {
        return (int) config('afterburner-subscriptions.minimum_price_cents', 50);
    }
}
