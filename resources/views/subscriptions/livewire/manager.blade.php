<div class="space-y-8">
    <x-action-section>
        <x-slot name="title">Subscription status</x-slot>
        <x-slot name="description">
            Your {{ config('afterburner.entity_label', 'team') }}'s current plan, trial period, and entitlements. Billing managers can update the payment method in Stripe.
        </x-slot>
        <x-slot name="content">
            @include('afterburner-subscriptions::subscriptions.livewire.partials.status-panel')
        </x-slot>
    </x-action-section>

    <x-section-border />

    <x-action-section>
        <x-slot name="title">Available plans</x-slot>
        <x-slot name="description">
            Choose a billing interval to subscribe or change your {{ config('afterburner.entity_label', 'team') }}'s plan.
            @if ($canManage && $promotionsEnabled && $hasActivePromotionCodes)
                Add an optional promotion code before checkout.
            @endif
        </x-slot>
        <x-slot name="content">
            @if (! $isActive)
                <div class="mb-6 flex gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/50 dark:bg-amber-900/20">
                    <svg class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-sm text-amber-900 dark:text-amber-200">
                        Your subscription is inactive. Select a plan below to restore access.
                    </p>
                </div>
            @endif

            @if ($canManage && $promotionsEnabled && $hasActivePromotionCodes)
                <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <label for="promotionCode" class="block text-sm font-medium text-gray-900 dark:text-gray-100">
                        Promotion code
                    </label>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Applied at checkout when you subscribe to any plan.
                    </p>
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start">
                        <input
                            id="promotionCode"
                            type="text"
                            wire:model="promotionCode"
                            placeholder="e.g. LAUNCH20"
                            autocomplete="off"
                            class="block w-full rounded-lg border-gray-300 font-mono text-sm uppercase tracking-wide shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:max-w-xs"
                        />
                        @if ($promotionCode !== '')
                            <button
                                type="button"
                                wire:click="$set('promotionCode', '')"
                                class="shrink-0 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            >
                                Clear
                            </button>
                        @endif
                    </div>
                    <x-input-error for="promotionCode" class="mt-2" />
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                @forelse ($plans as $plan)
                    @php
                        $isCurrentPlan = $team->subscription_plan_id === $plan->id;
                        $savingsPercent = $plan->annualSavingsPercent();
                        $symbol = $plan->currencySymbol();
                        $monthlyDisplay = number_format($plan->monthly_price_cents / 100, 2);
                        $annualDisplay = number_format($plan->annual_price_cents / 100, 2);
                        $planFeatures = is_array($plan->features) ? $plan->features : [];
                        $featureSlugs = is_array($planFeatures['features'] ?? null) ? $planFeatures['features'] : [];
                        $maxUsers = $planFeatures['max_users_per_team'] ?? null;
                        $maxStorage = $planFeatures['max_storage_gb'] ?? null;
                    @endphp
                    <div @class([
                        'relative flex flex-col rounded-xl border bg-white p-6 shadow-sm transition-shadow dark:bg-gray-800',
                        'border-indigo-500 ring-2 ring-indigo-500/20 dark:border-indigo-400' => $isCurrentPlan,
                        'border-gray-200 hover:shadow-md dark:border-gray-700' => ! $isCurrentPlan,
                    ])>
                        @if ($isCurrentPlan)
                            <span class="absolute -top-3 left-4 inline-flex rounded-full bg-indigo-600 px-3 py-0.5 text-xs font-semibold text-white dark:bg-indigo-500">
                                Current plan
                            </span>
                        @endif

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $plan->name }}</h4>
                                @if ($plan->description)
                                    <p class="mt-1 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $plan->description }}</p>
                                @endif
                            </div>
                            @if ($savingsPercent)
                                <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    Save {{ $savingsPercent }}% yearly
                                </span>
                            @endif
                        </div>

                        <div class="mt-6 border-t border-gray-100 pt-6 dark:border-gray-700">
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">{{ $symbol }}{{ $monthlyDisplay }}</span>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">/ month</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                or {{ $symbol }}{{ $annualDisplay }} billed annually
                            </p>
                        </div>

                        @if ($maxUsers || $maxStorage || count($featureSlugs) > 0)
                            <ul class="mt-5 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                @if ($maxUsers)
                                    <li class="flex items-center gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Up to {{ $maxUsers }} {{ config('afterburner.entity_label', 'team') }} members
                                    </li>
                                @endif
                                @if ($maxStorage)
                                    <li class="flex items-center gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ $maxStorage }} GB storage
                                    </li>
                                @endif
                                @foreach ($featureSlugs as $slug)
                                    <li class="flex items-center gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ ucfirst(str_replace('_', ' ', $slug)) }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($canManage)
                            <div class="mt-6 flex flex-col gap-2 sm:mt-auto sm:pt-6">
                                <x-button type="button" wire:click="subscribe({{ $plan->id }}, 'month')" wire:loading.attr="disabled" wire:loading.class="cursor-wait" wire:target="subscribe" class="w-full justify-center disabled:cursor-wait">
                                    Subscribe monthly
                                </x-button>
                                <x-secondary-button type="button" wire:click="subscribe({{ $plan->id }}, 'year')" wire:loading.attr="disabled" wire:loading.class="cursor-wait" wire:target="subscribe" class="w-full justify-center disabled:cursor-wait">
                                    @include('afterburner-subscriptions::partials.loading-spinner', ['target' => 'subscribe'])
                                    Subscribe annually
                                </x-secondary-button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-gray-300 p-10 text-center dark:border-gray-600">
                        <p class="text-sm text-gray-600 dark:text-gray-400">No subscription plans are available yet.</p>
                    </div>
                @endforelse
            </div>
        </x-slot>
    </x-action-section>

    <x-section-border />

    <x-action-section>
        <x-slot name="title">Invoices</x-slot>
        <x-slot name="description">
            Download receipts for past subscription payments.
        </x-slot>
        <x-slot name="content">
            <div class="mb-6">
                <label for="invoiceSearchQuery" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Search
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchQuery"
                    id="invoiceSearchQuery"
                    placeholder="Search invoices..."
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                />
            </div>

            <div class="-mx-4 overflow-x-auto sm:-mx-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Invoice #
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Amount
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Status
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @forelse ($invoices as $invoice)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $invoice->date()->timezone($team->timezone ?? config('app.timezone'))->format('M j, Y') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $invoice->number ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $invoice->total() }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @if ($invoice->isPaid())
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            Paid
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            Unpaid
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    @if ($invoice->invoice_pdf)
                                        <div class="flex items-center justify-end space-x-2">
                                            <a
                                                href="{{ $invoice->invoice_pdf }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="rounded p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400"
                                                title="Download invoice"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    @if ($searchQuery)
                                        No invoices found matching your search.
                                    @else
                                        No invoices yet.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>
    </x-action-section>
</div>
