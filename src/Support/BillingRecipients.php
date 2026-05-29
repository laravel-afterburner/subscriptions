<?php

namespace Afterburner\Subscriptions\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillingRecipients
{
    /**
     * Resolve unique users who should receive billing notifications for an entity.
     *
     * @return Collection<int, \Illuminate\Contracts\Auth\Authenticatable>
     */
    public static function forTeam(Model $team): Collection
    {
        $recipients = collect();

        if (method_exists($team, 'owner') && $team->owner) {
            $recipients->push($team->owner);
        }

        $roleSlugs = config('afterburner-subscriptions.billing_role_slugs', []);

        if ($roleSlugs === [] || ! DB::getSchemaBuilder()->hasTable('user_role')) {
            return $recipients->unique(fn ($user) => $user->getAuthIdentifier())->values();
        }

        $userRoleTable = static::resolveUserRoleTable();

        if ($userRoleTable === null) {
            return $recipients->unique(fn ($user) => $user->getAuthIdentifier())->values();
        }

        $userRoleColumns = DB::getSchemaBuilder()->getColumnListing($userRoleTable);
        $userModel = config('auth.providers.users.model');

        $query = DB::table($userRoleTable)
            ->join('roles', function ($join) use ($userRoleTable, $userRoleColumns) {
                if (in_array('role_id', $userRoleColumns, true)) {
                    $join->on("{$userRoleTable}.role_id", '=', 'roles.id');
                } elseif (in_array('role_slug', $userRoleColumns, true)) {
                    $join->on("{$userRoleTable}.role_slug", '=', 'roles.slug');
                }
            })
            ->whereIn('roles.slug', $roleSlugs)
            ->where("{$userRoleTable}.user_id", '!=', $team->user_id ?? 0);

        if (in_array('team_id', $userRoleColumns, true)) {
            $query->where("{$userRoleTable}.team_id", $team->getKey());
        }

        $userIds = $query->pluck("{$userRoleTable}.user_id");

        foreach ($userIds as $userId) {
            $user = $userModel::query()->find($userId);
            if ($user) {
                $recipients->push($user);
            }
        }

        return $recipients->unique(fn ($user) => $user->getAuthIdentifier())->values();
    }

    protected static function resolveUserRoleTable(): ?string
    {
        foreach (['user_role', 'role_user', 'user_roles', 'role_users'] as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                return $table;
            }
        }

        return null;
    }
}
