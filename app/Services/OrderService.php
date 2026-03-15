<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    private const MONEY_SCALE = 2;

    private const QUANTITY_SCALE = 3;

    private const TAX_RATE_SCALE = 2;

    public function __construct(
        protected InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function checkout(array $payload, User $actor): Order
    {
        return DB::transaction(function () use ($payload, $actor): Order {
            /** @var PosSession $session */
            $session = PosSession::query()
                ->lockForUpdate()
                ->findOrFail((int) $payload['pos_session_id']);

            if (! $session->isOpen()) {
                throw ValidationException::withMessages([
                    'pos_session_id' => 'The selected POS session is closed.',
                ]);
            }

            if ($session->user_id !== $actor->id && ! $actor->hasAnyRole(['admin', 'manager'])) {
                throw ValidationException::withMessages([
                    'pos_session_id' => 'You cannot use another cashier session.',
                ]);
            }

            $itemsPayload = collect($payload['items']);
            $productIds = $itemsPayload->pluck('product_id')->unique()->values();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalCents = 0;
            $itemsDiscountCents = 0;
            $itemsTaxCents = 0;

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $actor->id,
                'pos_session_id' => $session->id,
                'status' => 'completed',
                'order_type' => $payload['order_type'],
                'customer_name' => $payload['customer_name'] ?? null,
                'subtotal' => $this->formatMoney(0),
                'discount_amount' => $this->formatMoney(0),
                'tax_amount' => $this->formatMoney(0),
                'total_amount' => $this->formatMoney(0),
                'paid_amount' => $this->formatMoney(0),
                'change_amount' => $this->formatMoney(0),
                'payment_status' => 'unpaid',
                'notes' => $payload['notes'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($itemsPayload as $item) {
                /** @var Product|null $product */
                $product = $products->get((int) $item['product_id']);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more selected products are unavailable.',
                    ]);
                }

                $quantityMillis = $this->toScaledInt($item['quantity'], self::QUANTITY_SCALE);
                if ($quantityMillis <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Invalid product quantity provided.',
                    ]);
                }

                $quantity = $this->formatScaled($quantityMillis, self::QUANTITY_SCALE);
                $unitPriceCents = $this->toScaledInt((string) $product->price, self::MONEY_SCALE);
                $taxRateBasisPoints = $this->toScaledInt((string) $product->tax_rate, self::TAX_RATE_SCALE);
                $lineDiscountCents = 0;

                $lineSubtotalCents = $this->roundDiv(
                    $quantityMillis * $unitPriceCents,
                    10 ** self::QUANTITY_SCALE
                );
                $taxableAmountCents = max($lineSubtotalCents - $lineDiscountCents, 0);
                $lineTaxCents = $this->roundDiv(
                    $taxableAmountCents * $taxRateBasisPoints,
                    10 ** (self::TAX_RATE_SCALE + 2)
                );
                $lineTotalCents = $taxableAmountCents + $lineTaxCents;

                $subtotalCents += $lineSubtotalCents;
                $itemsDiscountCents += $lineDiscountCents;
                $itemsTaxCents += $lineTaxCents;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $this->formatMoney($unitPriceCents),
                    'tax_rate' => $this->formatScaled($taxRateBasisPoints, self::TAX_RATE_SCALE),
                    'tax_amount' => $this->formatMoney($lineTaxCents),
                    'discount_amount' => $this->formatMoney($lineDiscountCents),
                    'line_total' => $this->formatMoney($lineTotalCents),
                    'meta' => [
                        'unit' => $product->unit,
                        'product_type' => $product->type,
                    ],
                ]);

                if ($product->track_inventory) {
                    $this->inventoryService->adjustStock(
                        product: $product,
                        delta: -((float) $quantity),
                        movementType: 'sale',
                        actor: $actor,
                        note: "Order {$order->order_number}",
                        reference: $order
                    );
                }
            }

            $orderDiscountCents = 0;
            $totalDiscountCents = $itemsDiscountCents + $orderDiscountCents;
            $totalAmountCents = max($subtotalCents - $totalDiscountCents + $itemsTaxCents, 0);
            $amountTenderedCents = max(
                $this->toScaledInt($payload['amount_tendered'] ?? 0, self::MONEY_SCALE),
                0
            );
            $paidAmountCents = min($amountTenderedCents, $totalAmountCents);
            $changeAmountCents = max($amountTenderedCents - $totalAmountCents, 0);

            $paymentStatus = 'unpaid';
            if ($totalAmountCents === 0 || $paidAmountCents >= $totalAmountCents) {
                $paymentStatus = 'paid';
            } elseif ($paidAmountCents > 0) {
                $paymentStatus = 'partial';
            }

            $order->update([
                'subtotal' => $this->formatMoney($subtotalCents),
                'discount_amount' => $this->formatMoney($totalDiscountCents),
                'tax_amount' => $this->formatMoney($itemsTaxCents),
                'total_amount' => $this->formatMoney($totalAmountCents),
                'paid_amount' => $this->formatMoney($paidAmountCents),
                'change_amount' => $this->formatMoney($changeAmountCents),
                'payment_status' => $paymentStatus,
            ]);

            if ($paidAmountCents > 0) {
                $order->payments()->create([
                    'user_id' => $actor->id,
                    'method' => $payload['payment_method'],
                    'amount' => $this->formatMoney($paidAmountCents),
                    'status' => 'completed',
                    'reference' => $payload['payment_reference'] ?? null,
                    'paid_at' => now(),
                    'notes' => null,
                ]);
            }

            return $order->load(['items', 'payments', 'user']);
        });
    }

    /**
     * @return array<int, Order>
     */
    public function checkoutMarketplace(Cart $cart, User $actor): array
    {
        return DB::transaction(function () use ($cart, $actor): array {
            $cart->loadMissing('items.product');

            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty.',
                ]);
            }

            $orders = [];
            $grouped = $cart->items->groupBy(fn (CartItem $item) => $item->product?->store_id);

            foreach ($grouped as $storeId => $items) {
                $order = Order::query()->create([
                    'order_number' => $this->generateOrderNumber(),
                    'user_id' => $cart->user_id,
                    'pos_session_id' => null,
                    'store_id' => $storeId ?: null,
                    'status' => 'pending',
                    'order_type' => 'marketplace',
                    'customer_name' => null,
                    'subtotal' => $this->formatMoney(0),
                    'discount_amount' => $this->formatMoney(0),
                    'tax_amount' => $this->formatMoney(0),
                    'total_amount' => $this->formatMoney(0),
                    'paid_amount' => $this->formatMoney(0),
                    'change_amount' => $this->formatMoney(0),
                    'payment_status' => 'unpaid',
                    'notes' => null,
                    'placed_at' => now(),
                ]);

                $subtotalCents = 0;

                foreach ($items as $item) {
                    $product = $item->product;
                    if (! $product || ! $product->is_active) {
                        throw ValidationException::withMessages([
                            'cart' => 'One or more items are no longer available.',
                        ]);
                    }

                    $quantityMillis = $this->toScaledInt((string) $item->quantity, self::QUANTITY_SCALE);
                    if ($quantityMillis <= 0) {
                        throw ValidationException::withMessages([
                            'cart' => 'Invalid quantity selected.',
                        ]);
                    }

                    $quantity = $this->formatScaled($quantityMillis, self::QUANTITY_SCALE);
                    $unitPriceCents = $this->toScaledInt((string) $product->price, self::MONEY_SCALE);
                    $lineTotalCents = $this->roundDiv(
                        $quantityMillis * $unitPriceCents,
                        10 ** self::QUANTITY_SCALE
                    );
                    $subtotalCents += $lineTotalCents;

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'quantity' => $quantity,
                        'unit_price' => $this->formatMoney($unitPriceCents),
                        'tax_rate' => $this->formatMoney(0),
                        'tax_amount' => $this->formatMoney(0),
                        'discount_amount' => $this->formatMoney(0),
                        'line_total' => $this->formatMoney($lineTotalCents),
                        'meta' => [
                            'store_id' => $product->store_id,
                        ],
                    ]);

                    if ($product->track_inventory) {
                        $this->inventoryService->adjustStock(
                            product: $product,
                            delta: -((float) $quantity),
                            movementType: 'sale',
                            actor: $actor,
                            note: "Order {$order->order_number}",
                            reference: $order
                        );
                    }
                }

                $order->update([
                    'subtotal' => $this->formatMoney($subtotalCents),
                    'total_amount' => $this->formatMoney($subtotalCents),
                ]);

                $orders[] = $order;
            }

            $cart->items()->delete();

            return $orders;
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function createFromProduct(Product $product, int $quantity, ?User $actor, array $overrides = []): Order
    {
        return DB::transaction(function () use ($product, $quantity, $actor, $overrides): Order {
            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'This product is no longer available.',
                ]);
            }

            $quantityMillis = $quantity * (10 ** self::QUANTITY_SCALE);
            $unitPriceCents = $this->toScaledInt((string) $product->price, self::MONEY_SCALE);
            $lineTotalCents = $this->roundDiv(
                $quantityMillis * $unitPriceCents,
                10 ** self::QUANTITY_SCALE
            );

            $order = Order::query()->create(array_merge([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $actor?->id,
                'pos_session_id' => null,
                'store_id' => $product->store_id,
                'status' => 'pending',
                'order_type' => 'marketplace',
                'customer_name' => $actor?->name,
                'subtotal' => $this->formatMoney($lineTotalCents),
                'discount_amount' => $this->formatMoney(0),
                'tax_amount' => $this->formatMoney(0),
                'total_amount' => $this->formatMoney($lineTotalCents),
                'paid_amount' => $this->formatMoney(0),
                'change_amount' => $this->formatMoney(0),
                'payment_status' => 'unpaid',
                'payment_method' => null,
                'notes' => null,
                'placed_at' => now(),
            ], $overrides));

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $this->formatScaled($quantityMillis, self::QUANTITY_SCALE),
                'unit_price' => $this->formatMoney($unitPriceCents),
                'tax_rate' => $this->formatMoney(0),
                'tax_amount' => $this->formatMoney(0),
                'discount_amount' => $this->formatMoney(0),
                'line_total' => $this->formatMoney($lineTotalCents),
                'meta' => [
                    'unit' => $product->unit,
                    'product_type' => $product->type,
                    'store_id' => $product->store_id,
                ],
            ]);

            if ($product->track_inventory) {
                $this->inventoryService->adjustStock(
                    product: $product,
                    delta: -((float) $quantity),
                    movementType: 'sale',
                    actor: $actor,
                    note: "Order {$order->order_number}",
                    reference: $order
                );
            }

            return $order->load('items');
        });
    }

    protected function generateOrderNumber(): string
    {
        do {
            $candidate = sprintf(
                'SP-%s-%s',
                now()->format('YmdHis'),
                Str::upper(Str::random(4))
            );
        } while (Order::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }

    private function toScaledInt(int|float|string $value, int $scale): int
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0;
        }

        if (! preg_match('/^[+-]?\d+(\.\d+)?$/', $raw)) {
            throw ValidationException::withMessages([
                'items' => 'Invalid numeric value was provided.',
            ]);
        }

        $negative = str_starts_with($raw, '-');
        $normalized = ltrim($raw, '+-');
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $fraction = preg_replace('/\D/', '', $fraction) ?? '';

        $scaledFraction = str_pad(substr($fraction, 0, $scale), $scale, '0');
        $remainder = substr($fraction, $scale);

        $result = ((int) $whole * (10 ** $scale)) + (int) $scaledFraction;
        if ($remainder !== '' && (int) $remainder[0] >= 5) {
            $result++;
        }

        return $negative ? -$result : $result;
    }

    private function roundDiv(int $numerator, int $denominator): int
    {
        $sign = ($numerator >= 0) ? 1 : -1;
        $abs  = abs($numerator);

        return $sign * intdiv($abs + intdiv($denominator, 2), $denominator);
    }

    private function formatMoney(int $amountCents): string
    {
        return $this->formatScaled($amountCents, self::MONEY_SCALE);
    }

    private function formatScaled(int $value, int $scale): string
    {
        $negative = $value < 0;
        $absolute = abs($value);
        $divisor = 10 ** $scale;
        $whole = intdiv($absolute, $divisor);
        $fraction = str_pad((string) ($absolute % $divisor), $scale, '0', STR_PAD_LEFT);

        return sprintf('%s%d.%s', $negative ? '-' : '', $whole, $fraction);
    }
}
