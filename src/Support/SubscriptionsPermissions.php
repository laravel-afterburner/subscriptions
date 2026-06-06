<?php

namespace Afterburner\Subscriptions\Support;

use App\Models\Team;
use App\Models\User;
use App\Support\TeamPermissionGate;

/**
 * Subscription billing page sections mapped to permission slugs.
 */
final class SubscriptionsPermissions
{
    public const SECTION_STATUS = 'status';

    public const SECTION_PLANS = 'plans';

    public const SECTION_INVOICES = 'invoices';

    /**
     * @return array<string, string>
     */
    public static function sectionPermissionMap(): array
    {
        return [
            self::SECTION_STATUS => 'view_subscription_status',
            self::SECTION_PLANS => 'view_subscription_plans',
            self::SECTION_INVOICES => 'view_subscription_invoices',
        ];
    }

    /**
     * @return list<string>
     */
    public static function sectionDisplayOrder(): array
    {
        return [
            self::SECTION_STATUS,
            self::SECTION_PLANS,
            self::SECTION_INVOICES,
        ];
    }

    /**
     * @return list<string>
     */
    public static function moduleAccessSlugs(): array
    {
        return [
            'view_billing',
            'view_subscription_status',
            'view_subscription_plans',
            'view_subscription_invoices',
            'manage_billing',
        ];
    }

    public static function canAccessModule(User $user, Team $team): bool
    {
        return TeamPermissionGate::allowsAny($user, $team->id, self::moduleAccessSlugs());
    }

    public static function canViewSection(User $user, Team $team, string $section): bool
    {
        $slug = self::sectionPermissionMap()[$section] ?? null;

        if ($slug === null) {
            return false;
        }

        if ($section === self::SECTION_PLANS) {
            return TeamPermissionGate::allowsAny($user, $team->id, [
                $slug,
                'manage_billing',
            ]);
        }

        return TeamPermissionGate::allows($user, $team->id, $slug);
    }

    /**
     * @return list<string>
     */
    public static function visibleSections(User $user, Team $team): array
    {
        $visible = [];

        foreach (self::sectionDisplayOrder() as $section) {
            if (self::canViewSection($user, $team, $section)) {
                $visible[] = $section;
            }
        }

        return $visible;
    }
}
