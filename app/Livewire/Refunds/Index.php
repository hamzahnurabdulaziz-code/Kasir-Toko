<?php

namespace App\Livewire\Refunds;

use App\Models\Refund;
use App\Models\Sale;
use App\Models\Store;
use App\Services\RefundService;
use Exception;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $invoiceSearch = '';
    public ?Sale $foundSale = null;

    // Refund form
    public array $refundItems = []; // [sale_item_id => ['quantity' => 0, 'restore_stock' => true, 'note' => '']]
    public string $reason = '';
    public string $refundMethod = 'CASH';
    public ?string $errorMessage = null;

    protected function getStoreId(): int
    {
        return Store::first()?->id ?? 1;
    }

    public function mount(): void
    {
        if (request()->has('invoice')) {
            $this->invoiceSearch = (string) request()->query('invoice');
            $this->searchInvoice();
        }
    }

    public function searchInvoice(): void
    {
        $this->errorMessage = null;
        $this->refundItems = [];

        $storeId = $this->getStoreId();
        $sale = Sale::where('store_id', $storeId)
            ->where(function ($q) {
                $term = trim($this->invoiceSearch);
                $q->where('invoice_number', $term);
            })
            ->with(['items.refundItems', 'customer', 'shift'])
            ->first();

        if (!$sale) {
            $this->errorMessage = 'Faktur penjualan dengan nomor tersebut tidak ditemukan.';
            $this->foundSale = null;
            return;
        }

        if ($sale->status === 'REFUNDED') {
            $this->errorMessage = 'Faktur penjualan ini sudah diretur secara penuh sebelumnya.';
            $this->foundSale = null;
            return;
        }

        $this->foundSale = $sale;

        // Initialize items
        foreach ($sale->items as $item) {
            $refundable = $item->refundableQuantity();
            if ($refundable > 0) {
                $this->refundItems[$item->id] = [
                    'sale_item_id' => $item->id,
                    'quantity' => 0,
                    'max' => $refundable,
                    'restore_stock' => true,
                    'note' => '',
                ];
            }
        }
    }

    public function processRefund(RefundService $refundService): void
    {
        $this->errorMessage = null;

        if (!$this->foundSale) {
            $this->errorMessage = 'Pilih faktur terlebih dahulu.';
            return;
        }

        if (trim($this->reason) === '') {
            $this->errorMessage = 'Alasan retur wajib diisi.';
            return;
        }

        $itemsToProcess = [];
        foreach ($this->refundItems as $saleItemId => $data) {
            $qty = (float) $data['quantity'];
            if ($qty > 0) {
                $itemsToProcess[] = [
                    'sale_item_id' => $saleItemId,
                    'quantity' => $qty,
                    'restore_stock' => (bool) $data['restore_stock'],
                    'note' => $data['note'] ?? null,
                ];
            }
        }

        if (empty($itemsToProcess)) {
            $this->errorMessage = 'Masukkan jumlah barang yang akan diretur minimal 1 item.';
            return;
        }

        try {
            $refundService->processRefund([
                'sale_id' => $this->foundSale->id,
                'reason' => $this->reason,
                'refund_method' => $this->refundMethod,
                'items' => $itemsToProcess,
            ]);

            session()->flash('success', 'Retur barang dan pengembalian dana berhasil diproses.');
            $this->foundSale = null;
            $this->invoiceSearch = '';
            $this->reason = '';
            $this->refundItems = [];
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $storeId = $this->getStoreId();

        $refunds = Refund::whereHas('sale', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
        ->with(['sale', 'items.saleItem.product'])
        ->latest()
        ->paginate(10);

        return view('livewire.refunds.index', [
            'refunds' => $refunds,
        ]);
    }
}
