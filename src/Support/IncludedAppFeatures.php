<?php

namespace Afterburner\Subscriptions\Support;

class IncludedAppFeatures
{
    /**
     * Human-readable labels for features the host app includes with every active subscription.
     *
     * @return list<string>
     */
    public static function labels(): array
    {
        $configured = config('afterburner-subscriptions.included_app_features', []);

        if (! is_array($configured)) {
            return [];
        }

        return collect($configured)
            ->map(fn ($label) => is_string($label) ? trim($label) : '')
            ->filter(fn (string $label) => $label !== '')
            ->values()
            ->all();
    }

    public static function isConfigured(): bool
    {
        return count(self::labels()) > 0;
    }
}
