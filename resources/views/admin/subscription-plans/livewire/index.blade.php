<div>
    <x-action-section>
        <x-slot name="title">Plans</x-slot>
        <x-slot name="description">
            Manage platform subscription plans.
        </x-slot>
        <x-slot name="content">
            <div class="mb-4 flex justify-end">
                <x-button type="button" wire:click="createPlan" no-spinner>
                    Create plan
                </x-button>
            </div>

            <div class="-mx-4 overflow-x-auto sm:-mx-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Monthly</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Annual</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Plan active</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Subscribers</th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @forelse ($plans as $plan)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 text-sm">
                                    <button
                                        type="button"
                                        wire:click="showPlan({{ $plan->id }})"
                                        class="font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 text-left"
                                    >
                                        {{ $plan->name }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $plan->formattedPrice(\Afterburner\Subscriptions\Enums\BillingInterval::Monthly) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $plan->formattedPrice(\Afterburner\Subscriptions\Enums\BillingInterval::Annual) }}</td>
                                <td class="px-6 py-4 text-sm">{{ $plan->is_active ? 'Yes' : 'No' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    @php
                                        $stats = $subscriberStats[$plan->id] ?? ['total' => 0, 'statuses' => []];
                                    @endphp
                                    @if ($stats['total'] === 0)
                                        <span class="text-gray-500 dark:text-gray-400">—</span>
                                    @else
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $stats['total'] }}</span>
                                        <div class="mt-1.5 flex flex-wrap gap-1">
                                            @foreach ($stats['statuses'] as $status)
                                                <span @class([
                                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                                    $status['badgeClasses'],
                                                ])>
                                                    {{ $status['label'] }} ({{ $status['count'] }})
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <div class="flex items-center justify-end space-x-2">
                                        <x-action-icon type="view" wire:click="showPlan({{ $plan->id }})" title="View plan" />
                                        <x-action-icon type="edit" wire:click="editPlan({{ $plan->id }})" title="Edit plan" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-sm text-gray-600 dark:text-gray-400">No plans yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $plans->links() }}
            </div>
        </x-slot>
    </x-action-section>
</div>
