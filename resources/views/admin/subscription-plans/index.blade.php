<x-app-layout title="Subscription Plans">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Subscription Plans
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        @livewire('subscriptions.admin.plans.index')
    </div>
</x-app-layout>
