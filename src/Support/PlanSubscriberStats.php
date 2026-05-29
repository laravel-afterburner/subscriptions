<?php

namespace Afterburner\Subscriptions\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Laravel\Cashier\Cashier;

class PlanSubscriberStats
{
    /** @var array<int, string> */
    protected const STATUS_ORDER = [
        'Active',
        'Trial',
        'Past due',
        'Cancelled (grace period)',
        'Inactive',
    ];

    /**
     * @param  Collection<int, \Afterburner\Subscriptions\Models\SubscriptionPlan>  $plans
     * @return array<int, array{total: int, statuses: array<int, array{label: string, count: int, badgeClasses: string}>}>
     */
    public static function forPlans(Collection $plans): array
    {
        $planIds = $plans->pluck('id')->filter()->values()->all();

        if ($planIds === []) {
            return [];
        }

        $countsByPlan = array_fill_keys($planIds, []);

        $teamModel = Cashier::$customerModel;

        if (! is_string($teamModel) || ! class_exists($teamModel)) {
            return self::emptyStatsForPlanIds($planIds);
        }

        $query = $teamModel::query()->whereIn('subscription_plan_id', $planIds);

        if (Schema::hasTable('subscriptions')) {
            $query->with('subscriptions');
        }

        foreach ($query->get() as $team) {
            $planId = $team->subscription_plan_id;

            if ($planId === null) {
                continue;
            }

            $label = SubscriptionSummary::forTeam($team)->statusLabel();
            $countsByPlan[$planId][$label] = ($countsByPlan[$planId][$label] ?? 0) + 1;
        }

        $result = [];

        foreach ($planIds as $planId) {
            $counts = $countsByPlan[$planId] ?? [];
            $statuses = [];

            foreach (self::STATUS_ORDER as $label) {
                $count = $counts[$label] ?? 0;

                if ($count > 0) {
                    $statuses[] = [
                        'label' => $label,
                        'count' => $count,
                        'badgeClasses' => SubscriptionSummary::badgeClassesForStatusLabel($label),
                    ];
                }
            }

            foreach ($counts as $label => $count) {
                if (! in_array($label, self::STATUS_ORDER, true) && $count > 0) {
                    $statuses[] = [
                        'label' => $label,
                        'count' => $count,
                        'badgeClasses' => SubscriptionSummary::badgeClassesForStatusLabel($label),
                    ];
                }
            }

            $result[$planId] = [
                'total' => array_sum($counts),
                'statuses' => $statuses,
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, int|string>  $planIds
     * @return array<int, array{total: int, statuses: array<int, array{label: string, count: int, badgeClasses: string}>}>
     */
    protected static function emptyStatsForPlanIds(array $planIds): array
    {
        $empty = ['total' => 0, 'statuses' => []];

        return array_fill_keys($planIds, $empty);
    }
}
