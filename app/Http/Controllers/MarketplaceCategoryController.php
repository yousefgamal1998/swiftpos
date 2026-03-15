<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceCategoryController extends Controller
{
    public function show(Category $category): Response
    {
        $category->load([
            'cards' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->select([
                    'id',
                    'slug',
                    'title',
                    'description',
                    'image_path',
                    'color',
                    'route_name',
                    'permission',
                    'role',
                ]);
            },
        ]);

        return Inertia::render('Marketplace/CategoryShow', [
            'category' => $category->only([
                'id',
                'name',
                'slug',
                'description',
            ]),
            'cards' => $category->cards,
        ]);
    }
}
