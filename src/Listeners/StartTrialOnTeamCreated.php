<?php

namespace Afterburner\Subscriptions\Listeners;

use Afterburner\Subscriptions\Actions\Stripe\StartTeamTrial;
use App\Events\TeamCreated;

class StartTrialOnTeamCreated
{
    public function __construct(protected StartTeamTrial $startTeamTrial) {}

    public function handle(TeamCreated $event): void
    {
        ($this->startTeamTrial)($event->team);
    }
}
