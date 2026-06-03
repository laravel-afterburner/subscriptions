<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Actions\ClearTeamTrial;
use Afterburner\Subscriptions\Actions\SetTeamTrial;
use Afterburner\Subscriptions\Livewire\Admin\TeamTrials\Index;
use Afterburner\Subscriptions\Support\SubscriptionStatus;
use Afterburner\Subscriptions\Support\TeamTrialManagement;
use Afterburner\Subscriptions\Support\TeamTrialSchedule;
use Afterburner\Subscriptions\Tests\TestCase;
use Livewire\Livewire;

class TeamTrialAdminTest extends TestCase
{
    public function test_system_admin_can_manage_team_trials(): void
    {
        [$user] = $this->createTeamWithUser();
        $user->update(['is_system_admin' => true]);

        $this->assertTrue($user->can('viewAny', TeamTrialManagement::class));
        $this->assertTrue($user->can('manageTeamTrial', $user->currentTeam));
    }

    public function test_non_admin_cannot_manage_team_trials(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $this->assertFalse($user->can('viewAny', TeamTrialManagement::class));
        $this->assertFalse($user->can('manageTeamTrial', $team));
    }

    public function test_set_team_trial_restores_expired_trial(): void
    {
        [, $team] = $this->createTeamWithUser();
        $team->update(['trial_ends_at' => now()->subDay()]);

        $endsAt = now()->addDays(60);

        app(SetTeamTrial::class)($team, $endsAt);

        $team->refresh();

        $this->assertTrue($team->onGenericTrial());
        $this->assertTrue(SubscriptionStatus::forTeam($team)->isActive());
    }

    public function test_clear_team_trial_blocks_when_unpaid(): void
    {
        [, $team] = $this->createTeamWithUser();
        $team->update(['trial_ends_at' => now()->addDays(5)]);

        app(ClearTeamTrial::class)($team);

        $team->refresh();

        $this->assertFalse($team->onGenericTrial());
        $this->assertTrue(SubscriptionStatus::forTeam($team)->isBlocked());
    }

    public function test_system_admin_can_apply_trial_via_livewire(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['is_system_admin' => true]);
        $team->update(['trial_ends_at' => now()->subDay()]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedTeamId', $team->id)
            ->set('presetDays', 60)
            ->set('extendMode', TeamTrialSchedule::EXTEND_FROM_TODAY)
            ->call('applyTrial')
            ->assertHasNoErrors();

        $team->refresh();

        $this->assertTrue($team->onGenericTrial());
        $this->assertGreaterThanOrEqual(59, (int) now()->diffInDays($team->trial_ends_at, false));
    }

    public function test_non_admin_cannot_access_team_trials_livewire(): void
    {
        [$user] = $this->createTeamWithUser();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertForbidden();
    }

    public function test_system_admin_sees_team_trials_section(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $user->update(['is_system_admin' => true]);
        $team->update(['trial_ends_at' => now()->addDays(14)]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Team trials')
            ->assertSee('Active trials')
            ->assertSee($team->name);
    }
}
