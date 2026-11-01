<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Models\Store;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class History extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $startDate = '';
    public string $endDate = '';

    // Selected sale for detail & reprint
    public ?int $selectedSaleId = null;
    public bool $showDetailModal = false;
    public bool $showReprintModal = false;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function viewDetails(int $saleId): void
    {
        $this->selectedSaleId = $saleId;
        $this->showDetailModal = true;
    }

    public function reprintReceipt(int $saleId): void
    {
        $this->selectedSaleId = $saleId;
        $this->showReprintModal = true;
    }

    protected function getStoreId(): int
    {
        return Store::first()?->id ?? 1;
    }

    public function render()
    {
        $storeId = $this->getStoreId();

        $query = Sale::where('store_id', $storeId)
            ->with(['customer', 'paymentIntent', 'items.product', 'shift']);

        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', $term)
                  ->orWhereHas('customer', function ($cq) use ($term) {
                      $cq->where('name', 'like', $term);
                  });
            });
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->startDate !== '' && $this->endDate !== '') {
            $query->whereBetween('created_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59',
            ]);
        }

        $sales = $query->latest('created_at')->paginate(15);
        $selectedSale = $this->selectedSaleId ? Sale::with(['customer', 'paymentIntent', 'items.product', 'shift'])->find($this->selectedSaleId) : null;

        return view('livewire.sales.history', [
            'sales' => $sales,
            'selectedSale' => $selectedSale,
        ]);
    }
}
