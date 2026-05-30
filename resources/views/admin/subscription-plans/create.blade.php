<x-app-layout :title="\Afterburner\Subscriptions\Support\PageHeader::make('Subscription Plans', action: 'Create plan')">
    <x-slot name="header">
        <x-afterburner-subscriptions::page-header section="Subscription Plans" action="Create plan" />
    </x-slot>

    <div class="max-w-3xl mx-auto py-10 sm:px-6 lg:px-8">
        @livewire('subscriptions.admin.plans.create')
    </div>
</x-app-layout>
