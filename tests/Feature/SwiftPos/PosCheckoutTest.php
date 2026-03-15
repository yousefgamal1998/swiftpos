<?php

namespace Tests\Feature\SwiftPos;

use App\Models\Category;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_checkout_order_and_stock_is_deducted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = User::factory()->create();
        $cashier->assignRole('cashier');

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 5,
            'tax_rate' => 5,
            'stock_quantity' => 10,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $session = PosSession::factory()->create([
            'user_id' => $cashier->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($cashier)->post(route('pos.checkout'), [
            'pos_session_id' => $session->id,
            'order_type' => 'retail',
            'customer_name' => 'Walk-in',
            'discount_amount' => 0,
            'payment_method' => 'cash',
            'amount_tendered' => 20,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 5,
                    'tax_rate' => 5,
                    'discount_amount' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $cashier->id,
            'pos_session_id' => $session->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 10.5,
            'paid_amount' => 10.5,
            'change_amount' => 9.5,
        ]);

        $this->assertDatabaseHas('payments', [
            'method' => 'cash',
            'amount' => 10.5,
            'status' => 'completed',
            'user_id' => $cashier->id,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 8,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'movement_type' => 'sale',
            'quantity' => -2,
            'new_stock' => 8,
        ]);
    }

    public function test_checkout_ignores_client_submitted_pricing_and_tax_values(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = User::factory()->create();
        $cashier->assignRole('cashier');

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 10,
            'tax_rate' => 5,
            'stock_quantity' => 10,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $session = PosSession::factory()->create([
            'user_id' => $cashier->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($cashier)->post(route('pos.checkout'), [
            'pos_session_id' => $session->id,
            'order_type' => 'retail',
            'payment_method' => 'cash',
            'amount_tendered' => 20,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 0.01,
                    'tax_rate' => 0,
                    'discount_amount' => 999,
                ],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $cashier->id,
            'total_amount' => 10.5,
            'tax_amount' => 0.5,
            'discount_amount' => 0,
        ]);
    }
}
