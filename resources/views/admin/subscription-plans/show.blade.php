<x-app-layout :title="$plan->name">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $plan->name }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-10 sm:px-6 lg:px-8">
        @livewire('subscriptions.admin.plans.show', ['plan' => $plan])
    </div>
</x-app-layout>
