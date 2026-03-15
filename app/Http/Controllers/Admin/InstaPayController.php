<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstaPayController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->query('filter', 'pending');

        $query = Order::query()
            ->where('payment_method', 'instapay')
            ->with('user:id,name,email')
            ->latest('placed_at');

        if ($filter === 'pending') {
            $query->whereIn('payment_status', ['pending', 'waiting_confirmation']);
        }

        $orders = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/InstaPayPayments', [
            'orders' => $orders,
            'filter' => $filter,
        ]);
    }

    public function approve(Request $request, Order $order): \Illuminate\Http\RedirectResponse
    {
        if ($order->payment_method !== 'instapay') {
            abort(422, 'This order does not use InstaPay.');
        }

        if ($order->payment_status === 'paid') {
            return redirect()->route('admin.instapay.index')
                ->with('error', 'This payment has already been approved.');
        }

        $order->update([
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'paid_amount' => $order->total_amount,
        ]);

        $order->payments()->create([
            'user_id' => $order->user_id ?? $request->user()->id,
            'method' => 'instapay',
            'amount' => $order->total_amount,
            'status' => 'completed',
            'reference' => 'ORDER-' . $order->id,
            'paid_at' => now(),
            'notes' => 'InstaPay payment approved by ' . $request->user()->name,
        ]);

        return redirect()->route('admin.instapay.index')
            ->with('success', "Payment for Order #{$order->order_number} approved.");
    }

    public function reject(Request $request, Order $order): \Illuminate\Http\RedirectResponse
    {
        if ($order->payment_method !== 'instapay') {
            abort(422, 'This order does not use InstaPay.');
        }

        if (in_array($order->payment_status, ['paid', 'rejected'], true)) {
            return redirect()->route('admin.instapay.index')
                ->with('error', 'This payment cannot be rejected.');
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $order->update([
            'payment_status' => 'rejected',
            'status' => 'cancelled',
            'notes' => $request->input('reason')
                ? "Payment rejected: {$request->input('reason')}"
                : 'Payment rejected by admin',
        ]);

        return redirect()->route('admin.instapay.index')
            ->with('success', "Payment for Order #{$order->order_number} rejected.");
    }
}
