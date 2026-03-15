<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class PosCache
{
    public const VERSION_KEY = 'swiftpos.products.version';

    public static function productsKey(?string $search = null, ?int $categoryId = null): string
    {
        $version = Cache::get(self::VERSION_KEY, 1);

        return sprintf(
            'swiftpos.products.%d.%s',
            $version,
            md5(sprintf('%s|%s', $search ?? '', $categoryId ?? 'all'))
        );
    }

    public static function bumpProductsVersion(): void
    {
        if (! Cache::add(self::VERSION_KEY, 1, now()->addYear())) {
            Cache::increment(self::VERSION_KEY);
        }
    }
}
