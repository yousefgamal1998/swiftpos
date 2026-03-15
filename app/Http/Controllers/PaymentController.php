<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'method', 'from', 'to']);

        $payments = Payment::query()
            ->with([
                'order:id,order_number',
                'user:id,name',
            ])
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where(function ($query) use ($search) {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('order', fn ($query) => $query->where('order_number', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                })
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['method'] ?? null,
                fn ($query, $method) => $query->where('method', $method)
            )
            ->when(
                $filters['from'] ?? null,
                fn ($query, $from) => $query->whereDate('paid_at', '>=', $from)
            )
            ->when(
                $filters['to'] ?? null,
                fn ($query, $to) => $query->whereDate('paid_at', '<=', $to)
            )
            ->latest('paid_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'filters' => $filters,
            'methods' => Payment::query()
                ->select('method')
                ->distinct()
                ->orderBy('method')
                ->pluck('method'),
            'statuses' => Payment::query()
                ->select('status')
                ->distinct()
                ->orderBy('status')
                ->pluck('status'),
        ]);
    }
}
