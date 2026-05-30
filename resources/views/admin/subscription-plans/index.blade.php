<x-app-layout :title="\Afterburner\Subscriptions\Support\PageHeader::make('Subscription Plans')">
    <x-slot name="header">
        <x-afterburner-subscriptions::page-header section="Subscription Plans" />
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-10 py-10 sm:px-6 lg:px-8">
        @livewire('subscriptions.admin.plans.index')

        @if (config('afterburner-subscriptions.promotions_enabled', true))
            @livewire('subscriptions.admin.promotions.index')
        @endif
    </div>
</x-app-layout>
