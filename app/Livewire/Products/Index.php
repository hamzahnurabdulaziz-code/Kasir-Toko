<?php

namespace App\Livewire\Products;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Store;
use App\Models\Unit;
use App\Services\InventoryService;
use Exception;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $filterCategory = null;
    public bool $showArchived = false;

    // Form modal state
    public bool $showModal = false;
    public ?int $editingProductId = null;

    public string $sku = '';
    public ?string $barcode = null;
    public string $name = '';
    public ?int $category_id = null;
    public ?int $unit_id = null;
    public float $cost_price = 0.0;
    public float $sale_price = 0.0;
    public float $min_stock = 0.0;
    public float $initial_stock = 0.0; // Only on create
    public bool $is_active = true;
    public bool $track_stock = true;

    public ?string $errorMessage = null;

    protected function getStoreId(): int
    {
        return Store::first()?->id ?? 1;
    }

    public function mount(): void
    {
        // Support direct access to /products/create or ?action=create
        if (request()->routeIs('products.create') || request()->query('action') === 'create') {
            $this->create();
        }
    }

    protected function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'track_stock' => ['boolean'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingShowArchived(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->editingProductId = null;
        $this->sku = 'PRD-' . strtoupper(substr(uniqid(), -6));
        $this->barcode = null;
        $this->name = '';
        $this->category_id = Category::where('store_id', $this->getStoreId())->first()?->id;
        $this->unit_id = Unit::first()?->id;
        $this->cost_price = 0.0;
        $this->sale_price = 0.0;
        $this->min_stock = 5.0;
        $this->initial_stock = 0.0;
        $this->is_active = true;
        $this->track_stock = true;
        $this->errorMessage = null;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->resetValidation();
        $product = Product::where('id', $id)
            ->where('store_id', $this->getStoreId())
            ->firstOrFail();

        $this->editingProductId = $product->id;
        $this->sku = $product->sku;
        $this->barcode = $product->barcode;
        $this->name = $product->name;
        $this->category_id = $product->category_id;
        $this->unit_id = $product->unit_id;
        $this->cost_price = (float) $product->cost_price;
        $this->sale_price = (float) $product->sale_price;
        $this->min_stock = (float) $product->min_stock;
        $this->initial_stock = 0.0;
        $this->is_active = $product->is_active;
        $this->track_stock = $product->track_stock;
        $this->errorMessage = null;
        $this->showModal = true;
    }

    public function save(InventoryService $inventoryService): void
    {
        $this->validate();
        $storeId = $this->getStoreId();

        // Unique SKU check per store (ignoring soft-deleted or matching current)
        $skuCheck = Product::where('store_id', $storeId)
            ->where('sku', $this->sku)
            ->when($this->editingProductId, fn ($q) => $q->where('id', '!=', $this->editingProductId))
            ->exists();

        if ($skuCheck) {
            $this->addError('sku', 'SKU ini sudah digunakan oleh produk lain.');
            return;
        }

        // Unique Barcode check per store if filled
        if (!empty($this->barcode)) {
            $barcodeCheck = Product::where('store_id', $storeId)
                ->where('barcode', $this->barcode)
                ->when($this->editingProductId, fn ($q) => $q->where('id', '!=', $this->editingProductId))
                ->exists();

            if ($barcodeCheck) {
                $this->addError('barcode', 'Barcode ini sudah digunakan oleh produk lain.');
                return;
            }
        }

        try {
            if ($this->editingProductId) {
                $product = Product::where('id', $this->editingProductId)
                    ->where('store_id', $storeId)
                    ->firstOrFail();

                $oldValues = $product->toArray();

                $product->update([
                    'category_id' => $this->category_id,
                    'unit_id' => $this->unit_id,
                    'sku' => $this->sku,
                    'barcode' => $this->barcode ?: null,
                    'name' => $this->name,
                    'cost_price' => $this->cost_price,
                    'sale_price' => $this->sale_price,
                    'min_stock' => $this->min_stock,
                    'is_active' => $this->is_active,
                    'track_stock' => $this->track_stock,
                ]);

                AuditLog::log(
                    storeId: $storeId,
                    event: 'PRODUCT_UPDATED',
                    entityType: Product::class,
                    entityId: $product->id,
                    oldValues: $oldValues,
                    newValues: $product->toArray()
                );

                session()->flash('success', "Produk '{$product->name}' berhasil diperbarui.");
            } else {
                $product = Product::create([
                    'store_id' => $storeId,
                    'category_id' => $this->category_id,
                    'unit_id' => $this->unit_id,
                    'sku' => $this->sku,
                    'barcode' => $this->barcode ?: null,
                    'name' => $this->name,
                    'cost_price' => $this->cost_price,
                    'sale_price' => $this->sale_price,
                    'min_stock' => $this->min_stock,
                    'is_active' => $this->is_active,
                    'track_stock' => $this->track_stock,
                ]);

                // Create initial stock balance if tracked
                if ($this->track_stock) {
                    if ($this->initial_stock > 0) {
                        $inventoryService->adjustStock(
                            product: $product,
                            adjustmentQty: $this->initial_stock,
                            type: 'OPENING',
                            reason: 'Stok awal saat penambahan produk'
                        );
                    } else {
                        StockBalance::create([
                            'store_id' => $storeId,
                            'product_id' => $product->id,
                            'on_hand' => 0.0,
                            'reserved' => 0.0,
                        ]);
                    }
                }

                AuditLog::log(
                    storeId: $storeId,
                    event: 'PRODUCT_CREATED',
                    entityType: Product::class,
                    entityId: $product->id,
                    newValues: $product->toArray()
                );

                session()->flash('success', "Produk baru '{$product->name}' berhasil ditambahkan.");
            }

            $this->showModal = false;
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function toggleActive(int $id): void
    {
        $product = Product::where('id', $id)
            ->where('store_id', $this->getStoreId())
            ->firstOrFail();

        $product->update(['is_active' => !$product->is_active]);

        AuditLog::log(
            storeId: $this->getStoreId(),
            event: 'PRODUCT_STATUS_TOGGLED',
            entityType: Product::class,
            entityId: $product->id,
            newValues: ['is_active' => $product->is_active]
        );

        session()->flash('success', "Status produk '{$product->name}' diubah menjadi " . ($product->is_active ? 'Aktif' : 'Nonaktif') . '.');
    }

    /**
     * Archive (Soft Delete) as per PRD V3 AT-07
     */
    public function archiveProduct(int $id): void
    {
        $storeId = $this->getStoreId();
        $product = Product::where('id', $id)->where('store_id', $storeId)->firstOrFail();
        $name = $product->name;
        $product->delete();

        AuditLog::log(
            storeId: $storeId,
            event: 'PRODUCT_ARCHIVED',
            entityType: Product::class,
            entityId: $id,
            oldValues: ['name' => $name]
        );

        session()->flash('success', "Produk '{$name}' berhasil diarsipkan (soft-deleted).");
    }

    public function restoreProduct(int $id): void
    {
        $storeId = $this->getStoreId();
        $product = Product::onlyTrashed()->where('id', $id)->where('store_id', $storeId)->firstOrFail();
        $product->restore();

        AuditLog::log(
            storeId: $storeId,
            event: 'PRODUCT_RESTORED',
            entityType: Product::class,
            entityId: $id
        );

        session()->flash('success', "Produk '{$product->name}' berhasil dipulihkan.");
    }

    public function render()
    {
        $storeId = $this->getStoreId();

        $categories = Category::where('store_id', $storeId)->get();
        $units = Unit::all();

        $query = $this->showArchived 
            ? Product::onlyTrashed()->where('store_id', $storeId)
            : Product::where('store_id', $storeId);

        $query->with(['category', 'unit', 'stockBalance']);

        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('sku', 'like', $term)
                  ->orWhere('barcode', 'like', $term);
            });
        }

        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        $products = $query->orderBy('name')->paginate(12);

        return view('livewire.products.index', [
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
        ]);
    }
}
