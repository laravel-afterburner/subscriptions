<form wire:submit="save" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Fields marked with <span class="text-red-600">*</span> are required for Stripe product and price sync.
    </p>

    <div class="max-w-xl">
        <x-label for="name" value="Name *" />
        <x-input id="name" type="text" class="mt-1 block w-full" wire:model.live="name" required maxlength="250" />
        <x-input-error for="name" class="mt-2" />
    </div>

    <div class="max-w-xs">
        <x-label for="slug" value="Slug *" />
        <x-input id="slug" type="text" class="mt-1 block w-full font-mono" wire:model="slug" required maxlength="255" />
        <x-input-error for="slug" class="mt-2" />
    </div>

    <div>
        <x-label for="description" value="Description *" />
        <x-textarea-input id="description" wire:model="description" required maxlength="5000" rows="4" class="mt-1 block w-full max-w-2xl" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Shown to customers in Stripe Checkout. Cannot be blank.</p>
        <x-input-error for="description" class="mt-2" />
    </div>

    <div class="flex flex-wrap items-end gap-4">
        <div class="w-28">
            <x-label for="currency" value="Currency *" />
            <x-select-input id="currency" wire:model="currency" required class="mt-1 block w-full">
                @foreach ($supportedCurrencies as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </x-select-input>
            <x-input-error for="currency" class="mt-2" />
        </div>
        <div class="w-40">
            <x-label for="monthly_price" value="Monthly price *" />
            <x-money-input id="monthly_price" wire:model="monthly_price" min="{{ \Afterburner\Subscriptions\Support\PlanPriceInput::minimumPriceDollars() }}" class="mt-1 w-full" required />
            <x-input-error for="monthly_price" class="mt-2" />
        </div>
        <div class="w-40">
            <x-label for="annual_price" value="Annual price *" />
            <x-money-input id="annual_price" wire:model="annual_price" min="{{ \Afterburner\Subscriptions\Support\PlanPriceInput::minimumPriceDollars() }}" class="mt-1 w-full" required />
            <x-input-error for="annual_price" class="mt-2" />
        </div>
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400">Prices are in the selected currency. Canadian dollars (CAD) is the default.</p>

    <div class="flex flex-wrap gap-4">
        <div class="w-28">
            <x-label for="trial_days" value="Trial days *" />
            <x-input id="trial_days" type="number" min="0" max="999" class="mt-1 block w-full" wire:model="trial_days" required />
            <x-input-error for="trial_days" class="mt-2" />
        </div>
        <div class="w-28">
            <x-label for="sort_order" value="Sort order" />
            <x-input id="sort_order" type="number" min="0" class="mt-1 block w-full" wire:model="sort_order" />
            <x-input-error for="sort_order" class="mt-2" />
        </div>
    </div>

    <label class="flex items-center gap-2">
        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900">
        <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
    </label>

    @include('afterburner-subscriptions::admin.subscription-plans.partials.entitlements', ['knownFeatureSlugs' => $knownFeatureSlugs])

    <div class="flex items-center justify-end gap-3">
        <x-action-message on="saved" />
        <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
            Save plan
        </x-button>
    </div>
</form>
