<form wire:submit="save" class="space-y-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
    <div>
        <x-label for="code" value="Code *" />
        <x-input id="code" type="text" class="mt-1 block w-full font-mono uppercase" wire:model.live="code" required maxlength="40" />
        <x-input-error for="code" class="mt-2" />
    </div>

    <div>
        <x-label for="name" value="Name *" />
        <x-input id="name" type="text" class="mt-1 block w-full" wire:model="name" required maxlength="250" />
        <x-input-error for="name" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-label for="percent_off" value="Percent off" />
            <x-input id="percent_off" type="number" min="1" max="100" class="mt-1 block w-full" wire:model="percent_off" />
            <x-input-error for="percent_off" class="mt-2" />
        </div>
        <div>
            <x-label for="amount_off" value="Amount off" />
            <x-input id="amount_off" type="number" step="0.01" min="{{ \Afterburner\Subscriptions\Support\PlanPriceInput::minimumPriceDollars() }}" class="mt-1 block w-full" wire:model="amount_off" placeholder="e.g. 10.00" />
            <x-input-error for="amount_off" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-label for="duration" value="Duration *" />
            <select id="duration" wire:model.live="duration" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm">
                @foreach ($durations as $durationOption)
                    <option value="{{ $durationOption->value }}">{{ ucfirst($durationOption->value) }}</option>
                @endforeach
            </select>
            <x-input-error for="duration" class="mt-2" />
        </div>
        @if ($duration === 'repeating')
            <div>
                <x-label for="duration_in_months" value="Duration in months *" />
                <x-input id="duration_in_months" type="number" min="1" max="36" class="mt-1 block w-full" wire:model="duration_in_months" />
                <x-input-error for="duration_in_months" class="mt-2" />
            </div>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-label for="max_redemptions" value="Max redemptions" />
            <x-input id="max_redemptions" type="number" min="1" class="mt-1 block w-full" wire:model="max_redemptions" placeholder="Unlimited" />
            <x-input-error for="max_redemptions" class="mt-2" />
        </div>
        <div>
            <x-label for="redeem_by" value="Redeem by" />
            <x-input id="redeem_by" type="date" class="mt-1 block w-full" wire:model="redeem_by" />
            <x-input-error for="redeem_by" class="mt-2" />
        </div>
    </div>

    <div>
        <x-label for="subscription_plan_id" value="Restrict to plan" />
        <select id="subscription_plan_id" wire:model="subscription_plan_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm">
            <option value="">All plans</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
            @endforeach
        </select>
        <x-input-error for="subscription_plan_id" class="mt-2" />
    </div>

    <label class="flex items-center gap-2">
        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 dark:border-gray-700">
        <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
    </label>

    <div class="flex justify-end">
        <x-button type="submit" wire:loading.attr="disabled" wire:loading.class="cursor-wait" wire:target="save" no-spinner class="disabled:cursor-wait">
            @include('afterburner-subscriptions::partials.loading-spinner', ['target' => 'save'])
            {{ $submitLabel }}
        </x-button>
    </div>
</form>
