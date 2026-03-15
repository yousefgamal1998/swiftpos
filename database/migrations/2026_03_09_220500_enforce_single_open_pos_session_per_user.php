<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('pos_sessions')) {
            return;
        }

        $duplicateUserIds = DB::table('pos_sessions')
            ->where('status', 'open')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $latestOpenSessionId = DB::table('pos_sessions')
                ->where('user_id', $userId)
                ->where('status', 'open')
                ->orderByDesc('opened_at')
                ->orderByDesc('id')
                ->value('id');

            DB::table('pos_sessions')
                ->where('user_id', $userId)
                ->where('status', 'open')
                ->where('id', '!=', $latestOpenSessionId)
                ->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            if (! Schema::hasColumn('pos_sessions', 'open_guard')) {
                Schema::table('pos_sessions', function (Blueprint $table) {
                    $table->tinyInteger('open_guard')
                        ->nullable()
                        ->storedAs("case when `status` = 'open' then 1 else null end");
                });
            }

            Schema::table('pos_sessions', function (Blueprint $table) {
                $table->unique(['user_id', 'open_guard'], 'pos_sessions_user_open_unique');
            });

            return;
        }

        DB::statement(
            "CREATE UNIQUE INDEX pos_sessions_user_open_unique ON pos_sessions(user_id) WHERE status = 'open'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('pos_sessions')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('DROP INDEX pos_sessions_user_open_unique ON pos_sessions');

            if (Schema::hasColumn('pos_sessions', 'open_guard')) {
                Schema::table('pos_sessions', function (Blueprint $table) {
                    $table->dropColumn('open_guard');
                });
            }

            return;
        }

        DB::statement('DROP INDEX IF EXISTS pos_sessions_user_open_unique');
    }
};
