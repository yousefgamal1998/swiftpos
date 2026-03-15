<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function adjustStock(
        Product $product,
        float $delta,
        string $movementType,
        ?User $actor = null,
        ?string $note = null,
        ?Model $reference = null
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $delta, $movementType, $actor, $note, $reference): InventoryMovement {
            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail($product->id);

            $previousStock = (float) $lockedProduct->stock_quantity;
            $newStock = round($previousStock + $delta, 3);

            if ($lockedProduct->track_inventory && $newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock for {$lockedProduct->name}.",
                ]);
            }

            if ($lockedProduct->track_inventory) {
                $lockedProduct->update([
                    'stock_quantity' => $newStock,
                ]);
            }

            return InventoryMovement::query()->create([
                'product_id' => $lockedProduct->id,
                'user_id' => $actor?->id,
                'movement_type' => $movementType,
                'quantity' => $delta,
                'previous_stock' => $previousStock,
                'new_stock' => $lockedProduct->track_inventory ? $newStock : $previousStock,
                'note' => $note,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }
}
