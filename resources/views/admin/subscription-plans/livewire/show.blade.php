<div class="space-y-6">
    <a href="{{ route('admin.subscription-plans.index') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        &larr; Back to plans
    </a>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $plan->name }}</h3>
                <p class="mt-1 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $plan->slug }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span @class([
                    'rounded-full px-3 py-1 text-xs font-medium',
                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' => $plan->is_active,
                    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => ! $plan->is_active,
                ])>
                    {{ $plan->is_active ? 'Active' : 'Inactive' }}
                </span>

                @if ($canEdit)
                    <x-button type="button" wire:click="editPlan" no-spinner>
                        Edit plan
                    </x-button>
                @endif
            </div>
        </div>

        @if ($plan->description)
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $plan->description }}</p>
        @endif

        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Currency</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ strtoupper($plan->currencyCode()) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Monthly price</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->formattedPrice(\Afterburner\Subscriptions\Enums\BillingInterval::Monthly) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Annual price</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $plan->formattedPrice(\Afterburner\Subscriptions\Enums\BillingInterval::Annual) }}
                    @if ($savings = $plan->annualSavingsPercent())
                        <span class="text-green-700 dark:text-green-300">({{ $savings }}% savings)</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Trial days</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->trial_days }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Sort order</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->sort_order }}</dd>
            </div>
        </dl>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
            <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">Plan entitlements</h4>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Max users per entity</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        {{ $features['max_users_per_team'] ?? 'Unlimited' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Max storage (GB)</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        {{ $features['max_storage_gb'] ?? 'Unlimited' }}
                    </dd>
                </div>
            </dl>
            @if (count($features['feature_slugs']) > 0)
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Included features</dt>
                    <dd class="mt-2 flex flex-wrap gap-2">
                        @foreach ($features['feature_slugs'] as $slug)
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                {{ $slug }}
                            </span>
                        @endforeach
                    </dd>
                </div>
            @endif
        </div>

        @if ($plan->stripe_product_id || $plan->stripe_price_id_monthly || $plan->stripe_price_id_annual)
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-3">
                <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">Stripe sync</h4>
                <dl class="space-y-2 text-sm">
                    @if ($plan->stripe_product_id)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Product</dt>
                            <dd class="mt-1 font-mono text-gray-700 dark:text-gray-300">{{ $plan->stripe_product_id }}</dd>
                        </div>
                    @endif
                    @if ($plan->stripe_price_id_monthly)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Monthly price</dt>
                            <dd class="mt-1 font-mono text-gray-700 dark:text-gray-300">{{ $plan->stripe_price_id_monthly }}</dd>
                        </div>
                    @endif
                    @if ($plan->stripe_price_id_annual)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Annual price</dt>
                            <dd class="mt-1 font-mono text-gray-700 dark:text-gray-300">{{ $plan->stripe_price_id_annual }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif
    </div>
</div>
