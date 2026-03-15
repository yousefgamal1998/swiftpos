<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class CartController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        return response()->json($this->payload($cart));
    }

    public function storeItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $cart = $this->resolveCart($request);
        $quantity = (int) ($data['quantity'] ?? 1);

        $updated = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $data['product_id'])
            ->increment('quantity', $quantity);

        if ($updated === 0) {
            try {
                CartItem::query()->create([
                    'cart_id' => $cart->id,
                    'product_id' => $data['product_id'],
                    'quantity' => $quantity,
                ]);
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }

                CartItem::query()
                    ->where('cart_id', $cart->id)
                    ->where('product_id', $data['product_id'])
                    ->increment('quantity', $quantity);
            }
        }

        return response()->json($this->payload($cart));
    }

    public function updateItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $this->ensureCartOwner($request, $cartItem);

        if ($data['quantity'] <= 0) {
            $cart = $cartItem->cart;
            $cartItem->delete();

            return response()->json($this->payload($cart));
        }

        $cartItem->update(['quantity' => $data['quantity']]);

        return response()->json($this->payload($cartItem->cart));
    }

    public function destroyItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->ensureCartOwner($request, $cartItem);

        $cart = $cartItem->cart;
        $cartItem->delete();

        return response()->json($this->payload($cart));
    }

    public function checkout(Request $request, OrderService $orderService): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $orders = $orderService->checkoutMarketplace($cart, $request->user());

        $primaryOrder = $orders[0] ?? null;

        return response()->json([
            'order_id' => $primaryOrder?->id,
            'order_ids' => collect($orders)->pluck('id')->values(),
        ]);
    }

    private function resolveCart(Request $request): Cart
    {
        $user = $request->user();

        return Cart::query()->firstOrCreate(['user_id' => $user->id]);
    }

    private function ensureCartOwner(Request $request, CartItem $cartItem): void
    {
        $user = $request->user();

        if (! $user || $cartItem->cart->user_id !== $user->id) {
            abort(403);
        }
    }

    /**
     * @return array{items: array<int, array<string, mixed>>}
     */
    private function payload(?Cart $cart): array
    {
        if (! $cart) {
            return ['items' => []];
        }

        $cart->loadMissing('items.product');

        return [
            'items' => $cart->items->filter(fn (CartItem $item) => $item->product !== null)->map(fn (CartItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'description' => $item->product->description,
                    'price' => $item->product->price,
                ],
            ])->values()->all(),
        ];
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;

        return $sqlState === '23505'
            || ($sqlState === '23000' && in_array((int) $driverCode, [19, 1062], true));
    }
}
