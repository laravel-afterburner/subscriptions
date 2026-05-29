<div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
    <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">Plan entitlements</h4>
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Limits use null for unlimited. Feature slugs gate add-on packages via
        <code class="text-xs">SubscriptionEntitlementGate</code> in each package.
    </p>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-label for="max_users_per_team" value="Max users per entity" />
            <x-input id="max_users_per_team" type="number" min="1" class="mt-1 block w-full" wire:model="max_users_per_team" placeholder="Unlimited" />
            <x-input-error for="max_users_per_team" class="mt-2" />
        </div>
        <div>
            <x-label for="max_storage_gb" value="Max storage (GB)" />
            <x-input id="max_storage_gb" type="number" min="1" class="mt-1 block w-full" wire:model="max_storage_gb" placeholder="Unlimited" />
            <x-input-error for="max_storage_gb" class="mt-2" />
        </div>
    </div>

    @if (count($knownFeatureSlugs) > 0)
        <div>
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Included features</span>
            <div class="flex flex-wrap gap-3">
                @foreach ($knownFeatureSlugs as $slug)
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="feature_slugs" value="{{ $slug }}" class="rounded border-gray-300 dark:border-gray-700">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $slug }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error for="feature_slugs" class="mt-2" />
        </div>
    @endif
</div>
