<?php

namespace Afterburner\Subscriptions\Policies;

use Afterburner\Subscriptions\Models\SubscriptionPlan;
use Afterburner\Subscriptions\Support\SubscriptionsPermissions;
use App\Support\TeamPermissionGate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlanPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function view(Authenticatable $user, SubscriptionPlan $plan): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(Authenticatable $user, SubscriptionPlan $plan): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(Authenticatable $user, SubscriptionPlan $plan): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function manageBilling(Authenticatable $user, Model $team): bool
    {
        return TeamPermissionGate::allows($user, (int) $team->getKey(), 'manage_billing');
    }

    public function viewBilling(Authenticatable $user, Model $team): bool
    {
        return SubscriptionsPermissions::canAccessModule($user, $team);
    }

    protected function isSystemAdmin(Authenticatable $user): bool
    {
        return method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();
    }
}
