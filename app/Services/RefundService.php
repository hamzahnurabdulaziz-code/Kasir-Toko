<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\DB;

class RefundService
{
    /**
     * Process refund with item verification and optional restock
     *
     * @param array{
     *     sale_id: int,
     *     reason: string,
     *     refund_method: string,
     *     items: array<int, array{sale_item_id: int, quantity: float, restore_stock: bool, note: ?string}>
     * } $data
     */
    public function processRefund(array $data): Refund
    {
        return DB::transaction(function () use ($data) {
            /** @var Sale $sale */
            $sale = Sale::where('id', $data['sale_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->status === 'REFUNDED') {
                throw new Exception('Transaksi penjualan ini sudah diretur sepenuhnya.');
            }

            $today = now()->format('Ymd');
            $count = Refund::whereDate('created_at', now()->toDateString())->count() + 1;
            $refundNo = sprintf('REF-%s-%04d', $today, $count);

            $totalRefundAmount = 0.0;
            $refundItemsData = [];

            foreach ($data['items'] as $itemData) {
                $qtyToRefund = (float) $itemData['quantity'];
                if ($qtyToRefund <= 0) {
                    continue;
                }

                /** @var SaleItem $saleItem */
                $saleItem = SaleItem::where('id', $itemData['sale_item_id'])
                    ->where('sale_id', $sale->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $alreadyRefunded = (float) $saleItem->refundItems()->sum('quantity');
                $remainingRefundable = (float) $saleItem->quantity - $alreadyRefunded;

                if ($qtyToRefund > $remainingRefundable) {
                    throw new Exception(sprintf(
                        "Kuantitas retur untuk '%s' melebihi sisa barang yang dapat diretur (diminta: %s, tersedia: %s).",
                        $saleItem->product_name_snapshot,
                        $qtyToRefund,
                        $remainingRefundable
                    ));
                }

                $unitNetPrice = (float) $saleItem->unit_price - ((float) $saleItem->discount_amount / (float) $saleItem->quantity);
                $itemRefundAmount = $unitNetPrice * $qtyToRefund;
                $totalRefundAmount += $itemRefundAmount;

                $refundItemsData[] = [
                    'sale_item' => $saleItem,
                    'quantity' => $qtyToRefund,
                    'amount' => $itemRefundAmount,
                    'restore_stock' => (bool) ($itemData['restore_stock'] ?? true),
                    'note' => $itemData['note'] ?? null,
                ];
            }

            if (empty($refundItemsData)) {
                throw new Exception('Pilih setidaknya satu item untuk diretur.');
            }

            $refund = Refund::create([
                'sale_id' => $sale->id,
                'refund_no' => $refundNo,
                'amount' => $totalRefundAmount,
                'reason' => $data['reason'],
                'refund_method' => $data['refund_method'] ?? 'QRIS',
            ]);

            foreach ($refundItemsData as $item) {
                /** @var SaleItem $saleItem */
                $saleItem = $item['sale_item'];
                $qty = $item['quantity'];

                RefundItem::create([
                    'refund_id' => $refund->id,
                    'sale_item_id' => $saleItem->id,
                    'quantity' => $qty,
                    'amount' => $item['amount'],
                    'restore_stock' => $item['restore_stock'],
                    'note' => $item['note'],
                ]);

                // Restore stock if sellable (BR-10 & AT-15)
                if ($item['restore_stock']) {
                    $product = Product::find($saleItem->product_id);
                    if ($product && $product->track_stock) {
                        $balance = StockBalance::where('store_id', $sale->store_id)
                            ->where('product_id', $product->id)
                            ->lockForUpdate()
                            ->first();

                        $beforeQty = $balance ? (float) $balance->on_hand : 0.0;
                        $afterQty = $beforeQty + $qty;

                        if ($balance) {
                            $balance->update(['on_hand' => $afterQty]);
                        } else {
                            StockBalance::create([
                                'store_id' => $sale->store_id,
                                'product_id' => $product->id,
                                'on_hand' => $afterQty,
                                'reserved' => 0.0,
                            ]);
                        }

                        StockMovement::create([
                            'store_id' => $sale->store_id,
                            'product_id' => $product->id,
                            'type' => 'RETURN_IN',
                            'qty_delta' => $qty,
                            'before_qty' => $beforeQty,
                            'after_qty' => $afterQty,
                            'ref_type' => Refund::class,
                            'ref_id' => $refund->id,
                            'note' => "Retur layak jual untuk nota {$sale->invoice_no}",
                        ]);
                    }
                }
            }

            // Check if all items across sale are now fully refunded
            $allSaleItems = $sale->items()->get();
            $allFullyRefunded = true;
            foreach ($allSaleItems as $si) {
                $refQty = (float) $si->refundItems()->sum('quantity');
                if ($refQty < (float) $si->quantity) {
                    $allFullyRefunded = false;
                    break;
                }
            }

            $sale->update([
                'status' => $allFullyRefunded ? 'REFUNDED' : 'PARTIALLY_REFUNDED',
            ]);

            // If refund is cash, decrease shift expected cash if active shift exists
            if (($data['refund_method'] ?? 'QRIS') === 'CASH' && $sale->shift_id) {
                $shift = CashierShift::find($sale->shift_id);
                $shift?->decrement('expected_cash', $totalRefundAmount);
            }

            AuditLog::log(
                storeId: $sale->store_id,
                event: 'REFUND_PROCESSED',
                entityType: Refund::class,
                entityId: $refund->id,
                newValues: [
                    'refund_no' => $refund->refund_no,
                    'amount' => $totalRefundAmount,
                    'invoice_no' => $sale->invoice_no,
                ]
            );

            return $refund->load('items.saleItem');
        });
    }
}
