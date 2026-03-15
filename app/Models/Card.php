<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    /** @use HasFactory<\Database\Factories\CardFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'parent_id',
        'store_id',
        'slug',
        'title',
        'description',
        'icon',
        'image_path',
        'color',
        'route_name',
        'permission',
        'role',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'parent_id' => 'integer',
            'store_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return HasMany<Card>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function effectiveStoreId(int $maxDepth = 10): ?int
    {
        if ($this->store_id) {
            return (int) $this->store_id;
        }

        $cursor = $this;
        $depth = 0;

        while ($cursor->parent_id && $depth < $maxDepth) {
            $cursor->loadMissing('parent:id,parent_id,store_id');
            $cursor = $cursor->parent;

            if (! $cursor) {
                break;
            }

            if ($cursor->store_id) {
                return (int) $cursor->store_id;
            }

            $depth++;
        }

        return null;
    }
}
