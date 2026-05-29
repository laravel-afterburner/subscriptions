<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'current_team_id', 'is_system_admin'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_system_admin' => 'boolean',
        ];
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function isSystemAdmin(): bool
    {
        return $this->is_system_admin === true;
    }

    public function hasPermission(string $permissionSlug, ?int $teamId = null): bool
    {
        $teamId = $teamId ?? $this->currentTeam?->id;

        if (! $teamId) {
            return false;
        }

        return DB::table('user_role')
            ->join('roles', 'user_role.role_id', '=', 'roles.id')
            ->join('role_permission', 'roles.slug', '=', 'role_permission.role_slug')
            ->join('permissions', 'role_permission.permission_id', '=', 'permissions.id')
            ->where('user_role.user_id', $this->id)
            ->where('user_role.team_id', $teamId)
            ->where('permissions.slug', $permissionSlug)
            ->exists();
    }
}
