<?php

namespace Afterburner\Subscriptions\Database\Seeders;

use Afterburner\Subscriptions\Database\Seeders\Concerns\AssignsPermissionsToTeamOwners;
use Afterburner\Subscriptions\Support\SubscriptionPermissionDefinitions;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionsPermissionsSeeder extends Seeder
{
    use AssignsPermissionsToTeamOwners;

    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('permissions')) {
            if (isset($this->command)) {
                $this->command->error('Permissions table does not exist. Please ensure your database migrations are up to date.');
            }

            return;
        }

        $now = Carbon::now();
        $permissions = array_map(
            fn (array $permission) => $permission + ['created_at' => $now, 'updated_at' => $now],
            SubscriptionPermissionDefinitions::all()
        );

        $insertedPermissionIds = [];
        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore($permission);
            $permissionRecord = DB::table('permissions')
                ->where('slug', $permission['slug'])
                ->first();
            if ($permissionRecord) {
                $insertedPermissionIds[] = $permissionRecord->id;
            }
        }

        if (! empty($insertedPermissionIds) && DB::getSchemaBuilder()->hasTable('role_permission')) {
            $assignedCount = $this->assignPermissionsToTeamOwners($insertedPermissionIds, $permissions, $now);

            if (isset($this->command) && $assignedCount > 0) {
                $this->command->info("✓ Permissions assigned to {$assignedCount} entity owner role(s)");
            }

            $billingRoleCount = $this->assignPermissionsToBillingRoles($insertedPermissionIds, $permissions, $now);

            if (isset($this->command) && $billingRoleCount > 0) {
                $this->command->info("✓ Permissions assigned to {$billingRoleCount} billing role(s)");
            }
        }

        if (isset($this->command)) {
            $this->command->info('✓ Subscriptions permissions seeded successfully!');
        }
    }

    protected function assignPermissionsToBillingRoles(array $insertedPermissionIds, array $permissions, $now): int
    {
        $roleSlugs = config('afterburner-subscriptions.billing_role_slugs', []);

        if ($roleSlugs === []) {
            return 0;
        }

        $rolePermissionColumns = DB::getSchemaBuilder()->getColumnListing('role_permission');
        $hasTimestamps = in_array('created_at', $rolePermissionColumns, true)
            && in_array('updated_at', $rolePermissionColumns, true);
        $assignedCount = 0;

        foreach ($roleSlugs as $roleSlug) {
            $role = DB::table('roles')->where('slug', $roleSlug)->first();

            if (! $role) {
                continue;
            }

            if (in_array('role_slug', $rolePermissionColumns, true) && in_array('permission_id', $rolePermissionColumns, true)) {
                foreach ($insertedPermissionIds as $permissionId) {
                    $data = [
                        'role_slug' => $role->slug,
                        'permission_id' => $permissionId,
                    ];
                    if ($hasTimestamps) {
                        $data['created_at'] = $now;
                        $data['updated_at'] = $now;
                    }
                    DB::table('role_permission')->insertOrIgnore($data);
                }
                $assignedCount++;
            } elseif (in_array('role_slug', $rolePermissionColumns, true) && in_array('permission_slug', $rolePermissionColumns, true)) {
                foreach ($permissions as $permission) {
                    $data = [
                        'role_slug' => $role->slug,
                        'permission_slug' => $permission['slug'],
                    ];
                    if ($hasTimestamps) {
                        $data['created_at'] = $now;
                        $data['updated_at'] = $now;
                    }
                    DB::table('role_permission')->insertOrIgnore($data);
                }
                $assignedCount++;
            } elseif (in_array('role_id', $rolePermissionColumns, true) && in_array('permission_id', $rolePermissionColumns, true)) {
                foreach ($insertedPermissionIds as $permissionId) {
                    $data = [
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                    ];
                    if ($hasTimestamps) {
                        $data['created_at'] = $now;
                        $data['updated_at'] = $now;
                    }
                    DB::table('role_permission')->insertOrIgnore($data);
                }
                $assignedCount++;
            }
        }

        return $assignedCount;
    }
}
