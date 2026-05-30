<x-app-layout :title="\Afterburner\Subscriptions\Support\PageHeader::make('Subscription Plans', action: 'Create promotion code')">
    <x-slot name="header">
        <x-afterburner-subscriptions::page-header section="Subscription Plans" action="Create promotion code" />
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @livewire('subscriptions.admin.promotions.create')
        </div>
    </div>
</x-app-layout>
