<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Set initial opening stock or adjust existing on_hand stock
     */
    public function adjustStock(
        Product $product,
        float $adjustmentQty,
        string $type,
        string $reason
    ): StockBalance {
        if (!in_array($type, ['OPENING', 'ADJUSTMENT'])) {
            throw new Exception("Tipe pergerakan stok tidak valid: {$type}");
        }

        return DB::transaction(function () use ($product, $adjustmentQty, $type, $reason) {
            $balance = StockBalance::where('store_id', $product->store_id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $beforeOnHand = $balance ? (float) $balance->on_hand : 0.0;
            $afterOnHand = $beforeOnHand + $adjustmentQty;

            if ($product->track_stock && $afterOnHand < 0) {
                throw new Exception("Penyesuaian stok gagal: Saldo fisik akhir tidak boleh negatif ({$afterOnHand}).");
            }

            if ($balance) {
                $balance->update(['on_hand' => $afterOnHand]);
            } else {
                $balance = StockBalance::create([
                    'store_id' => $product->store_id,
                    'product_id' => $product->id,
                    'on_hand' => $afterOnHand,
                    'reserved' => 0.0,
                ]);
            }

            StockMovement::create([
                'store_id' => $product->store_id,
                'product_id' => $product->id,
                'type' => $type,
                'qty_delta' => $adjustmentQty,
                'before_qty' => $beforeOnHand,
                'after_qty' => $afterOnHand,
                'note' => $reason,
            ]);

            AuditLog::log(
                storeId: $product->store_id,
                event: 'STOCK_ADJUSTED',
                entityType: Product::class,
                entityId: $product->id,
                oldValues: ['on_hand' => $beforeOnHand],
                newValues: ['on_hand' => $afterOnHand, 'delta' => $adjustmentQty, 'reason' => $reason]
            );

            return $balance;
        });
    }
}
