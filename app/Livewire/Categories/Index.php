<?php

namespace App\Livewire\Categories;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Store;
use App\Models\Unit;
use Exception;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    // Category form
    public bool $showCategoryModal = false;
    public ?int $editingCategoryId = null;
    public string $categoryName = '';
    public bool $categoryIsActive = true;

    // Unit form
    public bool $showUnitModal = false;
    public string $unitName = '';
    public string $unitSymbol = '';

    public ?string $errorMessage = null;

    protected function getStoreId(): int
    {
        return Store::first()?->id ?? 1;
    }

    public function createCategory(): void
    {
        $this->resetValidation();
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->categoryIsActive = true;
        $this->errorMessage = null;
        $this->showCategoryModal = true;
    }

    public function editCategory(int $id): void
    {
        $this->resetValidation();
        $cat = Category::where('id', $id)
            ->where('store_id', $this->getStoreId())
            ->firstOrFail();

        $this->editingCategoryId = $cat->id;
        $this->categoryName = $cat->name;
        $this->categoryIsActive = $cat->is_active;
        $this->errorMessage = null;
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        $this->validate([
            'categoryName' => ['required', 'string', 'max:100'],
            'categoryIsActive' => ['boolean'],
        ]);

        $storeId = $this->getStoreId();

        if ($this->editingCategoryId) {
            $cat = Category::where('id', $this->editingCategoryId)
                ->where('store_id', $storeId)
                ->firstOrFail();

            $cat->update([
                'name' => $this->categoryName,
                'is_active' => $this->categoryIsActive,
            ]);

            session()->flash('success', "Kategori '{$cat->name}' berhasil diperbarui.");
        } else {
            $cat = Category::create([
                'store_id' => $storeId,
                'name' => $this->categoryName,
                'is_active' => $this->categoryIsActive,
            ]);

            session()->flash('success', "Kategori baru '{$cat->name}' berhasil dibuat.");
        }

        $this->showCategoryModal = false;
    }

    public function createUnit(): void
    {
        $this->resetValidation();
        $this->unitName = '';
        $this->unitSymbol = '';
        $this->errorMessage = null;
        $this->showUnitModal = true;
    }

    public function saveUnit(): void
    {
        $this->validate([
            'unitName' => ['required', 'string', 'max:50'],
            'unitSymbol' => ['required', 'string', 'max:20'],
        ]);

        Unit::create([
            'name' => $this->unitName,
            'symbol' => strtolower($this->unitSymbol),
        ]);

        session()->flash('success', "Satuan '{$this->unitName}' berhasil ditambahkan.");
        $this->showUnitModal = false;
    }

    public function render()
    {
        $storeId = $this->getStoreId();

        $categories = Category::where('store_id', $storeId)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $units = Unit::withCount('products')->orderBy('name')->get();

        return view('livewire.categories.index', [
            'categories' => $categories,
            'units' => $units,
        ]);
    }
}
