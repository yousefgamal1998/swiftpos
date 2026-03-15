<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $canManageAllOrders = $user->hasAnyRole(['admin', 'manager']);
        $filters = $request->only(['search', 'status', 'payment_status', 'from', 'to']);

        $orders = Order::query()
            ->with('user:id,name')
            ->when(
                ! $canManageAllOrders,
                fn ($query) => $query->where('user_id', $user->id)
            )
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where('order_number', 'like', "%{$search}%")
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['payment_status'] ?? null,
                fn ($query, $status) => $query->where('payment_status', $status)
            )
            ->when(
                $filters['from'] ?? null,
                fn ($query, $from) => $query->whereDate('placed_at', '>=', $from)
            )
            ->when(
                $filters['to'] ?? null,
                fn ($query, $to) => $query->whereDate('placed_at', '<=', $to)
            )
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => ['pending', 'confirmed', 'completed', 'cancelled', 'refunded'],
            'paymentStatuses' => ['unpaid', 'partial', 'paid', 'refunded'],
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $canManageAllOrders = $request->user()->hasAnyRole(['admin', 'manager']);

        if (! $canManageAllOrders && $order->user_id !== $request->user()->id) {
            abort(403);
        }

        $order->load([
            'user:id,name,email',
            'items.product:id,name,sku,unit',
            'payments.user:id,name',
            'posSession:id,user_id,opened_at,closed_at',
        ]);

        return Inertia::render('Orders/Show', [
            'order' => $order,
        ]);
    }
}
