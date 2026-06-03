<?php

namespace Afterburner\Subscriptions\Support;

class PlanFeatureSlug
{
    public static function label(string $slug): string
    {
        $labels = config('afterburner-subscriptions.feature_slug_labels', []);

        if (is_array($labels) && isset($labels[$slug]) && is_string($labels[$slug])) {
            $label = trim($labels[$slug]);

            if ($label !== '') {
                return $label;
            }
        }

        return ucfirst(str_replace('_', ' ', $slug));
    }
}
