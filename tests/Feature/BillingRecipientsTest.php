<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Support\BillingRecipients;
use Afterburner\Subscriptions\Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BillingRecipientsTest extends TestCase
{
    public function test_resolves_team_owner(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $recipients = BillingRecipients::forTeam($team);

        $this->assertTrue($recipients->contains(fn ($recipient) => $recipient->id === $user->id));
    }

    public function test_resolves_billing_role_members(): void
    {
        [$owner, $team] = $this->createTeamWithUser([]);

        $treasurerRoleId = DB::table('roles')->insertGetId([
            'name' => 'Treasurer',
            'slug' => 'treasurer',
            'hierarchy' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $treasurer = User::query()->create([
            'name' => 'Treasurer User',
            'email' => 'treasurer@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $team->users()->attach($treasurer);

        DB::table('user_role')->insert([
            'user_id' => $treasurer->id,
            'role_id' => $treasurerRoleId,
            'team_id' => $team->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config(['afterburner-subscriptions.billing_role_slugs' => ['treasurer']]);

        $recipients = BillingRecipients::forTeam($team->fresh());

        $this->assertTrue($recipients->contains(fn ($recipient) => $recipient->id === $owner->id));
        $this->assertTrue($recipients->contains(fn ($recipient) => $recipient->id === $treasurer->id));
    }
}
