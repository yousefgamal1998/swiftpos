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
        Schema::table('cards', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
            $table->unique('slug');
        });

        $existingSlugs = [];
        $cards = DB::table('cards')->select('id', 'title', 'slug')->orderBy('id')->get();

        foreach ($cards as $card) {
            if (! empty($card->slug)) {
                $existingSlugs[] = $card->slug;
                continue;
            }

            $base = Str::slug($card->title) ?: Str::random(8);
            $slug = $base;
            $counter = 1;

            while (
                in_array($slug, $existingSlugs, true)
                || DB::table('cards')->where('slug', $slug)->where('id', '!=', $card->id)->exists()
            ) {
                $slug = "{$base}-{$counter}";
                $counter++;
            }

            DB::table('cards')->where('id', $card->id)->update(['slug' => $slug]);
            $existingSlugs[] = $slug;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
