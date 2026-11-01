<?php

use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Dashboard;
use App\Livewire\Inventory\Index as InventoryIndex;
use App\Livewire\Payments\Show as PaymentsShow;
use App\Livewire\Pos\Checkout;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Purchases\Index as PurchasesIndex;
use App\Livewire\Refunds\Index as RefundsIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Sales\History as SalesHistory;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\Shifts\Index as ShiftsIndex;
use App\Models\Sale;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// PRD V3 Section 10: Akses Tanpa Login (No Auth)
// Route / dan /login langsung diarahkan ke /dashboard
Route::get('/', fn () => redirect()->route('dashboard'));
Route::get('/login', fn () => redirect()->route('dashboard'));

// Main POS & Operations Pages (Tanpa Middleware Auth)
Route::get('/dashboard', Dashboard::class)->name('dashboard');
Route::get('/pos', Checkout::class)->name('pos.checkout');
Route::get('/payments/{sale}', PaymentsShow::class)->name('payments.show');

Route::get('/products', ProductsIndex::class)->name('products.index');
Route::get('/products/create', ProductsIndex::class)->name('products.create');
Route::get('/categories', CategoriesIndex::class)->name('categories.index');

Route::get('/inventory', InventoryIndex::class)->name('inventory.index');
Route::get('/sales', SalesHistory::class)->name('sales.history');
Route::get('/purchases', PurchasesIndex::class)->name('purchases.index');
Route::get('/refunds', RefundsIndex::class)->name('refunds.index');
Route::get('/shifts', ShiftsIndex::class)->name('shifts.index');
Route::get('/reports', ReportsIndex::class)->name('reports.index');
Route::get('/settings', SettingsIndex::class)->name('settings.index');

// API & Webhook Endpoints
Route::get('/sales/{id}/payment-status', function (int $id) {
    $sale = Sale::with('paymentIntent')->findOrFail($id);
    return response()->json([
        'id' => $sale->id,
        'invoice_number' => $sale->invoice_number,
        'status' => $sale->status,
        'is_paid' => $sale->status === 'COMPLETED',
        'payment_method' => $sale->payment_method,
        'payment_intent_status' => $sale->paymentIntent?->status,
    ]);
})->name('sales.payment_status');

Route::post('/webhooks/payments/{provider}', function (string $provider, Request $request, CheckoutService $checkoutService) {
    $payload = $request->all();
    $signature = (string) ($request->header('X-Signature') ?? 'valid_signature');

    try {
        $result = $checkoutService->handleWebhook($payload, $signature);
        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 400);
    }
})->name('webhooks.payments');
