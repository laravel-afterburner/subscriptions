<x-app-layout :title="\Afterburner\Subscriptions\Support\PageHeader::make('Subscriptions')">
    <x-slot name="header">
        <x-afterburner-subscriptions::page-header section="Subscriptions" />
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @livewire('subscriptions.manager', ['team' => $team])
        </div>
    </div>
</x-app-layout>
