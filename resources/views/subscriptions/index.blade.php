<x-app-layout title="Subscriptions">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Subscriptions
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @livewire('subscriptions.manager', ['team' => $team])
        </div>
    </div>
</x-app-layout>
