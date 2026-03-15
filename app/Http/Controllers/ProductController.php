<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Card;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\ImageService;
use App\Support\PosCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category_id', 'status']);

        $products = Product::query()
            ->with('category:id,name')
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query
                    ->where(function ($subQuery) use ($search): void {
                        $subQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    })
            )
            ->when(
                $filters['category_id'] ?? null,
                fn ($query, $categoryId) => $query->where('category_id', $categoryId)
            )
            ->when(
                ($filters['status'] ?? 'all') !== 'all',
                fn ($query) => $query->where('is_active', ($filters['status'] ?? null) === 'active')
            )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Products/Create', [
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreProductRequest $request,
        InventoryService $inventoryService,
        ImageService $imageService
    ): RedirectResponse
    {
        $validated = $request->validated();
        $initialStock = (float) ($validated['stock_quantity'] ?? 0);
        $imagePath = $imageService->storeProductImage($request->file('image'));

        $payload = [
            ...$validated,
            'slug' => $this->resolveUniqueSlug($validated['name']),
            'stock_quantity' => 0,
        ];

        unset($payload['image']);

        if (! empty($payload['card_id'])) {
            $card = Card::query()->find($payload['card_id']);
            if ($card) {
                $payload['store_id'] = $card->effectiveStoreId() ?: null;
            }
        }

        if (! Schema::hasColumn('products', 'card_id')) {
            unset($payload['card_id']);
        }

        if ($imagePath) {
            $payload['image_path'] = $imagePath;
        }

        $product = Product::query()->create($payload);

        if ($product->track_inventory && $initialStock > 0) {
            $inventoryService->adjustStock(
                product: $product,
                delta: $initialStock,
                movementType: 'restock',
                actor: $request->user(),
                note: 'Initial stock',
            );
        }

        PosCache::bumpProductsVersion();

        $redirectTo = $request->input('redirect_to');
        if (
            $request->boolean('marketplace_simple')
            && is_string($redirectTo)
            && Str::startsWith($redirectTo, '/cards/')
        ) {
            return redirect()
                ->to($redirectTo)
                ->with('success', "Product {$product->name} created.");
        }

        if ($request->boolean('marketplace_simple')) {
            return redirect()
                ->route('admin.marketplace.index')
                ->with('success', "Product {$product->name} created.");
        }

        return redirect()
            ->route('products.index')
            ->with('success', "Product {$product->name} created.");
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): void
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Edit', [
            'product' => $product->only([
                'id',
                'name',
                'sku',
                'barcode',
                'category_id',
                'type',
                'description',
                'price',
                'cost',
                'tax_rate',
                'track_inventory',
                'stock_quantity',
                'low_stock_threshold',
                'unit',
                'is_active',
            ]),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateProductRequest $request,
        Product $product,
        ImageService $imageService
    ): RedirectResponse
    {
        $validated = $request->validated();
        $imagePath = $imageService->replaceProductImage($product->image_path, $request->file('image'));

        $payload = [
            ...$validated,
            'slug' => $this->resolveUniqueSlug($validated['name'], $product->id),
            'image_path' => $imagePath,
        ];

        unset($payload['image']);

        $product->update($payload);

        PosCache::bumpProductsVersion();

        return redirect()
            ->route('products.index')
            ->with('success', "Product {$product->name} updated.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            $product->update(['is_active' => false]);

            PosCache::bumpProductsVersion();

            return redirect()
                ->route('products.index')
                ->with('success', "Product {$product->name} was archived because it has linked orders.");
        }

        app(ImageService::class)->deleteProductImage($product->image_path);

        $product->delete();
        PosCache::bumpProductsVersion();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted.');
    }

    protected function resolveUniqueSlug(string $name, ?int $ignoreProductId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
            Product::query()
                ->where('slug', $slug)
                ->when($ignoreProductId, fn ($query) => $query->where('id', '!=', $ignoreProductId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

}
