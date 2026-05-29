<?php

namespace Afterburner\Subscriptions\Actions\Stripe;

use Illuminate\Database\Eloquent\Model;

class StartTeamTrial
{
    public function __invoke(Model $team): void
    {
        if (! config('afterburner-subscriptions.enabled', true)) {
            return;
        }

        if ($team->trial_ends_at !== null) {
            return;
        }

        $days = (int) config('afterburner-subscriptions.default_trial_days', 30);

        $team->forceFill([
            'trial_ends_at' => now()->addDays($days),
        ])->save();
    }
}
