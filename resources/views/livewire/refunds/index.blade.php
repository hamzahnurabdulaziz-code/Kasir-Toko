<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Retur Barang & Pengembalian Dana (Refund)</h1>
        <p class="text-sm text-slate-500">Persetujuan retur penjualan dengan kontrol kuantitas maksimal dan opsi restock fisik.</p>
    </div>

    <!-- Search Invoice Box -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
        <h3 class="font-bold text-slate-900 text-sm">1. Cari Faktur Penjualan</h3>
        <form wire:submit="searchInvoice" class="flex gap-3">
            <input wire:model="invoiceSearch" type="text" placeholder="Masukkan nomor nota, misal: INV-20261101-0001"
                class="flex-1 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:ring-indigo-500">
            <button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                Cari Nota
            </button>
        </form>

        @if($errorMessage)
            <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ $errorMessage }}</span>
            </div>
        @endif
    </div>

    <!-- Refund Processing Panel if Sale Found -->
    @if($foundSale)
        <div class="bg-white p-5 rounded-2xl border border-indigo-200 shadow-md space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Faktur Ditemukan</span>
                    <h3 class="text-lg font-black text-slate-900">{{ $foundSale->invoice_number }}</h3>
                    <p class="text-xs text-slate-500">Waktu: {{ $foundSale->sold_at ? $foundSale->sold_at->format('d/m/Y H:i') : $foundSale->created_at->format('d/m/Y H:i') }} • Terminal: {{ $foundSale->shift?->terminal_label ?? 'Kasir Utama' }} • Pelanggan: {{ $foundSale->customer?->name ?? 'Walk-in' }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">Total Transaksi Awal:</span>
                    <div class="text-base font-bold text-slate-800">Rp {{ number_format($foundSale->total_amount, 0, ',', '.') }}</div>
                </div>
            </div>

            <!-- Items to return table -->
            <div>
                <h4 class="font-bold text-xs uppercase tracking-wider text-slate-600 mb-2">Pilih Item & Kuantitas Retur</h4>
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Nama Produk</th>
                                <th class="px-4 py-3 text-right">Harga Jual</th>
                                <th class="px-4 py-3 text-center">Qty Beli</th>
                                <th class="px-4 py-3 text-center">Bisa Diretur</th>
                                <th class="px-4 py-3 text-center w-28">Qty Retur</th>
                                <th class="px-4 py-3 text-center">Kembalikan Stok?</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($foundSale->items as $item)
                                @php
                                    $refundable = $item->refundableQuantity();
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $item->product_name_snapshot }}</td>
                                    <td class="px-4 py-3 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center font-bold {{ $refundable > 0 ? 'text-indigo-600' : 'text-slate-400' }}">
                                        {{ number_format($refundable, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($refundable > 0)
                                            <input type="number" min="0" max="{{ $refundable }}" step="1"
                                                wire:model="refundItems.{{ $item->id }}.quantity"
                                                class="w-20 text-center rounded-lg border-slate-200 text-xs py-1 px-2 font-bold focus:ring-indigo-500">
                                        @else
                                            <span class="text-[10px] text-slate-400">Habis</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($refundable > 0)
                                            <label class="inline-flex items-center gap-1 cursor-pointer">
                                                <input type="checkbox" wire:model="refundItems.{{ $item->id }}.restore_stock" class="rounded text-indigo-600 focus:ring-indigo-500">
                                                <span class="text-[11px] text-slate-600">Restock</span>
                                            </label>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reason and Method -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Alasan Retur / Masalah Barang *</label>
                    <input wire:model="reason" type="text" placeholder="Contoh: Barang cacat kemasan / Salah beli ukuran"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Metode Pengembalian Uang</label>
                    <select wire:model="refundMethod" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
                        <option value="CASH">Tunai (Kas Laci Shift)</option>
                        <option value="TRANSFER">Transfer Bank / Non-Tunai</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" wire:click="$set('foundSale', null)" class="px-4 py-2.5 rounded-xl font-semibold text-xs text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                    Batalkan
                </button>
                <button type="button" wire:click="processRefund" class="px-5 py-2.5 rounded-xl font-bold text-xs text-white bg-amber-600 hover:bg-amber-700 shadow-md shadow-amber-200 transition">
                    Setujui & Proses Refund Sekarang
                </button>
            </div>
        </div>
    @endif

    <!-- Refunds History Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-sm">Riwayat Retur & Pengembalian Dana</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">No Retur</th>
                        <th class="px-5 py-3">No Faktur Asal</th>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">Terminal / Shift</th>
                        <th class="px-5 py-3">Alasan</th>
                        <th class="px-5 py-3">Metode</th>
                        <th class="px-5 py-3 text-right">Nilai Refund</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($refunds as $ref)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-3 font-semibold text-amber-700 font-mono text-xs">
                                {{ $ref->refund_no }}
                            </td>
                            <td class="px-5 py-3 text-xs font-medium text-indigo-600">
                                {{ $ref->sale->invoice_number }}
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                {{ $ref->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3 text-xs font-mono">
                                {{ $ref->shift?->terminal_label ?? 'Kasir Utama' }}
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600">
                                {{ $ref->reason }}
                            </td>
                            <td class="px-5 py-3 text-xs">
                                <span class="px-2 py-0.5 rounded-md font-semibold text-[10px] bg-slate-100 text-slate-700">
                                    {{ $ref->refund_method }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-red-600">
                                -Rp {{ number_format($ref->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-slate-400">
                                Belum ada transaksi retur yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $refunds->links() }}
        </div>
    </div>
</div>

