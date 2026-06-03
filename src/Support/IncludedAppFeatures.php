<?php

namespace Afterburner\Subscriptions\Support;

use Illuminate\Database\Eloquent\Model;

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

    /**
     * Labels for the entity billing status panel (core app features, plus installed
     * add-on packages during a full-access generic trial).
     *
     * @return list<string>
     */
    public static function billingLabelsForTeam(Model $team): array
    {
        $labels = self::labels();

        if (SubscriptionEntitlementGate::teamOnFullAccessTrial($team)) {
            $labels = array_merge($labels, SubscriptionPackageFeatures::trialFeatureLabels());
        }

        return array_values(array_unique($labels));
    }

    public static function isConfigured(): bool
    {
        return count(self::labels()) > 0;
    }

    public static function isBillingSectionConfigured(Model $team): bool
    {
        return count(self::billingLabelsForTeam($team)) > 0;
    }
}
