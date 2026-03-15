<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'manage products',
            'manage inventory',
            'manage orders',
            'process sales',
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $cashier = Role::firstOrCreate(['name' => 'cashier']);

        $admin->syncPermissions($permissions);

        $manager->syncPermissions([
            'view dashboard',
            'manage products',
            'manage inventory',
            'manage orders',
            'process sales',
        ]);

        $cashier->syncPermissions([
            'view dashboard',
            'manage orders',
            'process sales',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
