<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class InventoryService
{
    /**
     * Adjust inventory for a product or variation.
     */
    public function adjustInventory(
        Product|ProductVariation $item,
        int $quantity,
        string $reason,
        ?string $notes = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): InventoryAdjustment {
        if (! $item->track_inventory) {
            throw new \InvalidArgumentException('Cannot adjust inventory for item that does not track inventory');
        }

        return DB::transaction(function () use ($item, $quantity, $reason, $notes, $referenceType, $referenceId) {
            $quantityBefore = $item->inventory_quantity;
            $quantityAfter = max(0, $quantityBefore + $quantity);

            // Update the inventory quantity
            $item->update(['inventory_quantity' => $quantityAfter]);

            // Create audit record
            $adjustment = InventoryAdjustment::create([
                'team_id' => $item instanceof Product ? $item->team_id : $item->product->team_id,
                'adjustable_type' => $item::class,
                'adjustable_id' => $item->id,
                'user_id' => auth()->id(),
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'adjustment_quantity' => $quantity,
                'reason' => $reason,
                'notes' => $notes,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            // Check for low stock after adjustment
            if ($this->isLowStock($item)) {
                $this->triggerLowStockNotification($item);
            }

            return $adjustment;
        });
    }

    /**
     * Get available inventory quantity (current - reserved).
     */
    public function getAvailableQuantity(Product|ProductVariation $item): int
    {
        if (! $item->track_inventory) {
            return PHP_INT_MAX; // Unlimited if not tracking
        }

        return $item->availableInventory();
    }

    /**
     * Reserve inventory for a product or variation.
     */
    public function reserveInventory(
        Product|ProductVariation $item,
        int $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): bool {
        if (! $item->track_inventory) {
            return true; // Always successful if not tracking
        }

        if ($this->getAvailableQuantity($item) < $quantity) {
            return false; // Insufficient inventory
        }

        return DB::transaction(function () use ($item, $quantity, $referenceType, $referenceId): true {
            $item->increment('reserved_quantity', $quantity);

            // Log the reservation
            Log::info('Inventory reserved', [
                'item_type' => $item::class,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => auth()->id(),
            ]);

            return true;
        });
    }

    /**
     * Release reserved inventory.
     */
    public function releaseInventory(
        Product|ProductVariation $item,
        int $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): void {
        if (! $item->track_inventory) {
            return; // Nothing to release if not tracking
        }

        DB::transaction(function () use ($item, $quantity, $referenceType, $referenceId): void {
            $releaseQuantity = min($quantity, $item->reserved_quantity);
            $item->decrement('reserved_quantity', $releaseQuantity);

            // Log the release
            Log::info('Reserved inventory released', [
                'item_type' => $item::class,
                'item_id' => $item->id,
                'quantity' => $releaseQuantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => auth()->id(),
            ]);
        });
    }

    /**
     * Check if item is low on stock.
     */
    public function isLowStock(Product|ProductVariation $item, ?int $threshold = null): bool
    {
        if (! $item->track_inventory) {
            return false;
        }

        $threshold ??= config('inventory.low_stock_threshold', 10);

        return $this->getAvailableQuantity($item) <= $threshold;
    }

    /**
     * Automatically decrement inventory when a product is sold.
     */
    public function decrementForSale(
        Product|ProductVariation $item,
        int $quantity,
        string $referenceId,
    ): InventoryAdjustment {
        return $this->adjustInventory(
            item: $item,
            quantity: -$quantity,
            reason: 'Sale',
            notes: 'Inventory decremented for sale',
            referenceType: 'sale',
            referenceId: $referenceId,
        );
    }

    /**
     * Increment inventory when a product is returned.
     */
    public function incrementForReturn(
        Product|ProductVariation $item,
        int $quantity,
        string $referenceId,
    ): InventoryAdjustment {
        return $this->adjustInventory(
            item: $item,
            quantity: $quantity,
            reason: 'Return',
            notes: 'Inventory incremented for return',
            referenceType: 'return',
            referenceId: $referenceId,
        );
    }

    /**
     * Bulk adjust inventory for multiple items.
     */
    public function bulkAdjustInventory(array $adjustments): array
    {
        $results = [];

        DB::transaction(function () use ($adjustments, &$results): void {
            foreach ($adjustments as $adjustment) {
                $item = $adjustment['item'];
                $quantity = $adjustment['quantity'];
                $reason = $adjustment['reason'];
                $notes = $adjustment['notes'] ?? null;
                $referenceType = $adjustment['reference_type'] ?? null;
                $referenceId = $adjustment['reference_id'] ?? null;

                $results[] = $this->adjustInventory(
                    $item,
                    $quantity,
                    $reason,
                    $notes,
                    $referenceType,
                    $referenceId,
                );
            }
        });

        return $results;
    }

    /**
     * Get inventory statistics for a product.
     */
    public function getInventoryStats(Product $product): array
    {
        $stats = [
            'total_inventory' => $product->getTotalInventory(),
            'total_reserved' => $product->getTotalReserved(),
            'total_available' => $product->availableInventory(),
            'has_variants' => $product->hasVariants(),
            'track_inventory' => $product->track_inventory,
            'is_low_stock' => $this->isLowStock($product),
        ];

        if ($product->hasVariants()) {
            $stats['variations'] = $product->variations->map(fn (ProductVariation $variation): array => [
                'id' => $variation->id,
                'name' => $variation->name,
                'sku' => $variation->sku,
                'inventory_quantity' => $variation->inventory_quantity,
                'reserved_quantity' => $variation->reserved_quantity,
                'available_quantity' => $variation->availableInventory(),
                'is_low_stock' => $this->isLowStock($variation),
            ])->toArray();
        }

        return $stats;
    }

    /**
     * Get inventory adjustment history for an item.
     */
    public function getAdjustmentHistory(Product|ProductVariation $item, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return InventoryAdjustment::where('adjustable_type', $item::class)
            ->where('adjustable_id', $item->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Sync variation inventory with parent product.
     */
    public function syncVariationInventory(Product $product): void
    {
        if (! $product->hasVariants()) {
            return;
        }

        DB::transaction(function () use ($product): void {
            $totalInventory = $product->variations()->sum('inventory_quantity');
            $totalReserved = $product->variations()->sum('reserved_quantity');

            $product->update([
                'inventory_quantity' => $totalInventory,
                'reserved_quantity' => $totalReserved,
            ]);
        });
    }

    /**
     * Get low stock items for a team.
     */
    public function getLowStockItems(int $teamId, ?int $threshold = null): \Illuminate\Database\Eloquent\Collection
    {
        $threshold ??= config('inventory.low_stock_threshold', 10);

        // Get products that are low on stock
        $products = Product::where('team_id', $teamId)
            ->where('track_inventory', true)
            ->where('is_active', true)
            ->whereRaw('(inventory_quantity - reserved_quantity) <= ?', [$threshold])
            ->get();

        // Get variations that are low on stock
        $variations = ProductVariation::whereHas('product', function (\Illuminate\Contracts\Database\Query\Builder $query) use ($teamId): void {
            $query->where('team_id', $teamId)
                ->where('is_active', true);
        })
            ->where('track_inventory', true)
            ->whereRaw('(inventory_quantity - reserved_quantity) <= ?', [$threshold])
            ->with('product')
            ->get();

        return $products->merge($variations);
    }

    /**
     * Trigger low stock notification.
     */
    private function triggerLowStockNotification(Product|ProductVariation $item): void
    {
        // This would integrate with your notification system
        // For now, just log it
        Log::warning('Low stock detected', [
            'item_type' => $item::class,
            'item_id' => $item->id,
            'item_name' => $item->name,
            'available_quantity' => $this->getAvailableQuantity($item),
            'team_id' => $item instanceof Product ? $item->team_id : $item->product->team_id,
        ]);

        // TODO: Implement actual notification dispatch
        // This could dispatch a notification to relevant users
        // or trigger an event that other parts of the system can listen to
    }

    /**
     * Check if sufficient inventory is available for a sale.
     */
    public function canFulfillOrder(array $items): array
    {
        $results = [];

        foreach ($items as $itemData) {
            $item = $itemData['item'];
            $requestedQuantity = $itemData['quantity'];
            $availableQuantity = $this->getAvailableQuantity($item);

            $results[] = [
                'item' => $item,
                'requested_quantity' => $requestedQuantity,
                'available_quantity' => $availableQuantity,
                'can_fulfill' => $availableQuantity >= $requestedQuantity,
                'shortage' => max(0, $requestedQuantity - $availableQuantity),
            ];
        }

        return $results;
    }

    /**
     * Reserve inventory for multiple items (e.g., for an order).
     */
    public function reserveInventoryForOrder(array $items, string $orderId): array
    {
        $results = [];

        DB::transaction(function () use ($items, $orderId, &$results): void {
            foreach ($items as $itemData) {
                $item = $itemData['item'];
                $quantity = $itemData['quantity'];

                $success = $this->reserveInventory(
                    $item,
                    $quantity,
                    'order',
                    $orderId,
                );

                $results[] = [
                    'item' => $item,
                    'quantity' => $quantity,
                    'success' => $success,
                ];

                if (! $success) {
                    throw new \Exception("Insufficient inventory for item: {$item->name}");
                }
            }
        });

        return $results;
    }

    /**
     * Release inventory reservations for an order.
     */
    public function releaseInventoryForOrder(array $items, string $orderId): void
    {
        DB::transaction(function () use ($items, $orderId): void {
            foreach ($items as $itemData) {
                $item = $itemData['item'];
                $quantity = $itemData['quantity'];

                $this->releaseInventory(
                    $item,
                    $quantity,
                    'order',
                    $orderId,
                );
            }
        });
    }
}
