<?php

namespace Afterburner\Subscriptions\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class TeamTrialPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(Authenticatable $user, Model $team): bool
    {
        return $this->isSystemAdmin($user);
    }

    protected function isSystemAdmin(Authenticatable $user): bool
    {
        return method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();
    }
}
