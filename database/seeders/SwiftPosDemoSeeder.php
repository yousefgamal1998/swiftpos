<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SwiftPosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@swiftpos.test'],
            [
                'name' => 'SwiftPOS Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        $manager = User::updateOrCreate(
            ['email' => 'manager@swiftpos.test'],
            [
                'name' => 'SwiftPOS Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $manager->syncRoles(['manager']);

        $cashier = User::updateOrCreate(
            ['email' => 'cashier@swiftpos.test'],
            [
                'name' => 'SwiftPOS Cashier',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $cashier->syncRoles(['cashier']);

        $categories = collect([
            ['name' => 'Beverages', 'slug' => 'beverages'],
            ['name' => 'Snacks', 'slug' => 'snacks'],
            ['name' => 'Mains', 'slug' => 'mains'],
            ['name' => 'Grocery', 'slug' => 'grocery'],
        ])->mapWithKeys(function (array $category): array {
            $record = Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'is_active' => true,
                ]
            );

            return [$category['slug'] => $record->id];
        });

        $products = [
            ['sku' => 'BEV-001', 'name' => 'Mineral Water 500ml', 'category' => 'beverages', 'price' => 1.50, 'cost' => 0.80, 'tax_rate' => 5, 'stock' => 120],
            ['sku' => 'BEV-002', 'name' => 'Orange Juice', 'category' => 'beverages', 'price' => 2.75, 'cost' => 1.60, 'tax_rate' => 5, 'stock' => 85],
            ['sku' => 'SNK-001', 'name' => 'Potato Chips', 'category' => 'snacks', 'price' => 1.90, 'cost' => 1.10, 'tax_rate' => 8, 'stock' => 60],
            ['sku' => 'SNK-002', 'name' => 'Chocolate Bar', 'category' => 'snacks', 'price' => 2.20, 'cost' => 1.30, 'tax_rate' => 8, 'stock' => 40],
            ['sku' => 'MNS-001', 'name' => 'Chicken Wrap', 'category' => 'mains', 'price' => 7.50, 'cost' => 4.50, 'tax_rate' => 10, 'stock' => 50],
            ['sku' => 'MNS-002', 'name' => 'Cheese Burger', 'category' => 'mains', 'price' => 8.20, 'cost' => 4.90, 'tax_rate' => 10, 'stock' => 45],
            ['sku' => 'GRC-001', 'name' => 'Brown Rice 1kg', 'category' => 'grocery', 'price' => 4.80, 'cost' => 3.30, 'tax_rate' => 5, 'stock' => 35],
            ['sku' => 'GRC-002', 'name' => 'Olive Oil 1L', 'category' => 'grocery', 'price' => 10.90, 'cost' => 7.40, 'tax_rate' => 5, 'stock' => 25],
        ];

        foreach ($products as $item) {
            Product::updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'category_id' => $categories[$item['category']],
                    'name' => $item['name'],
                    'slug' => str($item['name'])->slug()->toString(),
                    'type' => in_array($item['category'], ['mains', 'beverages'], true) ? 'restaurant' : 'retail',
                    'price' => $item['price'],
                    'cost' => $item['cost'],
                    'tax_rate' => $item['tax_rate'],
                    'track_inventory' => true,
                    'stock_quantity' => $item['stock'],
                    'low_stock_threshold' => 10,
                    'unit' => 'pcs',
                    'is_active' => true,
                ]
            );
        }
    }
}
