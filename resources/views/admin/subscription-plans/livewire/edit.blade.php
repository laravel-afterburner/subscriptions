<form wire:submit="save" class="space-y-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Fields marked with <span class="text-red-600">*</span> are required for Stripe product and price sync.
    </p>

    <div>
        <x-label for="name" value="Name *" />
        <x-input id="name" type="text" class="mt-1 block w-full" wire:model="name" required maxlength="250" />
        <x-input-error for="name" class="mt-2" />
    </div>

    <div>
        <x-label for="slug" value="Slug *" />
        <x-input id="slug" type="text" class="mt-1 block w-full" wire:model="slug" required maxlength="255" />
        <x-input-error for="slug" class="mt-2" />
    </div>

    <div>
        <x-label for="description" value="Description *" />
        <textarea id="description" wire:model="description" required maxlength="5000" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm"></textarea>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Shown to customers in Stripe Checkout. Cannot be blank.</p>
        <x-input-error for="description" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-label for="currency" value="Currency *" />
            <select id="currency" wire:model="currency" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm">
                @foreach ($supportedCurrencies as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Changing currency creates new Stripe prices on save.</p>
            <x-input-error for="currency" class="mt-2" />
        </div>
        <div>
            <x-label for="monthly_price" value="Monthly price *" />
            <x-input id="monthly_price" type="number" step="0.01" min="{{ \Afterburner\Subscriptions\Support\PlanPriceInput::minimumPriceDollars() }}" class="mt-1 block w-full" wire:model="monthly_price" required />
            <x-input-error for="monthly_price" class="mt-2" />
        </div>
        <div>
            <x-label for="annual_price" value="Annual price *" />
            <x-input id="annual_price" type="number" step="0.01" min="{{ \Afterburner\Subscriptions\Support\PlanPriceInput::minimumPriceDollars() }}" class="mt-1 block w-full" wire:model="annual_price" required />
            <x-input-error for="annual_price" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-label for="trial_days" value="Trial days *" />
            <x-input id="trial_days" type="number" min="0" class="mt-1 block w-full" wire:model="trial_days" required />
            <x-input-error for="trial_days" class="mt-2" />
        </div>
        <div>
            <x-label for="sort_order" value="Sort order" />
            <x-input id="sort_order" type="number" min="0" class="mt-1 block w-full" wire:model="sort_order" />
            <x-input-error for="sort_order" class="mt-2" />
        </div>
    </div>

    <label class="flex items-center gap-2">
        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 dark:border-gray-700">
        <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
    </label>

    @include('afterburner-subscriptions::admin.subscription-plans.partials.entitlements', ['knownFeatureSlugs' => $knownFeatureSlugs])

    <div class="flex justify-end">
        <x-button type="submit" wire:loading.attr="disabled" wire:loading.class="cursor-wait" wire:target="save" no-spinner class="disabled:cursor-wait">
            @include('afterburner-subscriptions::partials.loading-spinner', ['target' => 'save'])
            Save plan
        </x-button>
    </div>
</form>
