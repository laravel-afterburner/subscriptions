<?php

namespace Afterburner\Subscriptions\Console\Commands;

use Afterburner\Subscriptions\Actions\NotifyTrialEnding;
use Illuminate\Console\Command;

class NotifyTrialEndingCommand extends Command
{
    protected $signature = 'afterburner:subscriptions:notify-trial-ending';

    protected $description = 'Send trial ending notifications to billing recipients';

    public function handle(NotifyTrialEnding $notifyTrialEnding): int
    {
        if (! config('afterburner-subscriptions.enabled', true)) {
            $this->warn('Subscriptions are disabled.');

            return Command::SUCCESS;
        }

        $days = config('afterburner-subscriptions.trial_ending_notification_days', [7, 1]);
        $total = 0;

        foreach ($days as $daysRemaining) {
            $sent = $notifyTrialEnding((int) $daysRemaining);
            $total += $sent;

            if ($sent > 0) {
                $this->info("Sent {$sent} notification(s) for trials ending in {$daysRemaining} day(s).");
            }
        }

        if ($total === 0) {
            $this->info('No trial ending notifications to send.');
        }

        return Command::SUCCESS;
    }
}
