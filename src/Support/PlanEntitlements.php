<?php

namespace Afterburner\Subscriptions\Support;

use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;

class PlanEntitlements
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(protected array $data) {}

    public static function forTeam(Model $team): self
    {
        $template = config('afterburner-subscriptions.plan_features_template', []);
        $planFeatures = $team->subscriptionPlan instanceof SubscriptionPlan
            ? ($team->subscriptionPlan->features ?? [])
            : [];

        if (! is_array($planFeatures)) {
            $planFeatures = [];
        }

        return new self(array_replace_recursive($template, $planFeatures));
    }

    public static function forPlan(?SubscriptionPlan $plan): self
    {
        $template = config('afterburner-subscriptions.plan_features_template', []);
        $planFeatures = $plan?->features ?? [];

        if (! is_array($planFeatures)) {
            $planFeatures = [];
        }

        return new self(array_replace_recursive($template, $planFeatures));
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function limit(string $key): ?int
    {
        $value = $this->get($key);

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    public function hasFeature(string $slug): bool
    {
        $features = $this->get('features', []);

        if (! is_array($features)) {
            return false;
        }

        return in_array($slug, $features, true);
    }

    public function withinLimit(string $key, int $current): bool
    {
        $limit = $this->limit($key);

        return $limit === null || $current <= $limit;
    }
}
