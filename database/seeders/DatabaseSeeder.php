<?php

namespace Database\Seeders;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database (PRD V3 - Tanpa Akun / No Auth).
     */
    public function run(): void
    {
        // 1. Create Store
        $store = Store::create([
            'name' => 'Toko Berkah POS',
            'address' => 'Jl. Sudirman No. 45, Jakarta Pusat',
            'phone' => '0812-3456-7890',
            'timezone' => 'Asia/Jakarta',
            'receipt_prefix' => 'INV-',
            'receipt_footer' => 'Terima kasih telah berbelanja di Toko Berkah! Barang yang sudah dibeli dapat ditukar maksimal 2 hari dengan membawa nota ini.',
            'tax_percentage' => 0.00,
            'qris_merchant_name' => 'TOKO BERKAH POS',
            'qris_merchant_id' => 'ID1020000123456',
        ]);

        // 2. Create Units
        $units = [
            ['name' => 'Pieces', 'symbol' => 'pcs'],
            ['name' => 'Kilogram', 'symbol' => 'kg'],
            ['name' => 'Box / Dus', 'symbol' => 'box'],
            ['name' => 'Liter', 'symbol' => 'liter'],
            ['name' => 'Botol', 'symbol' => 'btl'],
            ['name' => 'Pack / Sachet', 'symbol' => 'pack'],
        ];

        $unitModels = [];
        foreach ($units as $u) {
            $unitModels[$u['symbol']] = Unit::create($u);
        }

        // 3. Create Categories
        $categories = [
            'Makanan & Camilan',
            'Minuman Dingin',
            'Alat Tulis Kantor',
            'Kebutuhan Rumah Tangga',
            'Bumbu & Sembako',
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[$cat] = Category::create([
                'store_id' => $store->id,
                'name' => $cat,
                'is_active' => true,
            ]);
        }

        // 4. Create Customers
        Customer::create([
            'store_id' => $store->id,
            'name' => 'Pelanggan Umum (Walk-in)',
            'phone' => '-',
            'email' => null,
            'address' => null,
        ]);

        Customer::create([
            'store_id' => $store->id,
            'name' => 'Ibu Dewi Lestari',
            'phone' => '081299887766',
            'email' => 'dewi@example.com',
            'address' => 'Jl. Kebon Jeruk No. 12',
        ]);

        Customer::create([
            'store_id' => $store->id,
            'name' => 'Pak Bambang Haryanto',
            'phone' => '081311223344',
            'email' => 'bambang@example.com',
            'address' => 'Jl. Tebet Raya No. 5',
        ]);

        // 5. Create Suppliers
        Supplier::create([
            'store_id' => $store->id,
            'name' => 'PT Sumber Makmur Grosir',
            'phone' => '021-5551234',
            'email' => 'order@sumbermakmur.co.id',
            'address' => 'Kawasan Industri Pulogadung Blok C No. 4',
        ]);

        Supplier::create([
            'store_id' => $store->id,
            'name' => 'CV Berkah Sembako Nusantara',
            'phone' => '021-8889900',
            'email' => 'sales@berkahsembako.id',
            'address' => 'Pasar Induk Kramat Jati',
        ]);

        // 6. Products and Initial Stock
        $productsData = [
            [
                'name' => 'Indomie Goreng Spesial',
                'sku' => 'MKN-001',
                'barcode' => '8998866200112',
                'category' => 'Makanan & Camilan',
                'unit' => 'pcs',
                'cost' => 2800,
                'price' => 3500,
                'stock' => 120,
                'min' => 20,
            ],
            [
                'name' => 'Teh Botol Sosro 350ml',
                'sku' => 'MNM-001',
                'barcode' => '8999999001011',
                'category' => 'Minuman Dingin',
                'unit' => 'btl',
                'cost' => 4000,
                'price' => 5500,
                'stock' => 60,
                'min' => 15,
            ],
            [
                'name' => 'Aqua Air Mineral 600ml',
                'sku' => 'MNM-002',
                'barcode' => '8992775211110',
                'category' => 'Minuman Dingin',
                'unit' => 'btl',
                'cost' => 3000,
                'price' => 4000,
                'stock' => 80,
                'min' => 20,
            ],
            [
                'name' => 'Buku Tulis KIKY 38 Lembar',
                'sku' => 'ATK-001',
                'barcode' => '8991389100012',
                'category' => 'Alat Tulis Kantor',
                'unit' => 'pcs',
                'cost' => 4200,
                'price' => 6000,
                'stock' => 45,
                'min' => 10,
            ],
            [
                'name' => 'Pulpen Gel Pilot 0.5 Hitam',
                'sku' => 'ATK-002',
                'barcode' => '4902505123456',
                'category' => 'Alat Tulis Kantor',
                'unit' => 'pcs',
                'cost' => 11500,
                'price' => 15000,
                'stock' => 35,
                'min' => 10,
            ],
            [
                'name' => 'Beras Pandan Wangi 5kg',
                'sku' => 'SBK-001',
                'barcode' => '8991001200501',
                'category' => 'Bumbu & Sembako',
                'unit' => 'box',
                'cost' => 68000,
                'price' => 78000,
                'stock' => 25,
                'min' => 5,
            ],
            [
                'name' => 'Minyak Goreng Bimoli 2L',
                'sku' => 'SBK-002',
                'barcode' => '8992753112233',
                'category' => 'Bumbu & Sembako',
                'unit' => 'pcs',
                'cost' => 32000,
                'price' => 37500,
                'stock' => 30,
                'min' => 8,
            ],
            [
                'name' => 'Gula Pasir Gulaku 1kg',
                'sku' => 'SBK-003',
                'barcode' => '8993005112211',
                'category' => 'Bumbu & Sembako',
                'unit' => 'pcs',
                'cost' => 15500,
                'price' => 18000,
                'stock' => 40,
                'min' => 10,
            ],
            [
                'name' => 'Sunlight Pencuci Piring 700ml',
                'sku' => 'RMT-001',
                'barcode' => '8999999551101',
                'category' => 'Kebutuhan Rumah Tangga',
                'unit' => 'pack',
                'cost' => 13500,
                'price' => 16500,
                'stock' => 30,
                'min' => 6,
            ],
            [
                'name' => 'Sabun Mandi Lifebuoy Total 10',
                'sku' => 'RMT-002',
                'barcode' => '8999999552202',
                'category' => 'Kebutuhan Rumah Tangga',
                'unit' => 'pcs',
                'cost' => 3800,
                'price' => 5000,
                'stock' => 50,
                'min' => 12,
            ],
            [
                'name' => 'Kopi Kapal Api Spesial Mix 1 Renteng',
                'sku' => 'MKN-002',
                'barcode' => '8992696401019',
                'category' => 'Makanan & Camilan',
                'unit' => 'pack',
                'cost' => 13000,
                'price' => 16000,
                'stock' => 35,
                'min' => 5,
            ],
            [
                'name' => 'Telur Ayam Negeri Fresh 1kg',
                'sku' => 'SBK-004',
                'barcode' => '8991001999999',
                'category' => 'Bumbu & Sembako',
                'unit' => 'kg',
                'cost' => 25000,
                'price' => 29000,
                'stock' => 4, // Low stock test
                'min' => 10,
            ],
        ];

        foreach ($productsData as $p) {
            $product = Product::create([
                'store_id' => $store->id,
                'category_id' => $categoryModels[$p['category']]->id,
                'unit_id' => $unitModels[$p['unit']]->id,
                'sku' => $p['sku'],
                'barcode' => $p['barcode'],
                'name' => $p['name'],
                'cost_price' => $p['cost'],
                'sale_price' => $p['price'],
                'min_stock' => $p['min'],
                'is_active' => true,
                'track_stock' => true,
            ]);

            StockBalance::create([
                'store_id' => $store->id,
                'product_id' => $product->id,
                'on_hand' => $p['stock'],
                'reserved' => 0.0,
            ]);

            StockMovement::create([
                'store_id' => $store->id,
                'product_id' => $product->id,
                'type' => 'OPENING',
                'qty_delta' => $p['stock'],
                'before_qty' => 0,
                'after_qty' => $p['stock'],
                'note' => 'Stok awal setup sistem',
            ]);
        }

        // 7. Open initial cashier shift
        CashierShift::create([
            'store_id' => $store->id,
            'terminal_label' => 'Kasir-01 (Utama)',
            'opened_at' => now(),
            'opening_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'OPEN',
            'note' => 'Shift aktif kasir',
        ]);
    }
}
