<?php

namespace Afterburner\Subscriptions\Support;

use Afterburner\Subscriptions\Concerns\HasSubscriptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Cashier;

class TeamTrialAdmin
{
    /**
     * @return class-string<Model>
     */
    public static function teamModelClass(): string
    {
        $teamModel = config('afterburner.team_model', Cashier::$customerModel);

        if (! is_string($teamModel) || ! class_exists($teamModel)) {
            return Cashier::$customerModel;
        }

        return $teamModel;
    }

    public static function teamSupportsTrials(): bool
    {
        return in_array(HasSubscriptions::class, class_uses_recursive(static::teamModelClass()), true);
    }

    /**
     * @return Builder<Model>
     */
    public static function activeTrialsQuery(): Builder
    {
        return static::teamModelClass()::query()
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->orderBy('trial_ends_at');
    }

    /**
     * @return list<int>
     */
    public static function dayPresets(): array
    {
        $presets = config('afterburner-subscriptions.admin_trial_day_presets', [30, 60, 90]);

        return array_values(array_map('intval', $presets));
    }

    /**
     * @return list<Model>
     */
    public static function searchTeams(string $query, int $limit = 10): array
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return [];
        }

        $teamModel = static::teamModelClass();

        return $teamModel::query()
            ->where(function (Builder $builder) use ($query) {
                $builder->where('name', 'like', '%'.$query.'%');

                if (ctype_digit($query)) {
                    $builder->orWhere('id', (int) $query);
                }
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->all();
    }
}
