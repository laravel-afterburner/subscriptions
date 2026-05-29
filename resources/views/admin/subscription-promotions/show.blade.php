<x-app-layout :title="$promotion->name">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $promotion->name }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-10 sm:px-6 lg:px-8">
        @livewire('subscriptions.admin.promotions.show', ['promotion' => $promotion])
    </div>
</x-app-layout>
