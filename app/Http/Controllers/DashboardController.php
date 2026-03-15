<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Card;
use App\Models\PosSession;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $canManageAllOrders = $user->hasAnyRole(['admin', 'manager']);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description'])
            ->map(function (Category $category) use ($user) {
                $cards = Card::query()
                    ->where('category_id', $category->id)
                    ->where('is_active', true)
                    ->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->get([
                        'id',
                        'slug',
                        'title',
                        'description',
                        'icon',
                        'image_path',
                        'color',
                        'route_name',
                        'permission',
                        'role',
                    ])
                    ->filter(function (Card $card) use ($user): bool {
                        if ($card->permission) {
                            return $user->can($card->permission);
                        }

                        if ($card->role) {
                            return $user->hasRole($card->role);
                        }

                        return true;
                    })
                    ->values()
                    ->map(fn (Card $card) => [
                        'id' => $card->id,
                        'slug' => $card->slug,
                        'title' => $card->title,
                        'description' => $card->description,
                        'icon' => $card->icon,
                        'image_path' => $card->image_path,
                        'color' => $card->color,
                        'route_name' => $card->route_name,
                    ]);

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'cards' => $cards,
                ];
            })
            ->filter(fn (array $cat) => $cat['cards']->isNotEmpty())
            ->values();

        $ordersBaseQuery = Order::query()
            ->where('status', 'completed')
            ->when(
                ! $canManageAllOrders,
                fn ($query) => $query->where('user_id', $user->id)
            );

        $todaySales = (clone $ordersBaseQuery)
            ->whereDate('placed_at', now()->toDateString())
            ->sum('total_amount');

        $monthSales = (clone $ordersBaseQuery)
            ->whereYear('placed_at', now()->year)
            ->whereMonth('placed_at', now()->month)
            ->sum('total_amount');

        $ordersToday = (clone $ordersBaseQuery)
            ->whereDate('placed_at', now()->toDateString())
            ->count();

        $recentOrders = (clone $ordersBaseQuery)
            ->with('user:id,name')
            ->latest('placed_at')
            ->limit(6)
            ->get([
                'id',
                'order_number',
                'user_id',
                'total_amount',
                'payment_status',
                'placed_at',
            ]);

        $lowStockProducts = Product::query()
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->orderBy('stock_quantity')
            ->limit(8)
            ->get([
                'id',
                'name',
                'sku',
                'stock_quantity',
                'low_stock_threshold',
                'unit',
            ]);

        return Inertia::render('Dashboard', [
            'categories' => $categories,
            'stats' => [
                'sales_today' => (float) $todaySales,
                'sales_month' => (float) $monthSales,
                'orders_today' => $ordersToday,
                'open_sessions' => PosSession::query()->open()->count(),
                'low_stock_count' => $lowStockProducts->count(),
            ],
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
