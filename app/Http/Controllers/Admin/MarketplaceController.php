<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Card;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MarketplaceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Category::class);
        $this->authorize('viewAny', Card::class);

        $categories = Category::query()
            ->withCount('cards')
            ->with([
                'cards' => function ($query) {
                    $query->orderBy('sort_order')->select([
                        'id',
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
                    ]);
                },
            ])
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'is_active',
            ]);

        $unassignedCards = Card::query()
            ->whereNull('category_id')
            ->orderBy('sort_order')
            ->get([
                'id',
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
            ]);

        $productFilters = [
            'search' => $request->string('product_search')->toString(),
            'store' => $request->integer('store') ?: null,
        ];

        $products = Product::query()
            ->with('store:id,name')
            ->when(
                $productFilters['search'] !== '',
                fn ($query) => $query->where('name', 'like', '%'.$productFilters['search'].'%')
            )
            ->when(
                $productFilters['store'],
                fn ($query) => $query->where('store_id', $productFilters['store'])
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stores = Store::query()
            ->select(['id', 'name', 'image_path'])
            ->withCount('products')
            ->with([
                'cards' => function ($query) {
                    $query->orderBy('sort_order')->select([
                        'id',
                        'store_id',
                        'parent_id',
                        'title',
                    ])->with('parent:id,title');
                },
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Store $store) {
                $card = $store->cards->first();

                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'store_name' => $store->name,
                    'card_title' => $card?->title,
                    'parent_title' => $card?->parent?->title,
                    'image_path' => $store->image_path,
                    'products_count' => $store->products_count,
                ];
            });

        $storesByCategory = Store::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'category_id']);

        // Build list of leaf cards (cards without children) and their hierarchical path.
        $allCards = Card::query()
            ->whereNotNull('category_id')
            ->select(['id', 'title', 'parent_id', 'category_id'])
            ->with('category:id,name')
            ->orderBy('sort_order')
            ->get();

        $cardsById = $allCards->keyBy('id');
        $parentIds = $allCards
            ->pluck('parent_id')
            ->filter()
            ->unique()
            ->flip();

        $leafCards = $allCards
            ->filter(fn (Card $card) => ! $parentIds->has($card->id))
            ->map(function (Card $card) use ($cardsById) {
                $parts = [];
                $cursor = $card;
                $depth = 0;

                while ($cursor && $depth < 25) {
                    array_unshift($parts, $cursor->title);
                    $cursor = $cardsById->get($cursor->parent_id);
                    $depth++;
                }

                if ($card->category?->name) {
                    array_unshift($parts, $card->category->name);
                }

                return [
                    'id' => $card->id,
                    'title' => $card->title,
                    'path' => implode(' → ', $parts),
                ];
            })
            ->values();

        return Inertia::render('Admin/Marketplace', [
            'categories' => $categories,
            'unassignedCards' => $unassignedCards,
            'stores' => $stores,
            'leafCards' => $leafCards,
            'storesByCategory' => $storesByCategory,
            'products' => $products,
            'productFilters' => $productFilters,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'permissions' => Permission::query()->orderBy('name')->pluck('name'),
            'colors' => $this->availableColors(),
        ]);
    }

    public function storeCategory(CategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $data = $request->validated();
        $data['slug'] = $this->makeSlug($data['slug'] ?? $data['name']);

        Category::create($data);

        return redirect()
            ->back()
            ->with('success', 'Category created.');
    }

    public function updateCategory(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $data = $request->validated();
        $data['slug'] = $this->makeSlug($data['slug'] ?? $data['name'], $category->id);

        $category->update($data);

        return redirect()
            ->back()
            ->with('success', 'Category updated.');
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return redirect()
            ->back()
            ->with('success', 'Category deleted.');
    }

    public function storeCard(Request $request): RedirectResponse
    {
        $this->authorize('create', Card::class);

        $data = $this->validateCard($request);
        $data = $this->normalizeCardData($data);
        $data = $this->handleImageUpload($request, $data);

        Card::create($data);

        return redirect()
            ->back()
            ->with('success', 'Card created.');
    }

    public function updateCard(Request $request, Card $card): RedirectResponse
    {
        $this->authorize('update', $card);

        $data = $this->validateCard($request, $card);
        $data = $this->normalizeCardData($data, $card);
        $data = $this->handleImageUpload($request, $data, $card);

        $card->update($data);

        return redirect()
            ->back()
            ->with('success', 'Card updated.');
    }

    public function destroyCard(Card $card): RedirectResponse
    {
        $this->authorize('delete', $card);

        if ($card->image_path) {
            Storage::disk('public')->delete($card->image_path);
        }

        $card->delete();

        return redirect()
            ->back()
            ->with('success', 'Card deleted.');
    }

    public function reorderCards(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Card::class);

        $payload = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'integer', 'exists:cards,id'],
            'order.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        $categoryId = (int) $payload['category_id'];

        DB::transaction(function () use ($payload, $categoryId): void {
            foreach ($payload['order'] as $item) {
                Card::whereKey($item['id'])
                    ->where('category_id', $categoryId)
                    ->update([
                        'sort_order' => $item['sort_order'],
                    ]);
            }
        });

        return redirect()
            ->back()
            ->with('success', 'Card order updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCard(Request $request, ?Card $card = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('cards', 'id')->where(
                    fn ($query) => $query->where('category_id', $request->input('category_id'))
                ),
            ],
            'store_id' => ['nullable', 'integer', Rule::exists('stores', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'color' => ['required', 'string', Rule::in($this->availableColors())],
            'route_name' => ['nullable', 'string', 'max:255'],
            'permission' => ['nullable', 'string', Rule::exists('permissions', 'name')],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'remove_image' => ['boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCardData(array $data, ?Card $card = null): array
    {
        $data['permission'] = $data['permission'] ?: null;
        $data['role'] = $data['role'] ?: null;
        $data['parent_id'] = $data['parent_id'] ?: null;
        $data['store_id'] = $data['store_id'] ?: null;

        $data['route_name'] = 'cards.show';

        if ($card && $data['parent_id'] && (int) $data['parent_id'] === $card->id) {
            $data['parent_id'] = null;
        }

        if (! $card || ! $card->slug) {
            $data['slug'] = $this->makeCardSlug($data['title'], $card?->id);
        }

        if (! array_key_exists('icon', $data)) {
            if (! $card) {
                $data['icon'] = '';
            }
        } else {
            $data['icon'] = $data['icon'] ?: '';
        }

        if (empty($data['sort_order'])) {
            if ($card && $card->category_id === (int) $data['category_id']) {
                $data['sort_order'] = $card->sort_order ?: 1;
            } else {
                $data['sort_order'] = (int) (Card::query()
                    ->where('category_id', $data['category_id'])
                    ->max('sort_order') ?? 0) + 1;
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function handleImageUpload(Request $request, array $data, ?Card $card = null): array
    {
        $removeImage = (bool) ($data['remove_image'] ?? false);

        unset($data['image'], $data['remove_image']);

        if ($removeImage && $card?->image_path) {
            Storage::disk('public')->delete($card->image_path);
            $data['image_path'] = null;
        }

        if (! $request->hasFile('image')) {
            return $data;
        }

        if ($card?->image_path) {
            Storage::disk('public')->delete($card->image_path);
        }

        $data['image_path'] = $request->file('image')->store('cards', 'public');

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function availableColors(): array
    {
        return [
            'emerald',
            'sky',
            'amber',
            'violet',
            'slate',
        ];
    }

    private function makeCardSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $counter = 1;

        while (
            Card::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function makeSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $counter = 1;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
