<?php

namespace Database\Seeders;

use App\Models\Card;
use Illuminate\Database\Seeder;

class CardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            [
                'title' => 'Restaurants',
                'description' => 'Monitor dining locations, shifts, and table performance.',
                'icon' => 'M6 4.5h12M6 9h12M6 13.5h12M6 18h12',
                'color' => 'emerald',
                'route_name' => 'restaurants.index',
                'permission' => null,
                'role' => null,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Stores',
                'description' => 'Track retail performance, inventory, and store health.',
                'icon' => 'M3.75 6.75h16.5M6 6.75V4.5h12v2.25M6 21V12h12v9',
                'color' => 'sky',
                'route_name' => 'stores.index',
                'permission' => 'manage products',
                'role' => null,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Point of Sale',
                'description' => 'Open registers and process transactions in real time.',
                'icon' => 'M3.75 6.75h16.5M4.5 9.75h15v8.25h-15zM7.5 13.5h3',
                'color' => 'amber',
                'route_name' => 'pos.index',
                'permission' => 'process sales',
                'role' => null,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Users',
                'description' => 'Manage access, roles, and team members.',
                'icon' => 'M15 19.5v-1.5a3 3 0 0 0-3-3h-3a3 3 0 0 0-3 3v1.5m9-9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                'color' => 'violet',
                'route_name' => 'users.index',
                'permission' => 'manage users',
                'role' => null,
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($cards as $card) {
            Card::updateOrCreate(
                ['route_name' => $card['route_name']],
                $card
            );
        }
    }
}
