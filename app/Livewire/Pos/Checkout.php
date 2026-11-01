<?php

namespace App\Livewire\Pos;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Services\CheckoutService;
use App\Services\ShiftService;
use Exception;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Checkout extends Component
{
    public string $search = '';
    public ?int $selectedCategoryId = null;

    public array $cart = []; // [product_id => ['product_id', 'name', 'price', 'quantity', 'unit', 'discount', 'stock', 'track_stock']]

    public ?int $customerId = null;
    public float $cartDiscount = 0.0;
    public bool $applyTax = false;
    public string $note = '';
    public string $idempotencyKey = '';

    // Payment State
    public bool $showPaymentModal = false;
    public string $paymentMethod = 'CASH';
    public float $tenderedAmount = 0.0;
    public ?string $errorMessage = null;

    // Receipt Modal State
    public bool $showReceiptModal = false;
    public ?Sale $completedSale = null;

    public function mount(): void
    {
        $this->idempotencyKey = (string) Str::uuid();
        $store = Store::first();
        if ($store) {
            $this->customerId = Customer::where('store_id', $store->id)->first()?->id;
        }
    }

    public function updatedSearch(): void
    {
        $query = trim($this->search);
        if ($query === '') {
            return;
        }

        $store = Store::first();
        $matched = Product::where('store_id', $store->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($query) {
                $q->where('barcode', $query)
                  ->orWhere('sku', $query);
            })
            ->first();

        if ($matched) {
            $this->addToCart($matched->id);
            $this->search = '';
        }
    }

    public function addToCart(int $productId): void
    {
        $store = Store::first();
        /** @var Product $product */
        $product = Product::where('id', $productId)
            ->where('store_id', $store->id)
            ->whereNull('deleted_at')
            ->with(['unit', 'stockBalance'])
            ->firstOrFail();

        if (!$product->is_active) {
            $this->errorMessage = "Produk '{$product->name}' sedang dinonaktifkan.";
            return;
        }

        $availableStock = $product->available_stock;

        if (isset($this->cart[$productId])) {
            $newQty = $this->cart[$productId]['quantity'] + 1;
            if ($product->track_stock && $newQty > $availableStock) {
                $this->errorMessage = "Stok tersedia '{$product->name}' tidak mencukupi (tersedia: {$availableStock}).";
                return;
            }
            $this->cart[$productId]['quantity'] = $newQty;
        } else {
            if ($product->track_stock && $availableStock < 1) {
                $this->errorMessage = "Stok tersedia '{$product->name}' habis (sedang dipesan atau 0).";
                return;
            }

            $this->cart[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->sale_price,
                'cost' => (float) $product->cost_price,
                'quantity' => 1.0,
                'unit' => $product->unit->symbol,
                'discount' => 0.0,
                'stock' => $availableStock,
                'track_stock' => $product->track_stock,
            ];
        }

        $this->errorMessage = null;
    }

    public function updateQuantity(int $productId, float $quantity): void
    {
        if (!isset($this->cart[$productId])) {
            return;
        }

        if ($quantity <= 0) {
            $this->removeFromCart($productId);
            return;
        }

        $product = Product::find($productId);
        $available = $product ? $product->available_stock : 0;

        if ($this->cart[$productId]['track_stock'] && $quantity > $available) {
            $this->errorMessage = "Kuantitas melebihi sisa stok tersedia ({$available}).";
            $this->cart[$productId]['quantity'] = $available;
            return;
        }

        $this->cart[$productId]['quantity'] = $quantity;
        $this->errorMessage = null;
    }

    public function incrementQuantity(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->updateQuantity($productId, $this->cart[$productId]['quantity'] + 1);
        }
    }

    public function decrementQuantity(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->updateQuantity($productId, $this->cart[$productId]['quantity'] - 1);
        }
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->cartDiscount = 0.0;
        $this->note = '';
        $this->errorMessage = null;
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function getSubtotalProperty(): float
    {
        $subtotal = 0.0;
        foreach ($this->cart as $item) {
            $line = ($item['price'] * $item['quantity']) - ($item['discount'] ?? 0);
            $subtotal += max(0.0, $line);
        }
        return $subtotal;
    }

    public function getTaxAmountProperty(): float
    {
        if (!$this->applyTax) {
            return 0.0;
        }

        $store = Store::first();
        $rate = (float) ($store->tax_percentage ?? 0);
        $taxableBase = max(0.0, $this->subtotal - $this->cartDiscount);

        return round(($taxableBase * $rate) / 100, 2);
    }

    public function getTotalAmountProperty(): float
    {
        return max(0.0, ($this->subtotal - $this->cartDiscount) + $this->taxAmount);
    }

    public function openPaymentModal(string $method = 'CASH'): void
    {
        if (empty($this->cart)) {
            $this->errorMessage = 'Keranjang transaksi masih kosong!';
            return;
        }

        $this->paymentMethod = 'CASH';
        $this->tenderedAmount = $this->totalAmount;
        $this->showPaymentModal = true;
        $this->errorMessage = null;
    }

    public function setQuickCash(float $amount): void
    {
        $this->tenderedAmount = $amount;
    }

    public function processCheckout(CheckoutService $checkoutService): void
    {
        $this->errorMessage = null;
        $store = Store::first();
        $shift = $store?->activeShift();

        if ($this->tenderedAmount < $this->totalAmount) {
            $this->errorMessage = 'Uang tunai yang diterima kurang dari total belanja.';
            return;
        }

        try {
            $itemsPayload = [];
            foreach ($this->cart as $item) {
                $itemsPayload[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'discount' => $item['discount'] ?? 0.0,
                ];
            }

            // Step 1: Initiate sale & reserve stock
            $sale = $checkoutService->initiateCheckout([
                'store_id' => $store->id,
                'shift_id' => $shift?->id,
                'customer_id' => $this->customerId,
                'items' => $itemsPayload,
                'discount_amount' => $this->cartDiscount,
                'tax_amount' => $this->taxAmount,
                'payment_method' => 'CASH',
                'idempotency_key' => $this->idempotencyKey,
                'note' => $this->note,
            ]);

            // Confirm cash payment immediately
            $txId = 'CASH-' . strtoupper(Str::random(8));
            $sale = $checkoutService->confirmPayment(
                sale: $sale,
                providerTransactionId: $txId,
                amountPaid: (float) $sale->total_amount,
                payload: [
                    'tendered' => $this->tenderedAmount,
                    'change' => max(0.0, $this->tenderedAmount - $this->totalAmount),
                ]
            );

            $this->completedSale = $sale;
            $this->showPaymentModal = false;
            $this->showReceiptModal = true;
            $this->clearCart();
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function closeReceipt(): void
    {
        $this->showReceiptModal = false;
        $this->completedSale = null;
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function render()
    {
        $store = Store::first();
        $storeId = $store ? $store->id : 1;

        $categories = Category::where('store_id', $storeId)->where('is_active', true)->get();
        $customers = Customer::where('store_id', $storeId)->get();

        $productsQuery = Product::where('store_id', $storeId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->with(['unit', 'category', 'stockBalance']);

        if ($this->selectedCategoryId) {
            $productsQuery->where('category_id', $this->selectedCategoryId);
        }

        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $productsQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('sku', 'like', $term)
                  ->orWhere('barcode', 'like', $term);
            });
        }

        $products = $productsQuery->orderBy('name')->take(24)->get();

        return view('livewire.pos.checkout', [
            'store' => $store,
            'categories' => $categories,
            'customers' => $customers,
            'products' => $products,
            'subtotal' => $this->subtotal,
            'taxAmount' => $this->taxAmount,
            'totalAmount' => $this->totalAmount,
            'changeAmount' => max(0.0, $this->tenderedAmount - $this->totalAmount),
        ]);
    }
}
