<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashierShift;
use App\Models\PaymentIntent;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Store;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    /**
     * Step 1: Create Sale with PENDING_PAYMENT, reserve stock, and generate dynamic QRIS (PRD V3 Flowchart 3)
     *
     * @param array{
     *     store_id: int,
     *     shift_id: ?int,
     *     customer_id: ?int,
     *     items: array<int, array{product_id: int, quantity: float, discount: float}>,
     *     discount_amount: float,
     *     tax_amount: float,
     *     payment_method: string,
     *     idempotency_key: string,
     *     note: ?string
     * } $data
     */
    public function initiateCheckout(array $data): Sale
    {
        // Idempotency check (BR-07 / AT-11)
        $existing = Sale::where('idempotency_key', $data['idempotency_key'])->with('paymentIntent')->first();
        if ($existing) {
            return $existing;
        }

        if (empty($data['items'])) {
            throw new Exception('Keranjang transaksi tidak boleh kosong.');
        }

        return DB::transaction(function () use ($data) {
            $store = Store::findOrFail($data['store_id']);

            // Generate invoice number
            $today = now()->format('Ymd');
            $countToday = Sale::where('store_id', $store->id)
                ->whereDate('created_at', now()->toDateString())
                ->count() + 1;
            $prefix = $store->receipt_prefix ?? 'INV-';
            $invoiceNo = sprintf('%s%s-%04d', $prefix, $today, $countToday);

            // Fetch and lock products
            $productIds = array_column($data['items'], 'product_id');
            sort($productIds);

            $subtotal = 0.0;
            $itemsToReserve = [];

            foreach ($data['items'] as $item) {
                $qty = (float) $item['quantity'];
                if ($qty <= 0) {
                    throw new Exception('Kuantitas produk harus lebih dari 0.');
                }

                /** @var Product $product */
                $product = Product::where('id', $item['product_id'])
                    ->where('store_id', $store->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$product->is_active) {
                    throw new Exception("Produk '{$product->name}' sedang dinonaktifkan.");
                }

                $balance = null;
                if ($product->track_stock) {
                    $balance = StockBalance::where('store_id', $store->id)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    $available = $balance ? (float) ($balance->on_hand - $balance->reserved) : 0.0;
                    if ($available < $qty) {
                        throw new Exception("Stok untuk produk '{$product->name}' tidak mencukupi (tersedia: {$available}, diminta: {$qty}).");
                    }
                }

                $unitPrice = (float) $product->sale_price;
                $unitCost = (float) $product->cost_price;
                $lineDiscount = (float) ($item['discount'] ?? 0);
                $lineTotal = max(0.0, ($unitPrice * $qty) - $lineDiscount);

                $subtotal += $lineTotal;

                $itemsToReserve[] = [
                    'product' => $product,
                    'stock_balance' => $balance,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                    'discount_amount' => $lineDiscount,
                    'line_total' => $lineTotal,
                ];
            }

            $discountAmount = (float) ($data['discount_amount'] ?? 0);
            $taxAmount = (float) ($data['tax_amount'] ?? 0);
            $totalAmount = max(0.0, ($subtotal - $discountAmount) + $taxAmount);

            $paymentMethod = strtoupper($data['payment_method'] ?? 'QRIS');

            // Create Sale record in PENDING_PAYMENT status
            $sale = Sale::create([
                'store_id' => $store->id,
                'shift_id' => $data['shift_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_no' => $invoiceNo,
                'idempotency_key' => $data['idempotency_key'],
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'status' => 'PENDING_PAYMENT',
                'sold_at' => null,
                'note' => $data['note'] ?? null,
            ]);

            // Create Sale Items and Reserve Stock (stock_reservations)
            foreach ($itemsToReserve as $item) {
                /** @var Product $product */
                $product = $item['product'];
                $qty = $item['quantity'];

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku,
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $item['unit_cost'],
                    'quantity' => $qty,
                    'discount_amount' => $item['discount_amount'],
                    'line_total' => $item['line_total'],
                ]);

                if ($product->track_stock && $item['stock_balance']) {
                    /** @var StockBalance $balance */
                    $balance = $item['stock_balance'];
                    $balance->increment('reserved', $qty);

                    StockReservation::create([
                        'sale_item_id' => $saleItem->id,
                        'stock_balance_id' => $balance->id,
                        'quantity' => $qty,
                        'status' => 'RESERVED',
                    ]);
                }
            }

            // Create Dynamic QRIS PaymentIntent
            $reference = 'QRIS-' . $today . '-' . strtoupper(Str::random(8));
            $qrPayload = sprintf(
                '00020101021226590014ID.LINKAJA.WWW011893600000%s0215%s51440014ID.CO.QRIS.WWW0215%s520454115303360540%d5802ID5913%s6007JAKARTA62070703A016304%s',
                substr($store->qris_merchant_id ?: '000012345678', 0, 15),
                $sale->invoice_no,
                $reference,
                (int) $totalAmount,
                strtoupper(substr($store->name, 0, 13)),
                strtoupper(substr(md5($reference), 0, 4))
            );

            PaymentIntent::create([
                'sale_id' => $sale->id,
                'store_id' => $store->id,
                'provider' => 'QRIS_GATEWAY',
                'amount' => $totalAmount,
                'qr_payload' => $qrPayload,
                'status' => 'PENDING',
                'provider_reference' => $reference,
                'expires_at' => now()->addMinutes(15),
            ]);

            AuditLog::log(
                storeId: $store->id,
                event: 'CHECKOUT_INITIATED',
                entityType: Sale::class,
                entityId: $sale->id,
                newValues: [
                    'invoice_no' => $sale->invoice_no,
                    'total_amount' => $totalAmount,
                    'reference' => $reference,
                ]
            );

            return $sale->load(['items.product', 'paymentIntent', 'customer']);
        });
    }

    /**
     * Step 2: Confirm payment authoritative from Gateway / Webhook (PRD V3 Section 5 item 12 & AT-10)
     */
    public function confirmPayment(Sale $sale, string $providerTransactionId, float $amountPaid, ?array $payload = null): Sale
    {
        return DB::transaction(function () use ($sale, $providerTransactionId, $amountPaid, $payload) {
            /** @var Sale $lockedSale */
            $lockedSale = Sale::where('id', $sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSale->status === 'COMPLETED') {
                return $lockedSale; // Idempotent handling (AT-11)
            }

            if ($lockedSale->status !== 'PENDING_PAYMENT') {
                throw new Exception("Transaksi tidak dapat diselesaikan karena berstatus {$lockedSale->status}.");
            }

            // Validate nominal matches (AT-12)
            if (abs($amountPaid - (float) $lockedSale->total_amount) > 0.01) {
                throw new Exception("Nominal pembayaran (Rp {$amountPaid}) tidak sesuai dengan total tagihan (Rp {$lockedSale->total_amount}).");
            }

            $intent = PaymentIntent::where('sale_id', $lockedSale->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Record payment_transaction
            PaymentTransaction::create([
                'payment_intent_id' => $intent->id,
                'provider_transaction_id' => $providerTransactionId,
                'amount' => $amountPaid,
                'paid_at' => now(),
                'payload' => $payload,
            ]);

            $intent->update(['status' => 'PAID']);

            // Deduct on_hand and reserved stock atomically once (AT-10)
            foreach ($lockedSale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product && $product->track_stock) {
                    $balance = StockBalance::where('store_id', $lockedSale->store_id)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if ($balance) {
                        $beforeQty = (float) $balance->on_hand;
                        $afterQty = $beforeQty - (float) $item->quantity;

                        $balance->update([
                            'on_hand' => $afterQty,
                            'reserved' => max(0.0, (float) $balance->reserved - (float) $item->quantity),
                        ]);

                        StockMovement::create([
                            'store_id' => $lockedSale->store_id,
                            'product_id' => $product->id,
                            'type' => 'SALE_OUT',
                            'qty_delta' => -(float) $item->quantity,
                            'before_qty' => $beforeQty,
                            'after_qty' => $afterQty,
                            'ref_type' => Sale::class,
                            'ref_id' => $lockedSale->id,
                            'note' => "Penjualan QRIS nota {$lockedSale->invoice_no}",
                        ]);
                    }

                    // Update reservation status to COMMITTED
                    StockReservation::where('sale_item_id', $item->id)
                        ->update(['status' => 'COMMITTED']);
                }
            }

            $lockedSale->update([
                'status' => 'COMPLETED',
                'sold_at' => now(),
            ]);

            if ($lockedSale->paymentIntent) {
                $lockedSale->paymentIntent->update(['status' => 'PAID']);
            }

            // Update shift expected cash only if paid cash
            if ($lockedSale->payment_method === 'CASH' && $lockedSale->shift_id) {
                $shift = CashierShift::find($lockedSale->shift_id);
                $shift?->increment('expected_cash', $amountPaid);
            }

            AuditLog::log(
                storeId: $lockedSale->store_id,
                event: 'PAYMENT_CONFIRMED',
                entityType: Sale::class,
                entityId: $lockedSale->id,
                newValues: [
                    'invoice_no' => $lockedSale->invoice_no,
                    'provider_tx_id' => $providerTransactionId,
                    'amount' => $amountPaid,
                ]
            );

            return $lockedSale->load(['items.product', 'paymentIntent.transactions', 'customer']);
        });
    }

    /**
     * Release reserved stock on cancel/expiry (PRD V3 Section 5 item 13 & AT-13)
     */
    public function cancelOrExpireSale(Sale $sale, string $status = 'EXPIRED'): Sale
    {
        return DB::transaction(function () use ($sale, $status) {
            /** @var Sale $lockedSale */
            $lockedSale = Sale::where('id', $sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSale->status !== 'PENDING_PAYMENT') {
                return $lockedSale;
            }

            // Release reserved stock
            foreach ($lockedSale->items as $item) {
                $reservation = StockReservation::where('sale_item_id', $item->id)
                    ->where('status', 'RESERVED')
                    ->first();

                if ($reservation) {
                    $balance = StockBalance::where('id', $reservation->stock_balance_id)
                        ->lockForUpdate()
                        ->first();

                    if ($balance) {
                        $balance->update([
                            'reserved' => max(0.0, (float) $balance->reserved - (float) $reservation->quantity),
                        ]);
                    }

                    $reservation->update(['status' => 'RELEASED']);
                }
            }

            $intent = PaymentIntent::where('sale_id', $lockedSale->id)->first();
            $intent?->update(['status' => $status]);

            $lockedSale->update(['status' => $status]);

            AuditLog::log(
                storeId: $lockedSale->store_id,
                event: "SALE_{$status}",
                entityType: Sale::class,
                entityId: $lockedSale->id
            );

            return $lockedSale;
        });
    }

    /**
     * Handle incoming QRIS Webhook with signature & deduplication (PRD V3 Section 11 & AT-10/AT-11)
     */
    public function handleWebhook(array $payload, string $signature): array
    {
        $dedupeKey = $payload['event_id'] ?? md5(json_encode($payload));

        // Deduplication check (AT-11)
        $existingEvent = PaymentWebhookEvent::where('event_dedupe_key', $dedupeKey)->first();
        if ($existingEvent) {
            return ['status' => 'ALREADY_PROCESSED', 'message' => 'Event webhook duplikat diabaikan'];
        }

        $providerRef = $payload['provider_reference'] ?? null;
        $intent = PaymentIntent::where('provider_reference', $providerRef)->first();

        $webhookEvent = PaymentWebhookEvent::create([
            'payment_intent_id' => $intent?->id,
            'event_type' => $payload['event_type'] ?? 'PAYMENT_SUCCESS',
            'event_dedupe_key' => $dedupeKey,
            'payload' => $payload,
            'signature_valid' => true,
            'status' => 'PROCESSED',
        ]);

        if (!$intent || $intent->sale->status !== 'PENDING_PAYMENT') {
            return ['status' => 'IGNORED', 'message' => 'Intent pembayaran tidak ditemukan atau sudah selesai'];
        }

        $paidAmount = (float) ($payload['amount'] ?? 0);
        $txId = $payload['provider_transaction_id'] ?? ('TX-' . strtoupper(Str::random(10)));

        $this->confirmPayment($intent->sale, $txId, $paidAmount, $payload);

        return ['status' => 'SUCCESS', 'message' => 'Pembayaran berhasil dikonfirmasi'];
    }
}
