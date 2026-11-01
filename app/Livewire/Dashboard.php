<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public string $period = 'today';

    public function render()
    {
        $store = Store::first() ?? Store::create(['name' => 'Toko Kasir POS']);
        $storeId = $store->id;

        $dateFilter = match ($this->period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };

        // Completed sales
        $salesQuery = Sale::where('store_id', $storeId)
            ->where('status', 'COMPLETED')
            ->whereBetween('sold_at', $dateFilter);

        $grossSales = (float) $salesQuery->sum('total_amount');
        $completedTransactionsCount = $salesQuery->count();

        // Refunds
        $refundsTotal = (float) Refund::whereHas('sale', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })->whereBetween('created_at', $dateFilter)->sum('amount');

        $netSales = max(0.0, $grossSales - $refundsTotal);

        // Total items sold
        $itemsSold = (float) SaleItem::whereHas('sale', function ($q) use ($storeId, $dateFilter) {
            $q->where('store_id', $storeId)
              ->where('status', 'COMPLETED')
              ->whereBetween('sold_at', $dateFilter);
        })->sum('quantity');

        // Low stock count & list (on_hand - reserved <= min_stock)
        $lowStockProducts = Product::where('store_id', $storeId)
            ->where('track_stock', true)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('stockBalance', function ($q) {
                $q->whereRaw('(on_hand - reserved) <= products.min_stock');
            })
            ->with(['stockBalance', 'unit'])
            ->take(5)
            ->get();

        $lowStockCount = Product::where('store_id', $storeId)
            ->where('track_stock', true)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('stockBalance', function ($q) {
                $q->whereRaw('(on_hand - reserved) <= products.min_stock');
            })
            ->count();

        // Top 5 selling products
        $topProducts = SaleItem::select('product_id', 'product_name_snapshot', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(line_total) as total_revenue'))
            ->whereHas('sale', function ($q) use ($storeId, $dateFilter) {
                $q->where('store_id', $storeId)
                  ->where('status', 'COMPLETED')
                  ->whereBetween('sold_at', $dateFilter);
            })
            ->groupBy('product_id', 'product_name_snapshot')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        // Recent sales
        $recentSales = Sale::where('store_id', $storeId)
            ->with(['customer', 'paymentIntent'])
            ->latest('created_at')
            ->take(6)
            ->get();

        return view('livewire.dashboard', [
            'grossSales' => $grossSales,
            'refundsTotal' => $refundsTotal,
            'netSales' => $netSales,
            'transactionsCount' => $completedTransactionsCount,
            'itemsSold' => $itemsSold,
            'lowStockCount' => $lowStockCount,
            'lowStockProducts' => $lowStockProducts,
            'topProducts' => $topProducts,
            'recentSales' => $recentSales,
        ]);
    }
}
