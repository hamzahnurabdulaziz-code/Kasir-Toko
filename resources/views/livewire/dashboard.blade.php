<div class="space-y-6">
    <!-- Header with Period Selector -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard Ringkasan Usaha</h1>
            <p class="text-sm text-slate-500">Pantau performa penjualan, omzet bersih, dan stok barang toko.</p>
        </div>

        <div class="inline-flex rounded-xl p-1 bg-white border border-slate-200 shadow-2xs">
            <button wire:click="$set('period', 'today')" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $period === 'today' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }} transition">
                Hari Ini
            </button>
            <button wire:click="$set('period', 'week')" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $period === 'week' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }} transition">
                7 Hari Terakhir
            </button>
            <button wire:click="$set('period', 'month')" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $period === 'month' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }} transition">
                Bulan Ini
            </button>
        </div>
    </div>

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Omzet Bersih -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Omzet Penjualan Neto</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold text-slate-900">Rp {{ number_format($netSales, 0, ',', '.') }}</div>
                <div class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                    <span>Bruto: Rp {{ number_format($grossSales, 0, ',', '.') }}</span>
                    @if($refundsTotal > 0)
                        <span class="text-red-500 font-medium">(-Retur Rp {{ number_format($refundsTotal, 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Transaksi Selesai -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Transaksi Selesai</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold text-slate-900">{{ number_format($transactionsCount, 0, ',', '.') }}</div>
                <div class="text-xs text-slate-400 mt-1">Struk nota lunas</div>
            </div>
        </div>

        <!-- Barang Terjual -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Item Terjual</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold text-slate-900">{{ number_format($itemsSold, 0, ',', '.') }}</div>
                <div class="text-xs text-slate-400 mt-1">Kuantitas barang keluar</div>
            </div>
        </div>

        <!-- Stok Menipis -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Peringatan Stok Menipis</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold text-amber-600">{{ $lowStockCount }} <span class="text-xs font-normal text-slate-500">item</span></div>
                <div class="text-xs text-slate-400 mt-1">Di bawah batas minimum</div>
            </div>
        </div>
    </div>

    <!-- Main Content Split: Recent Sales vs Top Selling & Low Stock -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Sales (2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Transaksi Penjualan Terkini</h2>
                <a href="{{ route('sales.history') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Lihat Semua →</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">No Nota</th>
                            <th class="px-5 py-3">Waktu</th>
                            <th class="px-5 py-3">Pelanggan</th>
                            <th class="px-5 py-3">Metode</th>
                            <th class="px-5 py-3 text-right">Total</th>
                            <th class="px-5 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($recentSales as $sale)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-5 py-3 font-semibold text-indigo-600">
                                    <a href="{{ route('sales.history') }}">{{ $sale->invoice_no }}</a>
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-500">
                                    {{ $sale->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-5 py-3 text-xs font-medium">
                                    {{ $sale->customer?->name ?? 'Walk-in' }}
                                </td>
                                <td class="px-5 py-3 text-xs">
                                    <span class="inline-flex px-2 py-0.5 rounded-md font-semibold text-[10px] {{ $sale->payment_method === 'QRIS' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                        {{ $sale->payment_method }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right font-bold text-slate-900">
                                    Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($sale->status === 'COMPLETED')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Selesai</span>
                                    @elseif($sale->status === 'PENDING_PAYMENT')
                                        <a href="{{ route('payments.show', $sale->id) }}" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 hover:bg-amber-200 transition">
                                            Belum Lunas ➔
                                        </a>
                                    @elseif($sale->status === 'PARTIALLY_REFUNDED')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">Retur Sebagian</span>
                                    @elseif($sale->status === 'REFUNDED')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">Diretur</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">{{ $sale->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                    Belum ada transaksi penjualan pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sidebar cards: Top Products & Low Stock Alerts (1 col) -->
        <div class="space-y-6">
            <!-- Top Selling Products -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5">
                <h3 class="font-bold text-slate-900 text-sm mb-3">🔥 5 Produk Terlaris</h3>
                <div class="space-y-3">
                    @forelse ($topProducts as $top)
                        <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-50 last:border-0 last:pb-0">
                            <div class="truncate pr-2">
                                <p class="font-semibold text-slate-800 truncate">{{ $top->product_name_snapshot }}</p>
                                <p class="text-slate-400 text-[10px]">Rp {{ number_format($top->total_revenue, 0, ',', '.') }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded-md font-bold text-indigo-700 bg-indigo-50 flex-shrink-0">
                                {{ number_format($top->total_qty, 0, ',', '.') }} terjual
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-3 text-center">Belum ada data barang terjual.</p>
                    @endforelse
                </div>
            </div>

            <!-- Low Stock Alert Box -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-slate-900 text-sm">⚠️ Stok Menipis</h3>
                    <a href="{{ route('inventory.index') }}" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">Buka Stok →</a>
                </div>
                <div class="space-y-3">
                    @forelse ($lowStockProducts as $lp)
                        <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-50 last:border-0 last:pb-0">
                            <div>
                                <p class="font-medium text-slate-800">{{ $lp->name }}</p>
                                <p class="text-[10px] text-slate-400">Min: {{ number_format($lp->min_stock, 0, ',', '.') }} {{ $lp->unit->symbol }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded-md font-bold text-red-700 bg-red-50 text-[11px]">
                                Tersedia: {{ number_format($lp->available_stock, 0, ',', '.') }} {{ $lp->unit->symbol }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-emerald-600 py-3 text-center font-medium">✓ Semua stok barang dalam kondisi aman.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
