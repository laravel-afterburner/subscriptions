<?php

namespace Afterburner\Subscriptions\Support;

class PlanFeaturesInput
{
    /**
     * @return array<string, mixed>
     */
    public static function fromForm(?int $maxUsersPerTeam, ?int $maxStorageGb, array $featureSlugs): array
    {
        $template = config('afterburner-subscriptions.plan_features_template', []);

        return array_replace_recursive($template, array_filter([
            'max_users_per_team' => $maxUsersPerTeam,
            'max_storage_gb' => $maxStorageGb,
            'features' => array_values(array_unique(array_filter($featureSlugs))),
        ], fn ($value) => $value !== null && $value !== []));
    }

    /**
     * @param  array<string, mixed>|null  $features
     * @return array{max_users_per_team: ?int, max_storage_gb: ?int, feature_slugs: array<int, string>}
     */
    public static function toForm(?array $features): array
    {
        $features ??= [];

        return [
            'max_users_per_team' => isset($features['max_users_per_team']) ? (int) $features['max_users_per_team'] : null,
            'max_storage_gb' => isset($features['max_storage_gb']) ? (int) $features['max_storage_gb'] : null,
            'feature_slugs' => is_array($features['features'] ?? null) ? $features['features'] : [],
        ];
    }
}
