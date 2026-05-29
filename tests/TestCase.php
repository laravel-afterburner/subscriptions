<?php

namespace Afterburner\Subscriptions\Tests;

use Afterburner\Subscriptions\Providers\SubscriptionsServiceProvider;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'afterburner-subscriptions.enabled' => true,
            'afterburner-subscriptions.default_trial_days' => 30,
        ]);

        $this->app['router']->aliasMiddleware('system.admin', function ($request, $next) {
            if (! auth()->check() || ! auth()->user()->isSystemAdmin()) {
                abort(403);
            }

            return $next($request);
        });

        Blade::anonymousComponentPath(
            __DIR__.'/../vendor/laravel-afterburner/jetstream/resources/views/components'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            SubscriptionsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('auth.guards.web.provider', 'users');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function seedPermissions(): void
    {
        $now = now();
        foreach ([
            ['name' => 'Manage Billing', 'slug' => 'manage_billing'],
            ['name' => 'View Billing', 'slug' => 'view_billing'],
        ] as $permission) {
            DB::table('permissions')->insertOrIgnore($permission + [
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function createTeamWithUser(array $permissions = ['manage_billing', 'view_billing']): array
    {
        $this->seedPermissions();

        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Manager',
            'slug' => 'manager',
            'hierarchy' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($permissions as $slug) {
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
            DB::table('role_permission')->insert([
                'role_slug' => 'manager',
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $team = Team::query()->create([
            'name' => 'Test Entity',
            'user_id' => $user->id,
            'trial_ends_at' => now()->addDays(30),
        ]);

        $team->users()->attach($user);
        $user->update(['current_team_id' => $team->id]);

        DB::table('user_role')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'team_id' => $team->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $team];
    }

    protected function createTeamMember(Team $team, array $permissions = []): User
    {
        $this->seedPermissions();

        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Member',
            'slug' => 'member',
            'hierarchy' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($permissions as $slug) {
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
            DB::table('role_permission')->insert([
                'role_slug' => 'member',
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $member = User::query()->create([
            'name' => 'Entity Member',
            'email' => 'member@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $team->users()->attach($member);
        $member->update(['current_team_id' => $team->id]);

        DB::table('user_role')->insert([
            'user_id' => $member->id,
            'role_id' => $roleId,
            'team_id' => $team->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $member;
    }
}
