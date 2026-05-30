<x-app-layout :title="\Afterburner\Subscriptions\Support\PageHeader::make('Subscription Plans', detail: $plan->name)">
    <x-slot name="header">
        <x-afterburner-subscriptions::page-header section="Subscription Plans" :detail="$plan->name" />
    </x-slot>

    <div class="max-w-3xl mx-auto py-10 sm:px-6 lg:px-8">
        @livewire('subscriptions.admin.plans.show', ['plan' => $plan])
    </div>
</x-app-layout>
