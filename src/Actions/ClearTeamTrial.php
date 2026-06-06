<?php

namespace Afterburner\Subscriptions\Actions;

use Afterburner\Support\EntityLabel;
use App\Support\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ClearTeamTrial
{
    public function __invoke(Model $team): void
    {
        if (! config('afterburner-subscriptions.enabled', true)) {
            return;
        }

        $previous = $team->trial_ends_at;

        if ($previous === null) {
            return;
        }

        $team->forceFill([
            'trial_ends_at' => null,
        ])->save();

        $this->logChange($team, $previous);
    }

    protected function logChange(Model $team, mixed $previous): void
    {
        if (! class_exists(AuditLogger::class)) {
            return;
        }

        AuditLogger::log(
            category: 'billing',
            eventName: 'team_trial.cleared',
            auditable: $team,
            changes: AuditLogger::changesWithSummary(EntityLabel::singularTitle().' trial ended.', context: [
                'trial_ends_at' => [
                    'old' => $previous?->toIso8601String(),
                    'new' => null,
                ],
                'admin_user_id' => Auth::id(),
            ]),
            teamId: $team->getKey(),
            actionType: 'update',
        );
    }
}
