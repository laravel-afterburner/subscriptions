<x-app-layout :title="\App\Support\PageHeader::make('Subscription Plans', detail: $promotion->name)">
    <x-slot name="header">
        <x-afterburner-subscriptions::page-header section="Subscription Plans" :detail="$promotion->name" />
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 py-6 sm:py-10 sm:px-6 lg:px-8">
        @livewire('subscriptions.admin.promotions.show', ['promotion' => $promotion])
    </div>
</x-app-layout>
