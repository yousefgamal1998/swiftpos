<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceCardController extends Controller
{
    public function show(string $slug): Response
    {
        $card = Card::where('slug', $slug)->firstOrFail();

        $children = Card::query()
            ->where('parent_id', $card->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get([
                'id',
                'slug',
                'title',
                'description',
                'image_path',
                'color',
                'route_name',
            ]);

        return Inertia::render('Marketplace/CardChildren', [
            'card' => [
                'id' => $card->id,
                'slug' => $card->slug,
                'title' => $card->title,
                'description' => $card->description,
                'image_path' => $card->image_path,
                'color' => $card->color,
            ],
            'children' => $children,
        ]);
    }

    public function products(string $slug): Response
    {
        $card = Card::where('slug', $slug)->firstOrFail();

        $products = Product::query()
            ->where('card_id', $card->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'description',
                'price',
                'image_path',
            ]);

        return Inertia::render('Marketplace/CardProducts', [
            'card' => $card,
            'products' => $products,
        ]);
    }
}
