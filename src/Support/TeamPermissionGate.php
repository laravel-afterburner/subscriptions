<?php

namespace Afterburner\Subscriptions\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class TeamPermissionGate
{
    public static function allows(Authenticatable $user, string $slug, Model $team): bool
    {
        if (static::ownsTeam($user, $team) && $slug === 'manage_billing') {
            return true;
        }

        $teamId = (int) $team->getKey();

        if (class_exists(\App\Support\PermissionCatalog::class) && method_exists($user, 'getAuthIdentifier')) {
            return \App\Support\PermissionCatalog::allows($user, $teamId, $slug);
        }

        if (! method_exists($user, 'hasPermission')) {
            return false;
        }

        return $user->hasPermission($slug, $teamId);
    }

    public static function allowsAny(Authenticatable $user, array $slugs, Model $team): bool
    {
        $teamId = (int) $team->getKey();

        if (class_exists(\App\Support\PermissionCatalog::class) && method_exists($user, 'getAuthIdentifier')) {
            return \App\Support\PermissionCatalog::allowsAny($user, $teamId, $slugs);
        }

        foreach ($slugs as $slug) {
            if (static::allows($user, $slug, $team)) {
                return true;
            }
        }

        return false;
    }

    public static function ownsTeam(Authenticatable $user, Model $team): bool
    {
        if (! isset($team->user_id)) {
            return false;
        }

        return (int) $team->user_id === (int) $user->getAuthIdentifier();
    }
}
