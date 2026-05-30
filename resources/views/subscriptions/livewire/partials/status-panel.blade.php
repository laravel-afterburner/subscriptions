@php
    $plan = $summary->plan();
    $entityLabel = config('afterburner.entity_label', 'team');
    $limits = array_filter([
        $entityLabel.' members' => $entitlements->limit('max_users_per_team'),
        'storage (GB)' => $entitlements->limit('max_storage_gb'),
    ]);
    $featureSlugs = $entitlements->get('features', []);
    $featureSlugs = is_array($featureSlugs) ? $featureSlugs : [];
    $highlightStats = $summary->highlightStats();
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 border-b border-gray-200 pb-6 dark:border-gray-700 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 flex-1">
            @if ($plan)
                <h4 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $plan->name }}</h4>
                @if ($plan->description)
                    <p class="mt-1 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $plan->description }}</p>
                @endif
            @else
                <h4 class="text-xl font-semibold text-gray-900 dark:text-gray-100">No active plan</h4>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Choose a plan below to start or restore your {{ config('afterburner.entity_label', 'team') }}'s subscription.
                </p>
            @endif
        </div>
        <span @class([
            'inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold',
            $summary->statusBadgeClasses(),
        ])>
            {{ $summary->statusLabel() }}
        </span>
    </div>

    @if (count($highlightStats) > 0)
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($highlightStats as $stat)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                    <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100">{{ $stat['value'] }}</p>
                    @if ($stat['hint'])
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $stat['hint'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Payment method</p>
                    @if ($summary->hasPaymentMethod())
                        <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">{{ $summary->paymentMethodLabel() }}</p>
                    @elseif ($summary->hasStripeCustomer())
                        <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">No card on file — add one in the billing portal.</p>
                    @else
                        <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">Added when you subscribe to a plan.</p>
                    @endif
                </div>
            </div>
            @if ($canManage && $summary->hasStripeCustomer())
                <x-secondary-button type="button" wire:click="openBillingPortal" class="shrink-0">
                    Manage billing
                </x-secondary-button>
            @endif
        </div>
    </div>

    @if (count($limits) > 0 || count($featureSlugs) > 0)
        <div>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Included with your plan</p>
            <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach ($limits as $label => $value)
                    <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="h-4 w-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Up to {{ $value }} {{ $label }}
                    </li>
                @endforeach
                @foreach ($featureSlugs as $slug)
                    <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="h-4 w-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ ucfirst(str_replace('_', ' ', $slug)) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
