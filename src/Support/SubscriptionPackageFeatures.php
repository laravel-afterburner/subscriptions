<?php

namespace Afterburner\Subscriptions\Support;

use Composer\InstalledVersions;

class SubscriptionPackageFeatures
{
    /**
     * @var array<string, array{label: string, features: list<string>}>
     */
    protected static array $registered = [];

    /**
     * @param  list<string>  $features
     */
    public static function register(string $slug, ?string $label = null, array $features = []): void
    {
        $features = collect($features)
            ->map(fn ($feature) => is_string($feature) ? trim($feature) : '')
            ->filter(fn (string $feature) => $feature !== '')
            ->values()
            ->all();

        static::$registered[$slug] = [
            'label' => $label !== null && trim($label) !== ''
                ? trim($label)
                : PlanFeatureSlug::label($slug),
            'features' => $features,
        ];
    }

    public static function isInstalled(string $slug): bool
    {
        if (isset(static::$registered[$slug])) {
            return true;
        }

        $composerNames = config('afterburner-subscriptions.package_composer_names', []);

        if (is_array($composerNames) && isset($composerNames[$slug]) && is_string($composerNames[$slug])) {
            if (class_exists(InstalledVersions::class)) {
                return InstalledVersions::isInstalled($composerNames[$slug]);
            }
        }

        return false;
    }

    /**
     * @return list<array{slug: string, label: string, features: list<string>}>
     */
    public static function installedPackages(): array
    {
        $slugs = config('afterburner-subscriptions.known_feature_slugs', []);

        if (! is_array($slugs)) {
            return [];
        }

        $packages = [];

        foreach ($slugs as $slug) {
            if (! is_string($slug) || $slug === '' || ! static::isInstalled($slug)) {
                continue;
            }

            $packages[] = array_merge(['slug' => $slug], static::definition($slug));
        }

        return $packages;
    }

    /**
     * @return list<string>
     */
    public static function trialFeatureLabels(): array
    {
        $labels = [];

        foreach (static::installedPackages() as $package) {
            $features = $package['features'];

            if ($features === []) {
                $labels[] = $package['label'];

                continue;
            }

            foreach ($features as $feature) {
                $labels[] = $feature;
            }
        }

        return array_values(array_unique($labels));
    }

    /**
     * @return array{label: string, features: list<string>}
     */
    protected static function definition(string $slug): array
    {
        if (isset(static::$registered[$slug])) {
            return static::$registered[$slug];
        }

        $configured = config('afterburner-subscriptions.package_features', []);
        $features = is_array($configured) && isset($configured[$slug]) && is_array($configured[$slug])
            ? $configured[$slug]
            : [];

        $features = collect($features)
            ->map(fn ($feature) => is_string($feature) ? trim($feature) : '')
            ->filter(fn (string $feature) => $feature !== '')
            ->values()
            ->all();

        $label = PlanFeatureSlug::label($slug);

        if ($features === []) {
            return [
                'label' => $label,
                'features' => [$label],
            ];
        }

        return [
            'label' => $label,
            'features' => $features,
        ];
    }

    public static function clearRegistered(): void
    {
        static::$registered = [];
    }
}
