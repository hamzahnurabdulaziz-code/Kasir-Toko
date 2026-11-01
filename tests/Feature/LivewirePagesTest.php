<?php

namespace Tests\Feature;

use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Dashboard;
use App\Livewire\Inventory\Index as InventoryIndex;
use App\Livewire\Payments\Show as PaymentsShow;
use App\Livewire\Pos\Checkout;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Purchases\Index as PurchasesIndex;
use App\Livewire\Refunds\Index as RefundsIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Sales\History as SalesHistory;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\Shifts\Index as ShiftsIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewirePagesTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Toko Livewire Test V3',
            'receipt_prefix' => 'INV-',
            'tax_percentage' => 0.00,
            'timezone' => 'Asia/Jakarta',
            'qris_merchant_name' => 'TOKO TEST V3',
            'qris_merchant_id' => 'MID-TEST-001',
        ]);
    }

    public function test_dashboard_component_renders_without_auth(): void
    {
        Livewire::test(Dashboard::class)
            ->assertStatus(200)
            ->assertSee('Dashboard Ringkasan Usaha')
            ->assertSee('Omzet Penjualan Neto');
    }

    public function test_pos_checkout_component_renders_without_auth(): void
    {
        Livewire::test(Checkout::class)
            ->assertStatus(200)
            ->assertSee('Keranjang Transaksi');
    }

    public function test_products_index_renders_without_auth(): void
    {
        Livewire::test(ProductsIndex::class)
            ->assertStatus(200)
            ->assertSee('Master Data Produk');
    }

    public function test_categories_index_renders_without_auth(): void
    {
        Livewire::test(CategoriesIndex::class)
            ->assertStatus(200)
            ->assertSee('Kategori Toko');
    }

    public function test_inventory_index_renders_without_auth(): void
    {
        Livewire::test(InventoryIndex::class)
            ->assertStatus(200)
            ->assertSee('Saldo Stok Barang');
    }

    public function test_sales_history_renders_without_auth(): void
    {
        Livewire::test(SalesHistory::class)
            ->assertStatus(200)
            ->assertSee('Riwayat Transaksi Penjualan');
    }

    public function test_purchases_index_renders_without_auth(): void
    {
        Livewire::test(PurchasesIndex::class)
            ->assertStatus(200)
            ->assertSee('Pembelian Barang');
    }

    public function test_refunds_index_renders_without_auth(): void
    {
        Livewire::test(RefundsIndex::class)
            ->assertStatus(200)
            ->assertSee('Cari Faktur Penjualan');
    }

    public function test_shifts_index_renders_without_auth(): void
    {
        Livewire::test(ShiftsIndex::class)
            ->assertStatus(200)
            ->assertSee('Riwayat Sesi Shift Kasir');
    }

    public function test_reports_index_renders_without_auth(): void
    {
        Livewire::test(ReportsIndex::class)
            ->assertStatus(200)
            ->assertSee('Laporan Penjualan');
    }

    public function test_settings_index_renders_without_auth(): void
    {
        Livewire::test(SettingsIndex::class)
            ->assertStatus(200)
            ->assertSee('Pengaturan Toko');
    }

    public function test_payment_show_renders_with_pending_sale(): void
    {
        $category = Category::create(['store_id' => $this->store->id, 'name' => 'General']);
        $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
        $product = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'TEST-001',
            'name' => 'Produk Uji',
            'cost_price' => 1000,
            'sale_price' => 2000,
        ]);

        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_no' => 'INV-TEST-001',
            'idempotency_key' => 'IDEMP-TEST-001',
            'subtotal' => 2000,
            'total_amount' => 2000,
            'payment_method' => 'QRIS',
            'status' => 'PENDING_PAYMENT',
        ]);

        $sale->paymentIntent()->create([
            'store_id' => $this->store->id,
            'provider' => 'QRIS_GATEWAY',
            'amount' => 2000,
            'qr_payload' => '00020101021226670016ID.CO.QRIS.WWW',
            'provider_reference' => 'REF-TEST-001',
            'status' => 'PENDING',
            'expires_at' => now()->addMinutes(15),
        ]);

        Livewire::test(PaymentsShow::class, ['sale' => $sale->id])
            ->assertStatus(200)
            ->assertSee('MENUNGGU PEMBAYARAN PELANGGAN')
            ->assertSee('INV-TEST-001');
    }

    public function test_pos_checkout_processes_cash_payment_successfully(): void
    {
        $category = Category::create(['store_id' => $this->store->id, 'name' => 'General']);
        $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
        $product = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'CASH-001',
            'name' => 'Produk Cash',
            'cost_price' => 1000,
            'sale_price' => 2500,
            'track_stock' => true,
            'is_active' => true,
        ]);

        \App\Models\StockBalance::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'on_hand' => 10.0,
            'reserved' => 0.0,
        ]);

        Livewire::test(Checkout::class)
            ->call('addToCart', $product->id)
            ->call('openPaymentModal', 'CASH')
            ->set('tenderedAmount', 5000)
            ->call('processCheckout')
            ->assertSet('showReceiptModal', true)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sales', [
            'store_id' => $this->store->id,
            'payment_method' => 'CASH',
            'status' => 'COMPLETED',
            'total_amount' => 2500,
        ]);
    }
}
