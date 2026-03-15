<?php

namespace Tests\Feature\SwiftPos;

use App\Models\PosSession;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_open_a_second_pos_session(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = User::factory()->create();
        $cashier->assignRole('cashier');

        PosSession::factory()->create([
            'user_id' => $cashier->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($cashier)->post(route('pos.sessions.open'), [
            'opening_cash' => 120,
            'notes' => 'Shift start',
        ]);

        $response
            ->assertRedirect(route('pos.index'))
            ->assertSessionHas('error', 'You already have an open POS session.');

        $this->assertDatabaseCount('pos_sessions', 1);
    }

    public function test_database_allows_only_one_open_session_per_user(): void
    {
        $user = User::factory()->create();

        PosSession::factory()->create([
            'user_id' => $user->id,
            'status' => 'open',
            'opened_at' => now()->subHour(),
        ]);

        $this->expectException(QueryException::class);

        PosSession::factory()->create([
            'user_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }
}
