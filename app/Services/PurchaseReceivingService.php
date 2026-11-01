<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\DB;

class PurchaseReceivingService
{
    /**
     * Create draft purchase order
     *
     * @param array{
     *     store_id: int,
     *     supplier_id: int,
     *     items: array<int, array{product_id: int, quantity: float, unit_cost: float}>,
     *     note: ?string
     * } $data
     */
    public function createDraftPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $today = now()->format('Ymd');
            $count = Purchase::where('store_id', $data['store_id'])
                ->whereDate('created_at', now()->toDateString())
                ->count() + 1;
            $purchaseNo = sprintf('PO-%s-%04d', $today, $count);

            $totalAmount = 0.0;
            foreach ($data['items'] as $item) {
                $totalAmount += (float) $item['quantity'] * (float) $item['unit_cost'];
            }

            $purchase = Purchase::create([
                'store_id' => $data['store_id'],
                'supplier_id' => $data['supplier_id'],
                'purchase_no' => $purchaseNo,
                'status' => 'DRAFT',
                'total_amount' => $totalAmount,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $qty = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $qty * $unitCost,
                ]);
            }

            return $purchase->load('items.product');
        });
    }

    /**
     * Mark purchase as RECEIVED and increment on_hand stock (BR-13)
     */
    public function receivePurchase(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            /** @var Purchase $lockedPurchase */
            $lockedPurchase = Purchase::where('id', $purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPurchase->status === 'RECEIVED') {
                throw new Exception('Pembelian ini sudah diterima sebelumnya.');
            }

            if ($lockedPurchase->status === 'CANCELLED') {
                throw new Exception('Pembelian yang dibatalkan tidak dapat diterima.');
            }

            foreach ($lockedPurchase->items as $item) {
                /** @var Product $product */
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $balance = StockBalance::where('store_id', $lockedPurchase->store_id)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                $beforeQty = $balance ? (float) $balance->on_hand : 0.0;
                $afterQty = $beforeQty + (float) $item->quantity;

                if ($balance) {
                    $balance->update(['on_hand' => $afterQty]);
                } else {
                    StockBalance::create([
                        'store_id' => $lockedPurchase->store_id,
                        'product_id' => $product->id,
                        'on_hand' => $afterQty,
                        'reserved' => 0.0,
                    ]);
                }

                $product->update(['cost_price' => $item->unit_cost]);

                StockMovement::create([
                    'store_id' => $lockedPurchase->store_id,
                    'product_id' => $product->id,
                    'type' => 'PURCHASE_IN',
                    'qty_delta' => (float) $item->quantity,
                    'before_qty' => $beforeQty,
                    'after_qty' => $afterQty,
                    'ref_type' => Purchase::class,
                    'ref_id' => $lockedPurchase->id,
                    'note' => "Penerimaan barang PO no {$lockedPurchase->purchase_no}",
                ]);
            }

            $lockedPurchase->update([
                'status' => 'RECEIVED',
                'received_at' => now(),
            ]);

            AuditLog::log(
                storeId: $lockedPurchase->store_id,
                event: 'PURCHASE_RECEIVED',
                entityType: Purchase::class,
                entityId: $lockedPurchase->id,
                newValues: ['status' => 'RECEIVED', 'purchase_no' => $lockedPurchase->purchase_no]
            );

            return $lockedPurchase;
        });
    }
}
