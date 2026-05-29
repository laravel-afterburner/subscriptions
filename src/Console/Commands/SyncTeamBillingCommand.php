<?php

namespace Afterburner\Subscriptions\Console\Commands;

use Afterburner\Subscriptions\Actions\Stripe\SyncCompletedCheckout;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class SyncTeamBillingCommand extends Command
{
    protected $signature = 'afterburner:subscriptions:sync-checkout
                            {team : Team ID}
                            {session : Stripe Checkout session ID (cs_...)}';

    protected $description = 'Sync plan, subscription row, and payment method from a completed Stripe Checkout session';

    public function handle(SyncCompletedCheckout $sync): int
    {
        $teamModel = config('afterburner.team_model', \App\Models\Team::class);

        /** @var Model|null $team */
        $team = $teamModel::query()->find($this->argument('team'));

        if (! $team) {
            $this->components->error('Team not found.');

            return self::FAILURE;
        }

        $synced = $sync($team, $this->argument('session'));

        if (! $synced) {
            $this->components->warn('Checkout session was not complete or did not match this team.');

            return self::FAILURE;
        }

        $team->refresh();

        $this->components->info('Billing synced for team #'.$team->getKey());
        $this->line('  subscription_plan_id: '.($team->subscription_plan_id ?? '—'));
        $this->line('  pm: '.($team->pm_type ?? '—').' ···· '.($team->pm_last_four ?? '—'));
        $this->line('  cashier subscriptions: '.$team->subscriptions()->count());

        return self::SUCCESS;
    }
}
