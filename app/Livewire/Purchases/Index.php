<?php

namespace App\Livewire\Purchases;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\PurchaseReceivingService;
use Exception;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    // Create PO Modal
    public bool $showCreateModal = false;
    public ?int $supplierId = null;
    public string $note = '';
    public array $items = []; // array of ['product_id', 'quantity', 'unit_cost']

    // Detail Modal
    public bool $showDetailModal = false;
    public ?int $selectedPurchaseId = null;

    public ?string $errorMessage = null;

    protected function getStoreId(): int
    {
        return Store::first()?->id ?? 1;
    }

    public function openCreateModal(): void
    {
        $this->supplierId = Supplier::where('store_id', $this->getStoreId())->first()?->id;
        $this->note = '';
        $this->items = [
            ['product_id' => '', 'quantity' => 1, 'unit_cost' => 0],
        ];
        $this->errorMessage = null;
        $this->showCreateModal = true;
    }

    public function addItemRow(): void
    {
        $this->items[] = ['product_id' => '', 'quantity' => 1, 'unit_cost' => 0];
    }

    public function removeItemRow(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function onProductSelected(int $index, $productId): void
    {
        $product = Product::find($productId);
        if ($product) {
            $this->items[$index]['unit_cost'] = (float) $product->cost_price;
        }
    }

    public function savePurchase(PurchaseReceivingService $service): void
    {
        $this->errorMessage = null;

        if (!$this->supplierId) {
            $this->errorMessage = 'Pilih pemasok / supplier terlebih dahulu.';
            return;
        }

        $validItems = [];
        foreach ($this->items as $item) {
            if (!empty($item['product_id']) && (float) $item['quantity'] > 0) {
                $validItems[] = [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'unit_cost' => (float) $item['unit_cost'],
                ];
            }
        }

        if (empty($validItems)) {
            $this->errorMessage = 'Masukkan setidaknya satu produk yang valid.';
            return;
        }

        try {
            $purchase = $service->createDraftPurchase([
                'store_id' => $this->getStoreId(),
                'supplier_id' => $this->supplierId,
                'items' => $validItems,
                'note' => $this->note,
            ]);

            session()->flash('success', "Draft pembelian PO '{$purchase->purchase_no}' berhasil dibuat.");
            $this->showCreateModal = false;
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function viewDetails(int $id): void
    {
        $this->selectedPurchaseId = $id;
        $this->showDetailModal = true;
    }

    public function receivePurchase(int $id, PurchaseReceivingService $service): void
    {
        try {
            $purchase = Purchase::where('id', $id)
                ->where('store_id', $this->getStoreId())
                ->firstOrFail();

            $service->receivePurchase($purchase);
            session()->flash('success', "Barang untuk PO '{$purchase->purchase_no}' telah diterima dan stok bertambah.");
            $this->showDetailModal = false;
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $storeId = $this->getStoreId();

        $purchases = Purchase::where('store_id', $storeId)
            ->with(['supplier', 'items.product'])
            ->latest()
            ->paginate(10);

        $suppliers = Supplier::where('store_id', $storeId)->get();
        $products = Product::where('store_id', $storeId)->where('is_active', true)->orderBy('name')->get();

        $selectedPurchase = $this->selectedPurchaseId ? Purchase::with(['supplier', 'items.product.unit'])->find($this->selectedPurchaseId) : null;

        return view('livewire.purchases.index', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'products' => $products,
            'selectedPurchase' => $selectedPurchase,
        ]);
    }
}
