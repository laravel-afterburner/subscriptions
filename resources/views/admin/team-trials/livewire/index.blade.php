<div id="team-trials">
    <x-action-section>
        <x-slot name="title">{{ entity_plural_title() }} trials</x-slot>
        <x-slot name="description">
            Grant or extend full app access for a {{ entity_label() }} without a paid subscription by setting <code class="text-xs">trial_ends_at</code>.
        </x-slot>
        <x-slot name="content">
            @if (! $supportsTrials)
                <p class="text-sm text-amber-700 dark:text-amber-300">
                    The billable team model does not use subscription trials. Add <code class="text-xs">HasSubscriptions</code> to enable trials.
                </p>
            @else
                <div class="space-y-8">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-900/40">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Find a {{ entity_label() }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Search by name or ID (minimum 2 characters).</p>

                        <div class="relative mt-4 max-w-xl">
                            <x-label for="teamSearch" value="Search" />
                            <x-input
                                id="teamSearch"
                                type="search"
                                class="mt-1 block w-full"
                                wire:model.live.debounce.300ms="teamSearch"
                                placeholder="{{ entity_title() }} name or ID"
                                autocomplete="off"
                            />
                            @if ($teamSearch !== '' && count($searchResults) > 0)
                                <ul class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800">
                                    @foreach ($searchResults as $result)
                                        <li>
                                            <button
                                                type="button"
                                                wire:click="selectTeam({{ $result->getKey() }})"
                                                class="block w-full px-4 py-2 text-left text-sm text-gray-900 hover:bg-gray-100 dark:text-gray-100 dark:hover:bg-gray-700"
                                            >
                                                <span class="font-medium">{{ $result->name }}</span>
                                                <span class="text-gray-500 dark:text-gray-400">#{{ $result->getKey() }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif ($teamSearch !== '' && strlen($teamSearch) >= 2)
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No {{ entity_plural() }} found.</p>
                            @endif
                        </div>

                        @if ($selectedTeam)
                            <div class="mt-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $selectedTeam->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ entity_title() }} #{{ $selectedTeam->getKey() }}</p>
                                    </div>
                                    @if ($statusLabel)
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            $summary->statusBadgeClasses(),
                                        ])>
                                            {{ $statusLabel }}
                                        </span>
                                    @endif
                                </div>

                                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Trial ends</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-100">
                                            @if ($selectedTeam->trial_ends_at)
                                                {!! \Afterburner\Subscriptions\Support\TeamTrialDisplay::formatTrialEndsAt($selectedTeam->trial_ends_at, $selectedTeam) !!}
                                            @else
                                                —
                                            @endif
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Days remaining</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $summary?->trialDaysRemaining() ?? '—' }}
                                        </dd>
                                    </div>
                                </dl>

                                @if ($hasPaidSubscription)
                                    <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">
                                        This {{ entity_label() }} has an active Stripe subscription. Generic trial may be cleared when Stripe syncs; use Stripe for comping paying customers.
                                    </p>
                                @endif
                            </div>

                            <form wire:submit="applyTrial" class="mt-6 space-y-6">
                                <fieldset class="space-y-4">
                                    <legend class="text-sm font-medium text-gray-900 dark:text-gray-100">Trial duration</legend>

                                    <div class="flex flex-wrap gap-4">
                                        @foreach ($dayPresets as $days)
                                            <label @class([
                                                'inline-flex items-center gap-2 text-sm',
                                                'opacity-50' => $useCustomDate,
                                            ])>
                                                <input
                                                    type="radio"
                                                    wire:model.live="presetDays"
                                                    value="{{ $days }}"
                                                    @disabled($useCustomDate)
                                                    class="border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800"
                                                />
                                                {{ $days }} days
                                            </label>
                                        @endforeach
                                    </div>
                                    <x-input-error for="presetDays" class="mt-1" />

                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" wire:model.live="useCustomDate" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800" />
                                        Use custom end date
                                    </label>

                                    @if ($useCustomDate)
                                        <div class="max-w-xs">
                                            <x-label for="customEndsAt" value="End date" />
                                            <x-input id="customEndsAt" type="date" class="mt-1 block w-full" wire:model="customEndsAt" min="{{ $minCustomTrialDate }}" />
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Trial ends at end of day in the {{ entity_label() }}'s timezone ({{ \Afterburner\Subscriptions\Support\TeamTrialDisplay::teamTimezone($selectedTeam) }}).
                                            </p>
                                            <x-input-error for="customEndsAt" class="mt-2" />
                                        </div>
                                    @else
                                        <fieldset class="space-y-2">
                                            <legend class="text-sm text-gray-600 dark:text-gray-400">Extend from</legend>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input
                                                    type="radio"
                                                    wire:model="extendMode"
                                                    value="{{ \Afterburner\Subscriptions\Support\TeamTrialSchedule::EXTEND_FROM_TODAY }}"
                                                    class="border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800"
                                                />
                                                Today (replaces current end date)
                                            </label>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input
                                                    type="radio"
                                                    wire:model="extendMode"
                                                    value="{{ \Afterburner\Subscriptions\Support\TeamTrialSchedule::EXTEND_FROM_CURRENT_END }}"
                                                    class="border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800"
                                                />
                                                Current trial end (add days if still active)
                                            </label>
                                        </fieldset>
                                    @endif
                                </fieldset>

                                <div class="flex flex-wrap items-center gap-3">
                                    <x-button type="submit" wire:loading.attr="disabled" wire:target="applyTrial">
                                        Apply trial
                                    </x-button>

                                    @if ($selectedTeam->trial_ends_at)
                                        <x-danger-button
                                            type="button"
                                            wire:click="clearTrial"
                                            wire:confirm="End this {{ entity_label() }}'s trial now? They may lose app access if they have no paid subscription."
                                            wire:loading.attr="disabled"
                                            wire:target="clearTrial"
                                        >
                                            End trial now
                                        </x-danger-button>
                                    @endif
                                </div>
                            </form>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Active trials</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ entity_plural_title() }} with a trial end date in the future.</p>

                        <div class="-mx-4 mt-4 overflow-x-auto sm:-mx-6">
                            <table class="data-table table-team-trials min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ entity_title() }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Trial ends</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Days left</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Paid sub</th>
                                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    @forelse ($activeTrials as $trialTeam)
                                        @php
                                            $rowSummary = \Afterburner\Subscriptions\Support\SubscriptionSummary::forTeam($trialTeam);
                                            $rowStatus = \Afterburner\Subscriptions\Support\SubscriptionStatus::forTeam($trialTeam)->statusLabel();
                                            $rowPaid = method_exists($trialTeam, 'subscribed') && $trialTeam->subscribed();
                                        @endphp
                                        <tr @class([
                                            'hover:bg-gray-50 dark:hover:bg-gray-700',
                                            'bg-indigo-50/50 dark:bg-indigo-900/20' => $selectedTeam && $selectedTeam->is($trialTeam),
                                        ])>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $trialTeam->name }}
                                                <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">#{{ $trialTeam->getKey() }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                                {!! \Afterburner\Subscriptions\Support\TeamTrialDisplay::formatTrialEndsAt($trialTeam->trial_ends_at, $trialTeam) !!}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $rowSummary->trialDaysRemaining() ?? '—' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <span @class([
                                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                                    $rowSummary->statusBadgeClasses(),
                                                ])>
                                                    {{ $rowStatus }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $rowPaid ? 'Yes' : 'No' }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                                <x-secondary-button type="button" wire:click="editTeam({{ $trialTeam->getKey() }})" class="text-xs">
                                                    Edit
                                                </x-secondary-button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-6 text-sm text-gray-600 dark:text-gray-400">No active trials.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $activeTrials->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </x-slot>
    </x-action-section>
</div>
