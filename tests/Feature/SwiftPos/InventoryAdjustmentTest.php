<?php

namespace Tests\Feature\SwiftPos;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_adjust_inventory_levels(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock_quantity' => 5,
            'track_inventory' => true,
        ]);

        $response = $this->actingAs($manager)->post(route('inventory.adjust', $product->id), [
            'quantity' => 2,
            'direction' => 'out',
            'movement_type' => 'adjustment',
            'note' => 'Shrinkage',
        ]);

        $response->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 3,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'movement_type' => 'adjustment',
            'quantity' => -2,
            'previous_stock' => 5,
            'new_stock' => 3,
        ]);
    }

    public function test_inventory_adjustment_rejects_when_stock_would_go_negative(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock_quantity' => 1,
            'track_inventory' => true,
        ]);

        $response = $this->from(route('inventory.index'))->actingAs($manager)->post(route('inventory.adjust', $product->id), [
            'quantity' => 5,
            'direction' => 'out',
            'movement_type' => 'adjustment',
        ]);

        $response
            ->assertRedirect(route('inventory.index'))
            ->assertSessionHasErrors('quantity');
    }
}
