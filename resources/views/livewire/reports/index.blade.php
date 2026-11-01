<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Laporan Penjualan & Laba Rugi</h1>
            <p class="text-sm text-slate-500">Omzet bruto, diskon, pajak, retur, laba kotor, dan rincian metode pembayaran.</p>
        </div>

        <button type="button" onclick="window.print()"
            class="no-print inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition shadow-2xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Cetak Laporan</span>
        </button>
    </div>

    <!-- Date Range & Presets -->
    <div class="no-print bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="setPreset('today')" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 font-semibold text-slate-700">Hari Ini</button>
            <button type="button" wire:click="setPreset('week')" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 font-semibold text-slate-700">7 Hari</button>
            <button type="button" wire:click="setPreset('month')" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 font-semibold text-slate-700">Bulan Ini</button>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-slate-500 font-medium">Periode:</span>
            <input wire:model.live="startDate" type="date" class="rounded-xl border-slate-200 px-3 py-1.5 text-xs focus:ring-indigo-500">
            <span class="text-slate-400">s/d</span>
            <input wire:model.live="endDate" type="date" class="rounded-xl border-slate-200 px-3 py-1.5 text-xs focus:ring-indigo-500">
        </div>
    </div>

    <!-- Financial KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Omzet Penjualan Kotor</span>
            <div class="text-2xl font-bold text-slate-900 mt-2">Rp {{ number_format($grossSales, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $transactionCount }} transaksi nota</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Retur / Refund</span>
            <div class="text-2xl font-bold text-red-600 mt-2">-Rp {{ number_format($refundsTotal, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-400 mt-1">Pengembalian dana pelanggan</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-indigo-200 bg-indigo-50/20 shadow-2xs">
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Penjualan Bersih (Neto)</span>
            <div class="text-2xl font-black text-indigo-900 mt-2">Rp {{ number_format($netSales, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">Setelah dikurangi retur</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-emerald-200 bg-emerald-50/20 shadow-2xs">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Estimasi Laba Kotor</span>
            <div class="text-2xl font-black text-emerald-900 mt-2">Rp {{ number_format($grossProfit, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">Modal barang (HPP): Rp {{ number_format($cogs, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Payment Methods Breakdown & Tax Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Payment Methods -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h3 class="font-bold text-slate-900 text-sm">💳 Metode Pembayaran</h3>
            <div class="space-y-2 text-xs">
                @forelse ($paymentMethods as $pm)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div>
                            <span class="font-bold text-slate-800">{{ $pm->method }}</span>
                            <span class="text-[10px] text-slate-400 ml-1">({{ $pm->count }}x)</span>
                        </div>
                        <span class="font-extrabold text-slate-900">Rp {{ number_format($pm->total_amount, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <p class="text-slate-400 py-3 text-center">Belum ada data pembayaran.</p>
                @endforelse
            </div>
        </div>

        <!-- Product Sales Performance (2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h3 class="font-bold text-slate-900 text-sm">📦 Kinerja Penjualan per Produk</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Nama Produk</th>
                            <th class="px-3 py-2 text-center">Terjual</th>
                            <th class="px-3 py-2 text-right">Pendapatan</th>
                            <th class="px-3 py-2 text-right">Estimasi HPP</th>
                            <th class="px-3 py-2 text-right">Margin Laba</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($productStats as $stat)
                            @php
                                $margin = max(0.0, (float)$stat->total_sales - (float)$stat->total_cost);
                            @endphp
                            <tr>
                                <td class="px-3 py-2.5 font-semibold text-slate-800">{{ $stat->product_name_snapshot }}</td>
                                <td class="px-3 py-2.5 text-center font-bold">{{ number_format($stat->total_qty, 0, ',', '.') }}</td>
                                <td class="px-3 py-2.5 text-right font-medium">Rp {{ number_format($stat->total_sales, 0, ',', '.') }}</td>
                                <td class="px-3 py-2.5 text-right text-slate-500">Rp {{ number_format($stat->total_cost, 0, ',', '.') }}</td>
                                <td class="px-3 py-2.5 text-right font-bold text-emerald-600">+Rp {{ number_format($margin, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">Belum ada barang terjual pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

