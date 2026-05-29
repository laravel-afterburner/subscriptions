<div class="space-y-6">
    <a href="{{ route('admin.subscription-promotions.index') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        &larr; Back to promotion codes
    </a>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $promotion->name }}</h3>
                <p class="mt-1 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $promotion->code }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span @class([
                    'rounded-full px-3 py-1 text-xs font-medium',
                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' => $promotion->is_active,
                    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => ! $promotion->is_active,
                ])>
                    {{ $promotion->is_active ? 'Active' : 'Inactive' }}
                </span>

                @if ($canEdit)
                    <x-button type="button" wire:click="editPromotion" no-spinner>
                        Edit promotion
                    </x-button>
                @endif
            </div>
        </div>

        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Discount</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $promotion->formattedDiscount() }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Duration</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ ucfirst($promotion->duration->value) }}
                    @if ($promotion->duration === \Afterburner\Subscriptions\Enums\PromotionDuration::Repeating && $promotion->duration_in_months)
                        ({{ $promotion->duration_in_months }} months)
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Restricted to plan</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $promotion->subscriptionPlan?->name ?? 'All plans' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Max redemptions</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $promotion->max_redemptions ?? 'Unlimited' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Redeem by</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $promotion->redeem_by?->format('M j, Y') ?? 'No expiry' }}
                </dd>
            </div>
        </dl>

        @if ($promotion->stripe_coupon_id || $promotion->stripe_promotion_code_id)
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-3">
                <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">Stripe sync</h4>
                <dl class="space-y-2 text-sm">
                    @if ($promotion->stripe_coupon_id)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Coupon</dt>
                            <dd class="mt-1 font-mono text-gray-700 dark:text-gray-300">{{ $promotion->stripe_coupon_id }}</dd>
                        </div>
                    @endif
                    @if ($promotion->stripe_promotion_code_id)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Promotion code</dt>
                            <dd class="mt-1 font-mono text-gray-700 dark:text-gray-300">{{ $promotion->stripe_promotion_code_id }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif
    </div>
</div>
