<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()
            ->where('email', 'admin@swiftpos.test')
            ->first();

        if (! $user) {
            return;
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $user->syncRoles([$adminRole->name]);
    }
}
