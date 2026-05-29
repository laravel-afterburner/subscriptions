<?php

namespace Afterburner\Subscriptions\Policies;

use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Illuminate\Contracts\Auth\Authenticatable;

class SubscriptionPromotionPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function view(Authenticatable $user, SubscriptionPromotionCode $promotion): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(Authenticatable $user, SubscriptionPromotionCode $promotion): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(Authenticatable $user, SubscriptionPromotionCode $promotion): bool
    {
        return $this->isSystemAdmin($user);
    }

    protected function isSystemAdmin(Authenticatable $user): bool
    {
        return method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();
    }
}
