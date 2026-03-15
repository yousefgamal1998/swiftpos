<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryAdjustmentRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\InventoryService;
use App\Support\PosCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search']);

        $products = Product::query()
            ->with('category:id,name')
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                })
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $movements = InventoryMovement::query()
            ->with(['product:id,name,sku,unit', 'user:id,name'])
            ->latest()
            ->limit(50)
            ->get();

        return Inertia::render('Inventory/Index', [
            'products' => $products,
            'movements' => $movements,
            'filters' => $filters,
        ]);
    }

    public function adjust(
        InventoryAdjustmentRequest $request,
        Product $product,
        InventoryService $inventoryService
    ): RedirectResponse {
        $validated = $request->validated();

        $delta = (float) $validated['quantity'];
        if ($validated['direction'] === 'out') {
            $delta *= -1;
        }

        $inventoryService->adjustStock(
            product: $product,
            delta: $delta,
            movementType: $validated['movement_type'],
            actor: $request->user(),
            note: $validated['note'] ?? null,
        );

        PosCache::bumpProductsVersion();

        return redirect()
            ->route('inventory.index')
            ->with('success', "Inventory adjusted for {$product->name}.");
    }
}
