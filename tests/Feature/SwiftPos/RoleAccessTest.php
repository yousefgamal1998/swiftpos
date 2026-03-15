<?php

namespace Tests\Feature\SwiftPos;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_role_cannot_access_product_management(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('products.index'));

        $response->assertForbidden();
    }

    public function test_manager_can_access_product_management(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $response = $this->actingAs($manager)->get(route('products.index'));

        $response->assertOk();
    }

    public function test_cashier_cannot_access_inventory_management(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $cashier = User::factory()->create();
        $cashier->assignRole('cashier');

        $response = $this->actingAs($cashier)->get(route('inventory.index'));

        $response->assertForbidden();
    }
}
