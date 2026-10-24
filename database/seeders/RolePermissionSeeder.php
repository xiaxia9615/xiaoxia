<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'ledger.view',
            'ledger.manage',
            'skill.create',
            'skill.call',
            'skill.audit',
            'apikey.manage',
            'team.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        $user = Role::firstOrCreate(['name' => 'user']);
        $user->syncPermissions([
            'ledger.view',
            'skill.create',
            'skill.call',
            'apikey.manage',
        ]);

        // 现有用户赋角色: 全部 user, id=1 加 admin
        User::all()->each(function (User $u) {
            if (!$u->hasRole('user')) {
                $u->assignRole('user');
            }
        });

        $first = User::find(1);
        if ($first && !$first->hasRole('admin')) {
            $first->assignRole('admin');
        }

        $this->command->info(sprintf(
            'Seeded: %d permissions, roles admin/user, %d users assigned.',
            count($permissions),
            User::count(),
        ));
    }
}
