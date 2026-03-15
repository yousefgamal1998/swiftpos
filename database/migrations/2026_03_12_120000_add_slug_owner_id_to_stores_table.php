<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->foreignId('owner_id')->nullable()->after('slug')->constrained('users')->nullOnDelete();
        });

        $used = [];
        $stores = DB::table('stores')->orderBy('id')->get(['id', 'name']);

        foreach ($stores as $store) {
            $base = Str::slug($store->name ?: '') ?: "store-{$store->id}";
            $slug = $base;
            $counter = 2;

            while (in_array($slug, $used, true)) {
                $slug = "{$base}-{$counter}";
                $counter++;
            }

            $used[] = $slug;

            DB::table('stores')
                ->where('id', $store->id)
                ->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
