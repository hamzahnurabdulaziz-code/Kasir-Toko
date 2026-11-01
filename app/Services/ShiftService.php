<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Models\CashierShift;
use Exception;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    /**
     * Open a new shift
     */
    public function openShift(int $storeId, float $openingCash, string $terminalLabel = 'Kasir-01', ?string $note = null): CashierShift
    {
        $existing = CashierShift::where('store_id', $storeId)
            ->where('status', 'OPEN')
            ->first();

        if ($existing) {
            throw new Exception('Shift kasir sudah aktif. Tutup shift sebelumnya terlebih dahulu.');
        }

        return DB::transaction(function () use ($storeId, $openingCash, $terminalLabel, $note) {
            $shift = CashierShift::create([
                'store_id' => $storeId,
                'terminal_label' => $terminalLabel,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'expected_cash' => $openingCash,
                'status' => 'OPEN',
                'note' => $note,
            ]);

            AuditLog::log(
                storeId: $storeId,
                event: 'SHIFT_OPENED',
                entityType: CashierShift::class,
                entityId: $shift->id,
                newValues: ['opening_cash' => $openingCash, 'terminal' => $terminalLabel]
            );

            return $shift;
        });
    }

    /**
     * Record Cash In or Cash Out during shift
     */
    public function recordCashMovement(CashierShift $shift, string $type, float $amount, string $note): CashMovement
    {
        if (!$shift->isOpen()) {
            throw new Exception('Shift kasir sudah ditutup.');
        }

        if (!in_array($type, ['CASH_IN', 'CASH_OUT'])) {
            throw new Exception('Tipe mutasi kas harus CASH_IN atau CASH_OUT.');
        }

        if ($amount <= 0) {
            throw new Exception('Nominal mutasi kas harus lebih dari 0.');
        }

        return DB::transaction(function () use ($shift, $type, $amount, $note) {
            $movement = CashMovement::create([
                'shift_id' => $shift->id,
                'type' => $type,
                'amount' => $amount,
                'note' => $note,
            ]);

            if ($type === 'CASH_IN') {
                $shift->increment('expected_cash', $amount);
            } else {
                $shift->decrement('expected_cash', $amount);
            }

            return $movement;
        });
    }

    /**
     * Close shift and reconcile physical cash
     */
    public function closeShift(CashierShift $shift, float $actualCash, ?string $note = null): CashierShift
    {
        if (!$shift->isOpen()) {
            throw new Exception('Shift kasir sudah ditutup.');
        }

        return DB::transaction(function () use ($shift, $actualCash, $note) {
            /** @var CashierShift $lockedShift */
            $lockedShift = CashierShift::where('id', $shift->id)
                ->lockForUpdate()
                ->firstOrFail();

            $expected = (float) $lockedShift->expected_cash;
            $difference = $actualCash - $expected;

            $lockedShift->update([
                'closed_at' => now(),
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'status' => 'CLOSED',
                'note' => $note ?: $lockedShift->note,
            ]);

            AuditLog::log(
                storeId: $lockedShift->store_id,
                event: 'SHIFT_CLOSED',
                entityType: CashierShift::class,
                entityId: $lockedShift->id,
                newValues: [
                    'expected_cash' => $expected,
                    'actual_cash' => $actualCash,
                    'difference' => $difference,
                ]
            );

            return $lockedShift;
        });
    }
}
