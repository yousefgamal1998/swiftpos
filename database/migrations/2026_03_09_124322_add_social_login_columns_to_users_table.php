<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('oauth_provider', 32)->nullable()->after('password');
            $table->string('oauth_provider_id')->nullable()->after('oauth_provider');
            $table->string('avatar_url')->nullable()->after('oauth_provider_id');

            $table->index(['oauth_provider', 'oauth_provider_id'], 'users_oauth_provider_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_oauth_provider_id_index');
            $table->dropColumn(['oauth_provider', 'oauth_provider_id', 'avatar_url']);
        });
    }
};
