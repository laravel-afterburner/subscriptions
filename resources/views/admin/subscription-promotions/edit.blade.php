<x-app-layout title="Edit Promotion Code">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Edit Promotion Code
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('admin.subscription-promotions.show', $promotion) }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                &larr; Back to promotion
            </a>
        </div>
        @livewire('subscriptions.admin.promotions.edit', ['promotion' => $promotion])
    </div>
</x-app-layout>
