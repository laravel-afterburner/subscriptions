<?php

namespace Afterburner\Subscriptions\Actions;

use App\Support\Audit\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SetTeamTrial
{
    public function __invoke(Model $team, CarbonInterface $endsAt): void
    {
        if (! config('afterburner-subscriptions.enabled', true)) {
            return;
        }

        $previous = $team->trial_ends_at;

        $team->forceFill([
            'trial_ends_at' => $endsAt->copy()->utc(),
        ])->save();

        $this->logChange($team, $previous, $endsAt, 'Team trial updated.');
    }

    protected function logChange(Model $team, mixed $previous, CarbonInterface $new, string $summary): void
    {
        if (! class_exists(AuditLogger::class)) {
            return;
        }

        AuditLogger::log(
            category: 'billing',
            eventName: 'team_trial.updated',
            auditable: $team,
            changes: AuditLogger::changesWithSummary($summary, context: [
                'trial_ends_at' => [
                    'old' => $previous?->toIso8601String(),
                    'new' => $new->toIso8601String(),
                ],
                'admin_user_id' => Auth::id(),
            ]),
            teamId: $team->getKey(),
            actionType: 'update',
        );
    }
}
