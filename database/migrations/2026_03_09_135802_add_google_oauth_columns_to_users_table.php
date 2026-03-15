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
        $addProvider = ! Schema::hasColumn('users', 'provider');
        $addGoogleId = ! Schema::hasColumn('users', 'google_id');
        $addAvatar = ! Schema::hasColumn('users', 'avatar');

        Schema::table('users', function (Blueprint $table) use ($addProvider, $addGoogleId, $addAvatar) {
            if ($addProvider) {
                $table->string('provider', 32)->nullable()->after('password');
            }

            if ($addGoogleId) {
                $table->string('google_id')->nullable()->after('provider');
            }

            if ($addAvatar) {
                $table->string('avatar')->nullable()->after('google_id');
            }
        });

        if (! Schema::hasColumn('users', 'provider') || ! Schema::hasColumn('users', 'google_id')) {
            return;
        }

        if (Schema::hasColumn('users', 'oauth_provider')) {
            DB::table('users')
                ->whereNull('provider')
                ->whereNotNull('oauth_provider')
                ->update(['provider' => DB::raw('oauth_provider')]);
        }

        if (Schema::hasColumn('users', 'oauth_provider_id')) {
            DB::table('users')
                ->whereNull('google_id')
                ->whereNotNull('oauth_provider_id')
                ->update(['google_id' => DB::raw('oauth_provider_id')]);
        }

        if (Schema::hasColumn('users', 'avatar_url')) {
            DB::table('users')
                ->whereNull('avatar')
                ->whereNotNull('avatar_url')
                ->update(['avatar' => DB::raw('avatar_url')]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->index(['provider', 'google_id'], 'users_provider_google_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_provider_google_id_index');
            });
        } catch (\Throwable) {
            // no-op
        }

        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('users', 'provider')) {
                $columnsToDrop[] = 'provider';
            }

            if (Schema::hasColumn('users', 'google_id')) {
                $columnsToDrop[] = 'google_id';
            }

            if (Schema::hasColumn('users', 'avatar')) {
                $columnsToDrop[] = 'avatar';
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
