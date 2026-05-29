<x-app-layout title="Create Subscription Plan">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Create Subscription Plan
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-10 sm:px-6 lg:px-8">
        @livewire('subscriptions.admin.plans.create')
    </div>
</x-app-layout>
