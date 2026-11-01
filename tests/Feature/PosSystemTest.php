<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentIntent;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\PurchaseReceivingService;
use App\Services\RefundService;
use App\Services\ShiftService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected Category $category;
    protected Unit $unit;
    protected Product $productA;
    protected Product $productB;
    protected Customer $customer;
    protected Supplier $supplier;
    protected CashierShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Toko Kasir V3 Modern',
            'code' => 'STORE-01',
            'address' => 'Jl. Sudirman 45',
            'receipt_prefix' => 'INV-',
            'tax_percentage' => 0.00,
            'timezone' => 'Asia/Jakarta',
            'qris_merchant_name' => 'TOKO KASIR V3 MODERN',
            'qris_merchant_id' => 'MID-DEMO-001',
        ]);

        $this->unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs']);
        $this->category = Category::create(['store_id' => $this->store->id, 'name' => 'Sembako']);

        $this->productA = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-001',
            'barcode' => '8990001',
            'name' => 'Beras Pandan 5kg',
            'cost_price' => 50000,
            'sale_price' => 60000,
            'min_stock' => 5,
            'track_stock' => true,
            'is_active' => true,
        ]);

        StockBalance::create([
            'store_id' => $this->store->id,
            'product_id' => $this->productA->id,
            'on_hand' => 20.0,
            'reserved' => 0.0,
        ]);

        $this->productB = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-002',
            'barcode' => '8990002',
            'name' => 'Minyak Goreng 2L',
            'cost_price' => 25000,
            'sale_price' => 30000,
            'min_stock' => 5,
            'track_stock' => true,
            'is_active' => true,
        ]);

        StockBalance::create([
            'store_id' => $this->store->id,
            'product_id' => $this->productB->id,
            'on_hand' => 15.0,
            'reserved' => 0.0,
        ]);

        $this->customer = Customer::create([
            'store_id' => $this->store->id,
            'name' => 'Pelanggan Umum',
        ]);

        $this->supplier = Supplier::create([
            'store_id' => $this->store->id,
            'name' => 'PT Grosir Berkah',
        ]);

        $this->shift = CashierShift::create([
            'store_id' => $this->store->id,
            'terminal_label' => 'Kasir-01',
            'opened_at' => now(),
            'opening_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'OPEN',
        ]);
    }

    /**
     * AT-01 & AT-02: Otentikasi & Akun Dihapus (Tanpa Tabel users)
     */
    public function test_database_has_no_users_table_and_all_pages_accessible(): void
    {
        $this->assertFalse(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasTable('staff_invitations'));
        $this->assertFalse(Schema::hasTable('sessions'));
        $this->assertFalse(Schema::hasTable('password_reset_tokens'));

        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/login')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk();
        $this->get('/pos')->assertOk();
        $this->get('/products')->assertOk();
        $this->get('/inventory')->assertOk();
        $this->get('/sales')->assertOk();
        $this->get('/purchases')->assertOk();
        $this->get('/refunds')->assertOk();
        $this->get('/shifts')->assertOk();
        $this->get('/reports')->assertOk();
        $this->get('/settings')->assertOk();
    }

    /**
     * AT-03: Katalog Produk & Validasi Unik SKU
     */
    public function test_product_creation_and_unique_sku_per_store(): void
    {
        $this->expectException(Exception::class);

        // Attempt duplicate SKU in same store
        Product::create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-001', // Already exists
            'name' => 'Beras Duplikat',
            'cost_price' => 50000,
            'sale_price' => 60000,
        ]);
    }

    /**
     * AT-04: Transaksi Penjualan Memulai Reservasi Stok
     */
    public function test_checkout_initiates_sale_and_reserves_stock(): void
    {
        $checkoutService = app(CheckoutService::class);

        $sale = $checkoutService->initiateCheckout([
            'store_id' => $this->store->id,
            'shift_id' => $this->shift->id,
            'customer_id' => $this->customer->id,
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 3, 'discount' => 0],
                ['product_id' => $this->productB->id, 'quantity' => 2, 'discount' => 0],
            ],
            'discount_amount' => 0,
            'tax_amount' => 0,
            'payment_method' => 'QRIS',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $this->assertEquals('PENDING_PAYMENT', $sale->status);
        $this->assertEquals(240000.0, (float) $sale->total_amount);

        // Verify stock balance: on_hand intact, reserved increased, available reduced
        $balanceA = StockBalance::where('product_id', $this->productA->id)->first();
        $this->assertEquals(20.0, (float) $balanceA->on_hand);
        $this->assertEquals(3.0, (float) $balanceA->reserved);
        $this->assertEquals(17.0, (float) $balanceA->available);

        $balanceB = StockBalance::where('product_id', $this->productB->id)->first();
        $this->assertEquals(15.0, (float) $balanceB->on_hand);
        $this->assertEquals(2.0, (float) $balanceB->reserved);
        $this->assertEquals(13.0, (float) $balanceB->available);

        // Verify stock reservations recorded with status RESERVED
        $this->assertDatabaseHas('stock_reservations', [
            'quantity' => 3.0,
            'status' => 'RESERVED',
        ]);

        // Verify dynamic QRIS PaymentIntent generated
        $this->assertDatabaseHas('payment_intents', [
            'sale_id' => $sale->id,
            'amount' => 240000.0,
            'status' => 'PENDING',
        ]);
    }

    /**
     * AT-05: Konfirmasi Pembayaran QRIS Mengurangi Saldo Fisik (on_hand)
     */
    public function test_qris_confirmation_commits_reservation_and_deducts_physical_on_hand_stock(): void
    {
        $checkoutService = app(CheckoutService::class);

        $sale = $checkoutService->initiateCheckout([
            'store_id' => $this->store->id,
            'shift_id' => $this->shift->id,
            'customer_id' => $this->customer->id,
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 4, 'discount' => 0],
            ],
            'discount_amount' => 0,
            'tax_amount' => 0,
            'payment_method' => 'QRIS',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        // Confirm payment
        $confirmedSale = $checkoutService->confirmPayment(
            sale: $sale,
            providerTransactionId: 'PG-TX-123456',
            amountPaid: 240000.0,
            payload: ['provider' => 'QRIS_GATEWAY', 'status' => 'PAID']
        );

        $this->assertEquals('COMPLETED', $confirmedSale->status);

        // Physical on_hand should be reduced by 4, reserved reduced by 4
        $balanceA = StockBalance::where('product_id', $this->productA->id)->first();
        $this->assertEquals(16.0, (float) $balanceA->on_hand);
        $this->assertEquals(0.0, (float) $balanceA->reserved);
        $this->assertEquals(16.0, (float) $balanceA->available);

        // Reservation status should be COMMITTED
        $this->assertDatabaseHas('stock_reservations', [
            'quantity' => 4.0,
            'status' => 'COMMITTED',
        ]);

        // Stock movement SALE_OUT recorded
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->productA->id,
            'type' => 'SALE_OUT',
            'qty_delta' => -4.0,
            'after_qty' => 16.0,
        ]);
    }

    /**
     * AT-06: Webhook Idempoten & Deduplikasi
     */
    public function test_webhook_idempotency_prevents_duplicate_deduction(): void
    {
        $checkoutService = app(CheckoutService::class);

        $sale = $checkoutService->initiateCheckout([
            'store_id' => $this->store->id,
            'shift_id' => $this->shift->id,
            'customer_id' => $this->customer->id,
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 2, 'discount' => 0],
            ],
            'discount_amount' => 0,
            'tax_amount' => 0,
            'payment_method' => 'QRIS',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $payload = [
            'provider_reference' => $sale->paymentIntent->provider_reference,
            'event_id' => 'EVENT-DEDUPE-001',
            'amount' => 120000.0,
            'event_type' => 'PAYMENT_SUCCESS',
        ];

        // First webhook delivery
        $res1 = $checkoutService->handleWebhook($payload, 'sig_test_1');
        $this->assertEquals('SUCCESS', $res1['status']);

        $balanceAfterFirst = StockBalance::where('product_id', $this->productA->id)->first();
        $this->assertEquals(18.0, (float) $balanceAfterFirst->on_hand);

        // Duplicate webhook delivery with same deduplication key
        $res2 = $checkoutService->handleWebhook($payload, 'sig_test_1');
        $this->assertEquals('ALREADY_PROCESSED', $res2['status']);

        // Stock balance MUST remain untouched
        $balanceAfterSecond = StockBalance::where('product_id', $this->productA->id)->first();
        $this->assertEquals(18.0, (float) $balanceAfterSecond->on_hand);
    }

    /**
     * AT-07: Pembatalan / Kedaluwarsa QRIS Melepas Reservasi Stok
     */
    public function test_cancellation_or_expiry_releases_stock_reservation(): void
    {
        $checkoutService = app(CheckoutService::class);

        $sale = $checkoutService->initiateCheckout([
            'store_id' => $this->store->id,
            'shift_id' => $this->shift->id,
            'customer_id' => $this->customer->id,
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 5, 'discount' => 0],
            ],
            'discount_amount' => 0,
            'tax_amount' => 0,
            'payment_method' => 'QRIS',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $this->assertEquals(5.0, (float) StockBalance::where('product_id', $this->productA->id)->value('reserved'));

        // Cancel sale
        $cancelledSale = $checkoutService->cancelOrExpireSale($sale, 'EXPIRED');
        $this->assertEquals('EXPIRED', $cancelledSale->status);

        // Reserved stock is released back, on_hand remains full 20.0
        $balance = StockBalance::where('product_id', $this->productA->id)->first();
        $this->assertEquals(20.0, (float) $balance->on_hand);
        $this->assertEquals(0.0, (float) $balance->reserved);
        $this->assertEquals(20.0, (float) $balance->available);

        // Reservation marked as RELEASED
        $this->assertDatabaseHas('stock_reservations', [
            'quantity' => 5.0,
            'status' => 'RELEASED',
        ]);
    }

    /**
     * AT-08: Soft Delete Produk Menjaga Integritas Data
     */
    public function test_product_soft_delete_preserves_reporting_integrity(): void
    {
        $productId = $this->productA->id;
        $this->productA->delete();

        $this->assertSoftDeleted('products', ['id' => $productId]);
        $this->assertNull(Product::find($productId));
        $this->assertNotNull(Product::withTrashed()->find($productId));
    }

    /**
     * AT-09: Shift Kasir Tanpa User Accounts
     */
    public function test_cashier_shift_lifecycle(): void
    {
        $shiftService = app(ShiftService::class);

        // Record cash in
        $shiftService->recordCashMovement(
            shift: $this->shift,
            type: 'CASH_IN',
            amount: 50000,
            note: 'Tambah modal uang kembalian'
        );

        $this->assertEquals(150000, (float) $this->shift->fresh()->expected_cash);

        // Close shift
        $closedShift = $shiftService->closeShift(
            shift: $this->shift,
            actualCash: 148000,
            note: 'Tutup shift sore'
        );

        $this->assertEquals('CLOSED', $closedShift->status);
        $this->assertEquals(148000, (float) $closedShift->actual_cash);
        $this->assertEquals(-2000, (float) $closedShift->difference);
    }

    /**
     * AT-10: Retur / Refund Mengembalikan Stok Fisik
     */
    public function test_refund_restores_physical_stock(): void
    {
        $checkoutService = app(CheckoutService::class);
        $refundService = app(RefundService::class);

        $sale = $checkoutService->initiateCheckout([
            'store_id' => $this->store->id,
            'shift_id' => $this->shift->id,
            'customer_id' => $this->customer->id,
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 5, 'discount' => 0],
            ],
            'discount_amount' => 0,
            'tax_amount' => 0,
            'payment_method' => 'CASH',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $checkoutService->confirmPayment($sale, 'CASH-001', 300000.0, ['tendered' => 300000]);

        $balanceBeforeRefund = StockBalance::where('product_id', $this->productA->id)->first();
        $this->assertEquals(15.0, (float) $balanceBeforeRefund->on_hand);

        $saleItem = $sale->items->first();

        $refund = $refundService->processRefund([
            'sale_id' => $sale->id,
            'reason' => 'Kemasan rusak',
            'refund_method' => 'CASH',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 2,
                    'restore_stock' => true,
                    'note' => 'Dikembalikan ke rak',
                ],
            ],
        ]);

        $this->assertEquals(120000.0, (float) $refund->amount);

        // Physical on_hand restored from 15 to 17
        $balanceAfterRefund = StockBalance::where('product_id', $this->productA->id)->first();
        $this->assertEquals(17.0, (float) $balanceAfterRefund->on_hand);

        // Movement RETURN_IN recorded
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->productA->id,
            'type' => 'RETURN_IN',
            'qty_delta' => 2.0,
            'after_qty' => 17.0,
        ]);
    }

    /**
     * AT-11: Penerimaan Pembelian (PO) Menambah Stok Fisik
     */
    public function test_purchase_receiving_increments_physical_stock(): void
    {
        $purchaseService = app(PurchaseReceivingService::class);

        $purchase = $purchaseService->createDraftPurchase([
            'store_id' => $this->store->id,
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 10, 'unit_cost' => 52000],
            ],
            'note' => 'Restock mingguan',
        ]);

        $this->assertEquals('DRAFT', $purchase->status);

        $purchaseService->receivePurchase($purchase);

        $this->assertEquals('RECEIVED', $purchase->fresh()->status);

        // Initial on_hand was 20.0, received 10.0 -> total 30.0
        $balance = StockBalance::where('product_id', $this->productA->id)->first();
        $this->assertEquals(30.0, (float) $balance->on_hand);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->productA->id,
            'type' => 'PURCHASE_IN',
            'qty_delta' => 10.0,
            'after_qty' => 30.0,
        ]);
    }

    /**
     * AT-12: Endpoint Polling Status Pembayaran
     */
    public function test_payment_status_endpoint_returns_json(): void
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_no' => 'INV-POLL-001',
            'idempotency_key' => 'IDEMP-POLL-001',
            'subtotal' => 60000,
            'total_amount' => 60000,
            'payment_method' => 'QRIS',
            'status' => 'PENDING_PAYMENT',
        ]);

        $response = $this->getJson("/sales/{$sale->id}/payment-status");

        $response->assertOk()
            ->assertJson([
                'id' => $sale->id,
                'invoice_number' => 'INV-POLL-001',
                'status' => 'PENDING_PAYMENT',
                'is_paid' => false,
            ]);
    }

    /**
     * AT-13: Verifikasi 22 Tabel Basis Data PRD V3 (Tanpa Auth/Users)
     */
    public function test_prd_v3_database_has_all_22_tables(): void
    {
        $expectedTables = [
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'stores',
            'units',
            'categories',
            'products',
            'customers',
            'suppliers',
            'stock_balances',
            'stock_movements',
            'cashier_shifts',
            'purchases',
            'purchase_items',
            'sales',
            'sale_items',
            'stock_reservations',
            'payment_intents',
            'payment_transactions',
            'refunds',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table '{$table}' must exist in PRD V3 schema.");
        }

        $this->assertFalse(Schema::hasTable('qr_payment_profiles'));
        $this->assertFalse(Schema::hasTable('manual_payment_confirmations'));
    }

    /**
     * AT-14: Transaksi Kasir Tunai (Cash Only) Langsung Memotong Stok dan Menjadi COMPLETED
     */
    public function test_cash_checkout_immediately_deducts_physical_stock_and_marks_completed(): void
    {
        $checkoutService = app(CheckoutService::class);

        $sale = $checkoutService->initiateCheckout([
            'store_id' => $this->store->id,
            'shift_id' => $this->shift->id,
            'customer_id' => $this->customer->id,
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 2, 'discount' => 0],
            ],
            'discount_amount' => 0,
            'tax_amount' => 0,
            'payment_method' => 'CASH',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $this->assertEquals('PENDING_PAYMENT', $sale->status);

        $confirmed = $checkoutService->confirmPayment(
            sale: $sale,
            providerTransactionId: 'CASH-TX-001',
            amountPaid: 120000.0,
            payload: ['tendered' => 150000, 'change' => 30000]
        );

        $this->assertEquals('COMPLETED', $confirmed->status);

        $balance = StockBalance::where('product_id', $this->productA->id)->first();
        $this->assertEquals(18.0, (float) $balance->on_hand);
        $this->assertEquals(0.0, (float) $balance->reserved);
        $this->assertEquals(18.0, (float) $balance->available);
    }
}
