<?php

namespace App\Livewire\Payments;

use App\Models\Sale;
use App\Services\CheckoutService;
use Exception;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public int $saleId;

    public function mount(Sale|int $sale): void
    {
        $this->saleId = $sale instanceof Sale ? $sale->id : (int) $sale;
    }

    public function checkStatus(): void
    {
        // Polled automatically every 3 seconds by Livewire
    }

    public function simulateGatewayPayment(CheckoutService $checkoutService): void
    {
        $sale = Sale::with('paymentIntent')->findOrFail($this->saleId);

        if ($sale->status === 'COMPLETED') {
            return;
        }

        try {
            $txId = 'TX-' . strtoupper(Str::random(12));
            $checkoutService->confirmPayment(
                sale: $sale,
                providerTransactionId: $txId,
                amountPaid: (float) $sale->total_amount,
                payload: [
                    'source' => 'SANDBOX_SIMULATOR',
                    'timestamp' => now()->toISOString(),
                ]
            );

            session()->flash('success', 'Pembayaran QRIS berhasil disimulasikan dan dikonfirmasi oleh gateway!');
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelPayment(CheckoutService $checkoutService): void
    {
        $sale = Sale::with('paymentIntent')->findOrFail($this->saleId);
        $checkoutService->cancelOrExpireSale($sale, 'CANCELLED');
        session()->flash('success', 'Transaksi berhasil dibatalkan dan reservasi stok dilepas.');
        $this->redirect(route('pos.checkout'));
    }

    public function render()
    {
        $sale = Sale::with(['items.product', 'paymentIntent', 'customer'])->findOrFail($this->saleId);

        return view('livewire.payments.show', [
            'sale' => $sale,
            'intent' => $sale->paymentIntent,
        ]);
    }
}
