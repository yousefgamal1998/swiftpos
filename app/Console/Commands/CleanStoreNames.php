<?php

namespace App\Console\Commands;

use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CleanStoreNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stores:clean-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize store names and slugs, ensuring uniqueness.';

    public function handle(): int
    {
        $stores = Store::query()->orderBy('id')->get();
        $usedNames = [];
        $usedSlugs = [];
        $updated = 0;

        foreach ($stores as $store) {
            $rawName = trim((string) ($store->name ?? ''));
            $name = $rawName !== '' ? $rawName : "Store {$store->id}";
            $baseName = $name;
            $counter = 2;

            while (in_array(mb_strtolower($name), $usedNames, true)) {
                $name = "{$baseName} ({$counter})";
                $counter++;
            }

            $usedNames[] = mb_strtolower($name);

            $baseSlug = Str::slug($name) ?: "store-{$store->id}";
            $slug = $baseSlug;
            $slugCounter = 2;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = "{$baseSlug}-{$slugCounter}";
                $slugCounter++;
            }

            $usedSlugs[] = $slug;

            $changes = [];
            if ($store->name !== $name) {
                $changes['name'] = $name;
            }
            if ($store->slug !== $slug) {
                $changes['slug'] = $slug;
            }

            if ($changes) {
                $store->update($changes);
                $updated++;
            }
        }

        $this->info("Updated {$updated} store(s).");

        return Command::SUCCESS;
    }
}
