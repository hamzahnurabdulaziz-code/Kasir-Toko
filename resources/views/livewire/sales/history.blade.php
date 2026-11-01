<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Riwayat Transaksi Penjualan</h1>
            <p class="text-sm text-slate-500">Histori faktur penjualan, status pembayaran QRIS dinamis & tunai, cetak ulang struk, dan retur.</p>
        </div>

        <a href="{{ route('pos.checkout') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-200 transition">
            + Buka Mesin Kasir POS
        </a>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div>
            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Cari Nota / Pelanggan</label>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="No nota atau nama..."
                class="w-full rounded-xl border-slate-200 px-3 py-1.5 text-xs focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Status Transaksi</label>
            <select wire:model.live="status" class="w-full rounded-xl border-slate-200 px-3 py-1.5 text-xs focus:ring-indigo-500">
                <option value="">Semua Status</option>
                <option value="COMPLETED">Selesai (COMPLETED)</option>
                <option value="PENDING_PAYMENT">Menunggu Pembayaran (PENDING)</option>
                <option value="EXPIRED">Kedaluwarsa (EXPIRED)</option>
                <option value="CANCELLED">Dibatalkan (CANCELLED)</option>
                <option value="PARTIALLY_REFUNDED">Retur Sebagian</option>
                <option value="REFUNDED">Diretur Penuh</option>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Dari Tanggal</label>
            <input wire:model.live="startDate" type="date" class="w-full rounded-xl border-slate-200 px-3 py-1.5 text-xs focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Sampai Tanggal</label>
            <input wire:model.live="endDate" type="date" class="w-full rounded-xl border-slate-200 px-3 py-1.5 text-xs focus:ring-indigo-500">
        </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">No Faktur</th>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">Terminal / Shift</th>
                        <th class="px-5 py-3">Pelanggan</th>
                        <th class="px-5 py-3">Metode Bayar</th>
                        <th class="px-5 py-3 text-right">Total Transaksi</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($sales as $sale)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-3 font-semibold text-indigo-600 font-mono text-xs">
                                {{ $sale->invoice_number }}
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                {{ $sale->completed_at ? $sale->completed_at->format('d/m/Y H:i') : $sale->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3 text-xs font-mono">
                                {{ $sale->shift?->terminal_label ?? 'Kasir Utama' }}
                            </td>
                            <td class="px-5 py-3 text-xs">
                                {{ $sale->customer?->name ?? 'Walk-in' }}
                            </td>
                            <td class="px-5 py-3 text-xs">
                                <span class="inline-flex px-2 py-0.5 rounded-md font-semibold text-[10px] {{ $sale->payment_method === 'QRIS' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $sale->payment_method ?? 'QRIS' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-slate-900">
                                Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($sale->status === 'COMPLETED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Selesai</span>
                                @elseif($sale->status === 'PENDING_PAYMENT')
                                    <a href="{{ route('payments.show', $sale->id) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 hover:bg-amber-200 transition">
                                        Pending QRIS ➔
                                    </a>
                                @elseif($sale->status === 'EXPIRED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">Kedaluwarsa</span>
                                @elseif($sale->status === 'CANCELLED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">Dibatalkan</span>
                                @elseif($sale->status === 'PARTIALLY_REFUNDED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Retur Sebagian</span>
                                @elseif($sale->status === 'REFUNDED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">Diretur Penuh</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right space-x-2">
                                <button type="button" wire:click="viewDetails({{ $sale->id }})" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
                                    Rincian
                                </button>
                                @if($sale->status === 'COMPLETED')
                                    <button type="button" wire:click="reprintReceipt({{ $sale->id }})" class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                                        Struk
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                Belum ada faktur penjualan yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $sales->links() }}
        </div>
    </div>

    <!-- DETAIL MODAL -->
    @if($showDetailModal && $selectedSale)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Rincian Nota {{ $selectedSale->invoice_number }}</h3>
                        <p class="text-xs text-slate-500">{{ $selectedSale->created_at->format('d/m/Y H:i') }} • Terminal: {{ $selectedSale->shift?->terminal_label ?? 'Kasir Utama' }}</p>
                    </div>
                    <button type="button" wire:click="$set('showDetailModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Produk</th>
                                <th class="px-3 py-2 text-right">Harga</th>
                                <th class="px-3 py-2 text-center">Qty</th>
                                <th class="px-3 py-2 text-right">Diskon</th>
                                <th class="px-3 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($selectedSale->items as $item)
                                <tr>
                                    <td class="px-3 py-2.5 font-medium text-slate-800">{{ $item->product_name_snapshot }}</td>
                                    <td class="px-3 py-2.5 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-center font-bold">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-right text-slate-400">Rp {{ number_format($item->discount_amount, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-right font-bold text-slate-900">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl space-y-1 text-xs">
                    <div class="flex justify-between text-slate-500"><span>Subtotal:</span><span>Rp {{ number_format($selectedSale->subtotal, 0, ',', '.') }}</span></div>
                    @if($selectedSale->discount_amount > 0)
                        <div class="flex justify-between text-red-600"><span>Diskon Transaksi:</span><span>-Rp {{ number_format($selectedSale->discount_amount, 0, ',', '.') }}</span></div>
                    @endif
                    @if($selectedSale->tax_amount > 0)
                        <div class="flex justify-between text-slate-500"><span>Pajak:</span><span>Rp {{ number_format($selectedSale->tax_amount, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="flex justify-between font-bold text-sm text-slate-900 pt-1 border-t border-slate-200">
                        <span>Grand Total:</span>
                        <span>Rp {{ number_format($selectedSale->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    @if($selectedSale->status === 'COMPLETED')
                        <a href="{{ route('refunds.index') }}?invoice={{ $selectedSale->invoice_number }}" class="text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 px-3 py-2 rounded-lg border border-amber-200 transition">
                            Proses Retur Barang
                        </a>
                    @elseif($selectedSale->status === 'PENDING_PAYMENT')
                        <a href="{{ route('payments.show', $selectedSale->id) }}" class="text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-lg border border-indigo-200 transition">
                            Buka Layar Pembayaran QRIS ➔
                        </a>
                    @else
                        <span></span>
                    @endif
                    <button type="button" wire:click="$set('showDetailModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- REPRINT MODAL (WITH DUPLICATE WATERMARK) -->
    @if($showReprintModal && $selectedSale)
        @php
            $store = \App\Models\Store::first();
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="text-center font-mono text-xs p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                    <div class="pb-2 border-b border-dashed border-slate-300">
                        <div class="inline-block px-2 py-0.5 rounded-sm bg-slate-200 text-[9px] font-bold uppercase tracking-wider mb-1">
                            *** SALINAN / DUPLIKAT ***
                        </div>
                        <h4 class="font-black text-sm uppercase">{{ $store?->name ?? 'TOKO POS' }}</h4>
                        <p class="text-[10px] text-slate-500">{{ $store?->address ?? 'Indonesia' }}</p>
                    </div>

                    <div class="text-[10px] text-left space-y-0.5 pb-2 border-b border-dashed border-slate-300">
                        <div class="flex justify-between"><span>No Nota:</span><span class="font-bold">{{ $selectedSale->invoice_number }}</span></div>
                        <div class="flex justify-between"><span>Tanggal:</span><span>{{ $selectedSale->created_at->format('d/m/Y H:i') }}</span></div>
                        <div class="flex justify-between"><span>Terminal:</span><span>{{ $selectedSale->shift?->terminal_label ?? 'Kasir Utama' }}</span></div>
                    </div>

                    <div class="space-y-1.5 text-left text-[11px] pb-2 border-b border-dashed border-slate-300">
                        @foreach ($selectedSale->items as $item)
                            <div>
                                <div class="font-semibold">{{ $item->product_name_snapshot }}</div>
                                <div class="flex justify-between text-slate-600 text-[10px]">
                                    <span>{{ number_format($item->quantity, 0, ',', '.') }} x {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                                    <span>Rp {{ number_format($item->line_total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-1 text-left text-[11px] pb-2 border-b border-dashed border-slate-300">
                        <div class="flex justify-between font-bold text-xs">
                            <span>TOTAL:</span>
                            <span>Rp {{ number_format($selectedSale->total_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-500">
                            <span>Metode:</span>
                            <span>{{ $selectedSale->payment_method }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="window.print()" class="flex-1 py-2.5 px-3 rounded-xl font-bold text-xs text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition">
                        Cetak Ulang
                    </button>
                    <button type="button" wire:click="$set('showReprintModal', false)" class="flex-1 py-2.5 px-3 rounded-xl font-bold text-xs text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
