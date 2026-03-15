<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequest;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        $categoryId = $request->integer('category');

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $stores = Store::query()
            ->with(['category:id,name'])
            ->withCount('products')
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->get([
                'id',
                'category_id',
                'name',
                'description',
                'image',
                'image_path',
            ]);

        return Inertia::render('Admin/Stores/Index', [
            'stores' => $stores,
            'categories' => $categories,
            'filters' => [
                'category' => $categoryId,
            ],
        ]);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        $data = $this->handleImageUpload($request, $request->validated());

        Store::create($data);

        return redirect()
            ->back()
            ->with('success', 'Store created.');
    }

    public function update(StoreRequest $request, Store $store): RedirectResponse
    {
        $data = $this->handleImageUpload($request, $request->validated(), $store);

        $store->update($data);

        return redirect()
            ->back()
            ->with('success', 'Store updated.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        if ($store->image_path) {
            Storage::disk('public')->delete($store->image_path);
        }

        $store->delete();

        return redirect()
            ->back()
            ->with('success', 'Store deleted.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function handleImageUpload(Request $request, array $data, ?Store $store = null): array
    {
        unset($data['image']);

        if (! $request->hasFile('image')) {
            return $data;
        }

        if ($store?->image_path) {
            Storage::disk('public')->delete($store->image_path);
        }

        $data['image_path'] = $request->file('image')->store('stores', 'public');

        return $data;
    }
}
