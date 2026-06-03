@php
    $includedAppFeatures = \Afterburner\Subscriptions\Support\IncludedAppFeatures::labels();
@endphp

@if (count($includedAppFeatures) > 0)
    <div @class([
        'space-y-3',
        $wrapperClass ?? null,
    ])>
        <div>
            <h4 @class([
                'font-medium text-gray-900 dark:text-gray-100',
                $headingClass ?? 'text-base',
            ])>
                {{ $heading ?? 'Included with every subscription' }}
            </h4>
            @if ($description ?? true)
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $descriptionText ?? 'Core '.config('app.name', 'app').' features included automatically — not tied to add-on packages on the plan.' }}
                </p>
            @endif
        </div>

        <ul @class([
            'text-sm text-gray-600 dark:text-gray-400',
            ($listClass ?? null) === 'grid' ? 'grid gap-2 sm:grid-cols-2' : 'space-y-2',
        ])>
            @foreach ($includedAppFeatures as $label)
                <li class="flex items-center gap-2">
                    <svg class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ $label }}
                </li>
            @endforeach
        </ul>
    </div>
@endif
