<div id="promotion-codes">
    <x-action-section>
        <x-slot name="title">Promotion codes</x-slot>
        <x-slot name="description">
            Manage platform promotion codes.
        </x-slot>
        <x-slot name="content">
            <div class="mb-4 flex justify-end">
                <x-button type="button" wire:click="createPromotion" no-spinner>
                    New promotion code
                </x-button>
            </div>

            <div class="-mx-4 overflow-x-auto sm:-mx-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Code</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Discount</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Plan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @forelse ($promotions as $promotion)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 text-sm">
                                    <button
                                        type="button"
                                        wire:click="showPromotion({{ $promotion->id }})"
                                        class="font-mono font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 text-left"
                                    >
                                        {{ $promotion->code }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $promotion->formattedDiscount() }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $promotion->subscriptionPlan?->name ?? 'All plans' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($promotion->is_active)
                                        <span class="text-green-700 dark:text-green-300">Active</span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">Inactive</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <div class="flex items-center justify-end space-x-2">
                                        <x-action-icon type="view" wire:click="showPromotion({{ $promotion->id }})" title="View promotion" />
                                        <x-action-icon type="edit" wire:click="editPromotion({{ $promotion->id }})" title="Edit promotion" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-6 text-sm text-gray-600 dark:text-gray-400">No promotion codes yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $promotions->links() }}
            </div>
        </x-slot>
    </x-action-section>
</div>
