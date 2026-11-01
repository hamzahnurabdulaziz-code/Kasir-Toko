<?php

namespace App\Livewire\Shifts;

use App\Models\CashierShift;
use App\Models\Sale;
use App\Models\Store;
use App\Services\ShiftService;
use Exception;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    // Modals
    public bool $showOpenShiftModal = false;
    public bool $showCashMovementModal = false;
    public bool $showCloseShiftModal = false;

    // Open Shift Form
    public float $openingCash = 0.0;
    public string $terminalLabel = 'Kasir-01';
    public string $openShiftNote = '';

    // Cash Movement Form (Cash In / Cash Out)
    public string $movementType = 'CASH_IN';
    public float $movementAmount = 0.0;
    public string $movementNote = '';

    // Close Shift Form
    public float $actualCash = 0.0;
    public string $closeShiftNote = '';

    public ?string $errorMessage = null;

    protected function getStore(): Store
    {
        return Store::first() ?? Store::create([
            'name' => 'Toko Kasir Modern',
            'code' => 'STORE-01',
            'qris_merchant_name' => 'TOKO KASIR MODERN',
            'qris_merchant_id' => 'MID-DEMO-001',
        ]);
    }

    public function openShift(ShiftService $shiftService): void
    {
        $this->errorMessage = null;
        try {
            $shiftService->openShift(
                storeId: $this->getStore()->id,
                openingCash: $this->openingCash,
                terminalLabel: $this->terminalLabel ?: 'Kasir-01',
                note: $this->openShiftNote ?: 'Shift kasir baru'
            );
            $this->showOpenShiftModal = false;
            $this->openingCash = 0.0;
            session()->flash('success', 'Shift kasir berhasil dibuka.');
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function recordCashMovement(ShiftService $shiftService): void
    {
        $this->errorMessage = null;
        $activeShift = $this->getStore()->activeShift();

        if (!$activeShift) {
            $this->errorMessage = 'Tidak ada shift kasir yang sedang aktif.';
            return;
        }

        if (trim($this->movementNote) === '') {
            $this->errorMessage = 'Alasan mutasi kas wajib diisi.';
            return;
        }

        try {
            $shiftService->recordCashMovement(
                shift: $activeShift,
                type: $this->movementType,
                amount: $this->movementAmount,
                note: $this->movementNote
            );
            $this->showCashMovementModal = false;
            $this->movementAmount = 0.0;
            $this->movementNote = '';
            session()->flash('success', 'Mutasi kas laci berhasil dicatat.');
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function closeShift(ShiftService $shiftService): void
    {
        $this->errorMessage = null;
        $activeShift = $this->getStore()->activeShift();

        if (!$activeShift) {
            $this->errorMessage = 'Tidak ada shift kasir yang sedang aktif.';
            return;
        }

        try {
            $shiftService->closeShift(
                shift: $activeShift,
                actualCash: $this->actualCash,
                note: $this->closeShiftNote
            );
            $this->showCloseShiftModal = false;
            $this->actualCash = 0.0;
            session()->flash('success', 'Shift kasir berhasil ditutup dan kas telah direkonsiliasi.');
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $store = $this->getStore();
        $activeShift = $store->activeShift();

        // Calculate active shift metrics if active
        $cashSales = 0.0;
        $nonCashSales = 0.0;
        $cashIn = 0.0;
        $cashOut = 0.0;

        if ($activeShift) {
            $cashSales = (float) Sale::where('shift_id', $activeShift->id)
                ->where('status', 'COMPLETED')
                ->where('payment_method', 'CASH')
                ->sum('total_amount');

            $nonCashSales = (float) Sale::where('shift_id', $activeShift->id)
                ->where('status', 'COMPLETED')
                ->where('payment_method', '!=', 'CASH')
                ->sum('total_amount');

            $cashIn = (float) $activeShift->cashMovements()->where('type', 'CASH_IN')->sum('amount');
            $cashOut = (float) $activeShift->cashMovements()->where('type', 'CASH_OUT')->sum('amount');
        }

        $shifts = CashierShift::where('store_id', $store->id)
            ->with(['cashMovements'])
            ->latest('opened_at')
            ->paginate(10);

        return view('livewire.shifts.index', [
            'activeShift' => $activeShift,
            'cashSales' => $cashSales,
            'nonCashSales' => $nonCashSales,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'shifts' => $shifts,
        ]);
    }
}
