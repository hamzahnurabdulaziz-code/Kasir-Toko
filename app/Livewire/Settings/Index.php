<?php

namespace App\Livewire\Settings;

use App\Models\AuditLog;
use App\Models\Store;
use Exception;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $name = '';
    public string $address = '';
    public string $phone = '';
    public string $receipt_prefix = 'INV-';
    public string $receipt_footer = '';
    public float $tax_percentage = 0.0;
    public string $qris_merchant_name = '';
    public string $qris_merchant_id = '';

    public ?string $errorMessage = null;

    protected function getStore(): Store
    {
        return Store::first() ?? Store::create([
            'name' => 'Toko Kasir Modern',
            'receipt_prefix' => 'INV-',
            'qris_merchant_name' => 'TOKO KASIR MODERN',
            'qris_merchant_id' => 'MID-DEMO-001',
        ]);
    }

    public function mount(): void
    {
        $store = $this->getStore();
        $this->name = $store->name;
        $this->address = $store->address ?? '';
        $this->phone = $store->phone ?? '';
        $this->receipt_prefix = $store->receipt_prefix ?? 'INV-';
        $this->receipt_footer = $store->receipt_footer ?? '';
        $this->tax_percentage = (float) $store->tax_percentage;
        $this->qris_merchant_name = $store->qris_merchant_name ?? '';
        $this->qris_merchant_id = $store->qris_merchant_id ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'receipt_prefix' => ['required', 'string', 'max:20'],
            'receipt_footer' => ['nullable', 'string'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'qris_merchant_name' => ['required', 'string', 'max:100'],
            'qris_merchant_id' => ['required', 'string', 'max:100'],
        ]);

        try {
            $store = $this->getStore();
            $oldValues = $store->toArray();

            $store->update([
                'name' => $this->name,
                'address' => $this->address ?: null,
                'phone' => $this->phone ?: null,
                'receipt_prefix' => strtoupper($this->receipt_prefix),
                'receipt_footer' => $this->receipt_footer ?: null,
                'tax_percentage' => $this->tax_percentage,
                'qris_merchant_name' => $this->qris_merchant_name,
                'qris_merchant_id' => $this->qris_merchant_id,
            ]);

            AuditLog::log(
                storeId: $store->id,
                event: 'STORE_SETTINGS_UPDATED',
                entityType: Store::class,
                entityId: $store->id,
                oldValues: $oldValues,
                newValues: $store->toArray()
            );

            session()->flash('success', 'Pengaturan identitas toko, QRIS dinamis, dan struk berhasil diperbarui.');
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.settings.index');
    }
}
