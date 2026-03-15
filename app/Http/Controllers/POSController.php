<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Payment;
use App\Models\PosSession;
use App\Models\Product;
use App\Services\OrderService;
use App\Support\PosCache;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class POSController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $categoryId = $request->integer('category_id') ?: null;

        $products = Cache::remember(
            PosCache::productsKey($search, $categoryId),
            now()->addMinutes(2),
            fn () => Product::query()
                ->active()
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($subQuery) use ($search): void {
                        $subQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
                })
                ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
                ->orderBy('name')
                ->limit(250)
                ->get([
                    'id',
                    'category_id',
                    'sku',
                    'name',
                    'price',
                    'tax_rate',
                    'track_inventory',
                    'stock_quantity',
                    'unit',
                ])
        );

        $openSession = PosSession::query()
            ->where('user_id', $request->user()->id)
            ->open()
            ->latest('opened_at')
            ->first();

        $recentOrders = $request->user()
            ->orders()
            ->latest('placed_at')
            ->limit(8)
            ->get([
                'id',
                'order_number',
                'total_amount',
                'payment_status',
                'placed_at',
            ]);

        return Inertia::render('POS/Cashier', [
            'products' => $products,
            'categories' => \App\Models\Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'session' => $openSession,
            'recentOrders' => $recentOrders,
            'paymentMethods' => ['cash', 'card', 'wallet', 'bank_transfer', 'mixed'],
            'orderTypes' => ['retail', 'dine_in', 'takeaway', 'delivery'],
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
            ],
        ]);
    }

    public function openSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($request, $validated): void {
                $hasOpenSession = PosSession::query()
                    ->where('user_id', $request->user()->id)
                    ->open()
                    ->lockForUpdate()
                    ->exists();

                if ($hasOpenSession) {
                    throw new \RuntimeException('swiftpos.pos_session_open_exists');
                }

                PosSession::query()->create([
                    'user_id' => $request->user()->id,
                    'opened_at' => now(),
                    'opening_cash' => $validated['opening_cash'],
                    'status' => 'open',
                    'notes' => $validated['notes'] ?? null,
                ]);
            });
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'swiftpos.pos_session_open_exists') {
                return redirect()
                    ->route('pos.index')
                    ->with('error', 'You already have an open POS session.');
            }

            throw $exception;
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return redirect()
                    ->route('pos.index')
                    ->with('error', 'You already have an open POS session.');
            }

            throw $exception;
        }

        return redirect()
            ->route('pos.index')
            ->with('success', 'POS session opened.');
    }

    public function closeSession(Request $request, PosSession $session): RedirectResponse
    {
        if ($session->user_id !== $request->user()->id && ! $request->user()->hasAnyRole(['admin', 'manager'])) {
            abort(403);
        }

        if (! $session->isOpen()) {
            return redirect()
                ->route('pos.index')
                ->with('error', 'This session is already closed.');
        }

        $validated = $request->validate([
            'closing_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $expectedCash = Payment::query()
            ->whereIn('order_id', $session->orders()->select('id'))
            ->where('method', 'cash')
            ->where('status', 'completed')
            ->sum('amount') + (float) $session->opening_cash;

        $session->update([
            'closing_cash' => $validated['closing_cash'],
            'expected_cash' => round($expectedCash, 2),
            'closed_at' => now(),
            'status' => 'closed',
        ]);

        return redirect()
            ->route('pos.index')
            ->with('success', 'POS session closed.');
    }

    public function checkout(StoreOrderRequest $request, OrderService $orderService): RedirectResponse
    {
        $order = $orderService->checkout($request->validated(), $request->user());

        PosCache::bumpProductsVersion();

        return redirect()
            ->route('pos.index')
            ->with('success', "Order {$order->order_number} completed.")
            ->with('order', [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
                'paid_amount' => $order->paid_amount,
                'change_amount' => $order->change_amount,
            ]);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;

        return $sqlState === '23505'
            || ($sqlState === '23000' && in_array((int) $driverCode, [19, 1062], true));
    }
}
