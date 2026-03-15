<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\Card;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = $request->integer('store');

        $stores = Store::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'category_id']);

        $products = Product::query()
            ->with(['store:id,name,category_id'])
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->orderBy('name')
            ->get([
                'id',
                'store_id',
                'sku',
                'name',
                'description',
                'price',
                'is_active',
            ]);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'stores' => $stores,
            'filters' => [
                'store' => $storeId,
            ],
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->makeSlug($data['name']);

        // If a card_id is provided, attach it and set store_id from the card's effective store
        if (! empty($data['card_id'])) {
            $card = Card::query()->find($data['card_id']);
            if ($card) {
                $data['store_id'] = $card->effectiveStoreId() ?: null;
            }
        }

        if (! Schema::hasColumn('products', 'card_id')) {
            unset($data['card_id']);
        }

        Product::create($data);

        return redirect()
            ->back()
            ->with('success', 'Product created.');
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->makeSlug($data['name'], $product->id);

        if (! Schema::hasColumn('products', 'card_id')) {
            unset($data['card_id']);
        }

        $product->update($data);

        return redirect()
            ->back()
            ->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->back()
            ->with('success', 'Product deleted.');
    }

    private function makeSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $counter = 1;

        while (
            Product::query()
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
