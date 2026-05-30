<x-app-layout :title="\Afterburner\Subscriptions\Support\PageHeader::make('Subscription Plans', action: 'Edit promotion code', detail: $promotion->name)">
    <x-slot name="header">
        <x-afterburner-subscriptions::page-header section="Subscription Plans" action="Edit promotion code" :detail="$promotion->name" />
    </x-slot>

    <div class="max-w-3xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('admin.subscription-plans.promotion-codes.show', $promotion) }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                &larr; Back to promotion
            </a>
        </div>
        @livewire('subscriptions.admin.promotions.edit', ['promotion' => $promotion])
    </div>
</x-app-layout>
