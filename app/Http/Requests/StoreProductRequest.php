<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isMarketplaceSimple = $this->boolean('marketplace_simple');

        return [
            'store_id' => [
                Rule::requiredIf($isMarketplaceSimple && ! $this->filled('card_id')),
                'nullable',
                'integer',
                'exists:stores,id',
            ],
            'card_id' => ['nullable', 'integer', 'exists:cards,id'],
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')],
            'barcode' => ['nullable', 'string', 'max:64', 'unique:products,barcode'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['required', 'string', 'in:retail,restaurant'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit' => ['required', 'string', 'max:20'],
            'track_inventory' => ['boolean'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'meta' => ['nullable', 'array'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $isMarketplaceSimple = $this->boolean('marketplace_simple');
        $sku = $this->input('sku');

        if ($isMarketplaceSimple && (! is_string($sku) || trim($sku) === '')) {
            $this->merge([
                'sku' => $this->generateSku($this->input('name')),
            ]);
        }

        $this->merge([
            'track_inventory' => $this->boolean('track_inventory'),
            'is_active' => $this->boolean('is_active', true),
            'stock_quantity' => $this->input('stock_quantity', 0),
            'tax_rate' => $this->input('tax_rate', 0),
            'low_stock_threshold' => $this->input('low_stock_threshold', 0),
            'type' => $this->input('type', $isMarketplaceSimple ? 'restaurant' : 'retail'),
            'unit' => $this->input('unit', 'pcs'),
        ]);
    }

    private function generateSku(?string $seed = null): string
    {
        $baseSku = $seed ? Str::upper(Str::slug($seed, '')) : Str::upper(Str::random(8));
        $baseSku = $baseSku ?: Str::upper(Str::random(8));
        $sku = $baseSku;
        $counter = 2;

        while (Product::query()->where('sku', $sku)->exists()) {
            $sku = "{$baseSku}-{$counter}";
            $counter++;
        }

        return $sku;
    }
}
