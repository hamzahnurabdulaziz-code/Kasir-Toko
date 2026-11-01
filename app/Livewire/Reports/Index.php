<?php

namespace App\Livewire\Reports;

use App\Models\Refund;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $startDate = '';
    public string $endDate = '';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function setPreset(string $preset): void
    {
        if ($preset === 'today') {
            $this->startDate = now()->toDateString();
            $this->endDate = now()->toDateString();
        } elseif ($preset === 'week') {
            $this->startDate = now()->subDays(6)->toDateString();
            $this->endDate = now()->toDateString();
        } elseif ($preset === 'month') {
            $this->startDate = now()->startOfMonth()->toDateString();
            $this->endDate = now()->toDateString();
        }
    }

    protected function getStoreId(): int
    {
        return Store::first()?->id ?? 1;
    }

    public function render()
    {
        $storeId = $this->getStoreId();

        $dateRange = [
            $this->startDate . ' 00:00:00',
            $this->endDate . ' 23:59:59',
        ];

        // Completed / revenue-generating sales in range
        $sales = Sale::where('store_id', $storeId)
            ->whereIn('status', ['COMPLETED', 'PARTIALLY_REFUNDED', 'REFUNDED'])
            ->whereBetween('created_at', $dateRange)
            ->get();

        $grossSales = (float) $sales->sum('subtotal');
        $totalDiscount = (float) $sales->sum('discount_amount');
        $totalTax = (float) $sales->sum('tax_amount');
        $totalRevenue = (float) $sales->sum('total_amount');
        $transactionCount = $sales->count();

        // Refunds in range
        $refundsTotal = (float) Refund::whereHas('sale', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })->whereBetween('created_at', $dateRange)->sum('amount');

        $netSales = max(0.0, $totalRevenue - $refundsTotal);

        // Cost of Goods Sold (COGS)
        $cogs = (float) SaleItem::whereHas('sale', function ($q) use ($storeId, $dateRange) {
            $q->where('store_id', $storeId)
              ->whereIn('status', ['COMPLETED', 'PARTIALLY_REFUNDED', 'REFUNDED'])
              ->whereBetween('created_at', $dateRange);
        })->sum(DB::raw('quantity * unit_cost'));

        $grossProfit = max(0.0, $netSales - $cogs);

        // Payment method breakdown
        $paymentMethods = Sale::select('payment_method as method', DB::raw('SUM(total_amount) as total_amount'), DB::raw('COUNT(id) as count'))
            ->where('store_id', $storeId)
            ->whereIn('status', ['COMPLETED', 'PARTIALLY_REFUNDED', 'REFUNDED'])
            ->whereBetween('created_at', $dateRange)
            ->groupBy('payment_method')
            ->get();

        // Product performance breakdown
        $productStats = SaleItem::select(
                'product_id',
                'product_name_snapshot',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(line_total) as total_sales'),
                DB::raw('SUM(quantity * unit_cost) as total_cost')
            )
            ->whereHas('sale', function ($q) use ($storeId, $dateRange) {
                $q->where('store_id', $storeId)
                  ->whereIn('status', ['COMPLETED', 'PARTIALLY_REFUNDED', 'REFUNDED'])
                  ->whereBetween('created_at', $dateRange);
            })
            ->groupBy('product_id', 'product_name_snapshot')
            ->orderByDesc('total_sales')
            ->get();

        return view('livewire.reports.index', [
            'grossSales' => $grossSales,
            'totalDiscount' => $totalDiscount,
            'totalTax' => $totalTax,
            'totalRevenue' => $totalRevenue,
            'refundsTotal' => $refundsTotal,
            'netSales' => $netSales,
            'cogs' => $cogs,
            'grossProfit' => $grossProfit,
            'transactionCount' => $transactionCount,
            'paymentMethods' => $paymentMethods,
            'productStats' => $productStats,
        ]);
    }
}
