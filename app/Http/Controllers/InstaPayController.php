<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InstaPayController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
    ) {}

    public function initiate(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $product = Product::where('id', $data['product_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $quantity = (int) ($data['quantity'] ?? 1);
        $accessToken = $user ? null : Str::random(40);

        $order = $this->orderService->createFromProduct($product, $quantity, $user, [
            'customer_name' => $user?->name,
            'payment_method' => 'instapay',
            'payment_status' => 'pending',
            'status' => 'pending',
            'expires_at' => $this->resolveExpiration(),
            'access_token' => $accessToken,
        ]);

        $redirectParams = ['order' => $order->id];
        if ($order->access_token) {
            $redirectParams['token'] = $order->access_token;
        }

        return response()->json([
            'redirect' => route('instapay.show', $redirectParams),
            'order_id' => $order->id,
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->ensureCanAccess($request, $order);

        $order->load('items');

        $instapayConfig = [
            'phone' => config('instapay.phone'),
            'account_name' => config('instapay.account_name'),
            'deeplink_base' => config('instapay.deeplink_base'),
        ];

        return Inertia::render('Payment/InstaPay', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
                'payment_status' => $order->payment_status,
                'status' => $order->status,
                'instapay_screenshot' => $order->instapay_screenshot,
                'placed_at' => $order->placed_at?->toISOString(),
                'expires_at' => $order->expires_at?->toISOString(),
                'access_token' => $order->user_id ? null : $order->access_token,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ]),
            ],
            'instapay' => $instapayConfig,
        ]);
    }

    public function confirmSent(Request $request, Order $order): \Illuminate\Http\RedirectResponse
    {
        $this->ensureCanAccess($request, $order);
        $expiredResponse = $this->handleExpiredOrder($order);
        if ($expiredResponse) {
            return $expiredResponse;
        }

        if (! in_array($order->payment_status, ['pending', 'unpaid'], true)) {
            return redirect()->route('instapay.show', $order->id)
                ->with('error', 'Payment has already been confirmed or processed.');
        }

        $order->update([
            'payment_status' => 'waiting_confirmation',
        ]);

        return redirect()->route('instapay.show', $order->id)
            ->with('success', 'Payment confirmation sent! We will verify your payment shortly.');
    }

    public function uploadScreenshot(Request $request, Order $order): \Illuminate\Http\RedirectResponse
    {
        $this->ensureCanAccess($request, $order);
        $expiredResponse = $this->handleExpiredOrder($order);
        if ($expiredResponse) {
            return $expiredResponse;
        }

        $request->validate([
            'screenshot' => ['required', 'image', 'max:5120'],
        ]);

        if ($order->instapay_screenshot) {
            Storage::disk('public')->delete($order->instapay_screenshot);
        }

        $path = $request->file('screenshot')->store('instapay-screenshots', 'public');

        $order->update([
            'instapay_screenshot' => $path,
        ]);

        if (in_array($order->payment_status, ['pending', 'unpaid'], true)) {
            $order->update([
                'payment_status' => 'waiting_confirmation',
            ]);
        }

        return redirect()->route('instapay.show', $order->id)
            ->with('success', 'Screenshot uploaded successfully!');
    }

    private function ensureCanAccess(Request $request, Order $order): void
    {
        if ($order->payment_method !== 'instapay') {
            abort(404);
        }

        $user = $request->user();

        if ($user && $order->user_id === $user->id) {
            return;
        }

        if ($user && $user->hasAnyRole(['admin', 'manager'])) {
            return;
        }

        if ($order->user_id === null && $this->hasValidAccessToken($request, $order)) {
            return;
        }

        abort(403);
    }

    private function handleExpiredOrder(Order $order): ?\Illuminate\Http\RedirectResponse
    {
        if ($this->isExpired($order)) {
            return redirect()->route('instapay.show', $order->id)
                ->with('error', 'This payment link has expired. Please initiate a new order.');
        }

        return null;
    }

    private function isExpired(Order $order): bool
    {
        return $order->expires_at !== null
            && $order->expires_at->isPast()
            && $order->payment_status !== 'paid';
    }

    private function hasValidAccessToken(Request $request, Order $order): bool
    {
        $token = (string) $request->query('token', '');
        $orderToken = (string) ($order->access_token ?? '');

        return $orderToken !== '' && hash_equals($orderToken, $token);
    }

    private function resolveExpiration(): ?\Illuminate\Support\Carbon
    {
        $minutes = (int) config('instapay.order_expiration_minutes', 0);

        return $minutes > 0 ? now()->addMinutes($minutes) : null;
    }
}
