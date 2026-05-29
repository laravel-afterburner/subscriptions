<?php

namespace Afterburner\Subscriptions\Actions;

use Afterburner\Subscriptions\Concerns\HasSubscriptions;
use Afterburner\Subscriptions\Notifications\TrialEndingNotification;
use Afterburner\Subscriptions\Support\BillingRecipients;
use Illuminate\Support\Facades\Notification;

class NotifyTrialEnding
{
    public function __invoke(int $daysRemaining): int
    {
        $teamModel = config('afterburner.team_model', \App\Models\Team::class);

        if (! in_array(HasSubscriptions::class, class_uses_recursive($teamModel), true)) {
            return 0;
        }

        $targetDate = now()->addDays($daysRemaining)->toDateString();

        $teams = $teamModel::query()
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', $targetDate)
            ->get();

        $sent = 0;

        foreach ($teams as $team) {
            if (method_exists($team, 'subscribed') && $team->subscribed()) {
                continue;
            }

            $recipients = BillingRecipients::forTeam($team);

            if ($recipients->isEmpty()) {
                continue;
            }

            Notification::send($recipients, new TrialEndingNotification($team, $daysRemaining));
            $sent += $recipients->count();
        }

        return $sent;
    }
}
