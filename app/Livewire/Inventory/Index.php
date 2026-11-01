<?php

namespace App\Livewire\Inventory;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Store;
use App\Services\InventoryService;
use Exception;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $activeTab = 'balances'; // 'balances' or 'movements'

    // Balances filter
    public string $searchProduct = '';
    public bool $onlyLowStock = false;

    // Movements filter
    public string $filterMovementType = '';
    public ?int $filterProductId = null;

    // Adjustment modal
    public bool $showAdjustModal = false;
    public ?int $adjustProductId = null;
    public float $adjustDelta = 0.0;
    public string $adjustReason = '';
    public ?string $errorMessage = null;

    protected function getStoreId(): int
    {
        return Store::first()?->id ?? 1;
    }

    public function openAdjustModal(int $productId): void
    {
        $this->adjustProductId = $productId;
        $this->adjustDelta = 0.0;
        $this->adjustReason = '';
        $this->errorMessage = null;
        $this->showAdjustModal = true;
    }

    public function saveAdjustment(InventoryService $inventoryService): void
    {
        $this->errorMessage = null;

        if ($this->adjustDelta == 0) {
            $this->errorMessage = 'Nominal perubahan stok tidak boleh 0.';
            return;
        }

        if (trim($this->adjustReason) === '') {
            $this->errorMessage = 'Alasan penyesuaian stok wajib diisi.';
            return;
        }

        try {
            $product = Product::where('id', $this->adjustProductId)
                ->where('store_id', $this->getStoreId())
                ->firstOrFail();

            $inventoryService->adjustStock(
                product: $product,
                adjustmentQty: $this->adjustDelta,
                type: 'ADJUSTMENT',
                reason: $this->adjustReason
            );

            session()->flash('success', "Penyesuaian stok produk '{$product->name}' berhasil dicatat.");
            $this->showAdjustModal = false;
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $storeId = $this->getStoreId();

        // Balances tab query
        $balancesQuery = Product::where('store_id', $storeId)
            ->where('track_stock', true)
            ->with(['category', 'unit', 'stockBalance']);

        if (trim($this->searchProduct) !== '') {
            $term = '%' . trim($this->searchProduct) . '%';
            $balancesQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('sku', 'like', $term)
                  ->orWhere('barcode', 'like', $term);
            });
        }

        if ($this->onlyLowStock) {
            $balancesQuery->whereHas('stockBalance', function ($q) {
                $q->whereRaw('(on_hand - reserved) <= products.min_stock');
            });
        }

        $balances = $balancesQuery->orderBy('name')->paginate(12, ['*'], 'balancesPage');

        // Movements tab query
        $movementsQuery = StockMovement::where('store_id', $storeId)
            ->with(['product.unit']);

        if ($this->filterMovementType !== '') {
            $movementsQuery->where('type', $this->filterMovementType);
        }

        if ($this->filterProductId) {
            $movementsQuery->where('product_id', $this->filterProductId);
        }

        $movements = $movementsQuery->latest('created_at')->paginate(15, ['*'], 'movementsPage');

        $allTrackedProducts = Product::where('store_id', $storeId)->where('track_stock', true)->orderBy('name')->get();
        $adjustingProduct = $this->adjustProductId ? Product::with('stockBalance', 'unit')->find($this->adjustProductId) : null;

        return view('livewire.inventory.index', [
            'balances' => $balances,
            'movements' => $movements,
            'allTrackedProducts' => $allTrackedProducts,
            'adjustingProduct' => $adjustingProduct,
        ]);
    }
}
