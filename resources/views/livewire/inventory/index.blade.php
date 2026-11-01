<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Manajemen Inventori & Stok</h1>
            <p class="text-sm text-slate-500">Saldo stok fisik (On-Hand), stok direservasi (Reserved), dan kartu riwayat mutasi.</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex bg-slate-200/80 p-1 rounded-xl">
            <button type="button" wire:click="$set('activeTab', 'balances')"
                class="px-4 py-1.5 rounded-lg text-xs font-bold transition {{ $activeTab === 'balances' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                Saldo Stok Barang
            </button>
            <button type="button" wire:click="$set('activeTab', 'movements')"
                class="px-4 py-1.5 rounded-lg text-xs font-bold transition {{ $activeTab === 'movements' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                Kartu Stok (Log Mutasi)
            </button>
        </div>
    </div>

    @if($activeTab === 'balances')
        <!-- BALANCES TAB -->
        <div class="space-y-4">
            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="w-full sm:w-80">
                    <input wire:model.live.debounce.300ms="searchProduct" type="text" placeholder="Cari nama barang atau SKU..."
                        class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input wire:model.live="onlyLowStock" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Hanya Tampilkan Stok Menipis (<= Min)
                    </label>
                </div>
            </div>

            <!-- Balances Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">SKU</th>
                                <th class="px-5 py-3">Nama Produk</th>
                                <th class="px-5 py-3">Kategori</th>
                                <th class="px-5 py-3 text-right">Batas Min</th>
                                <th class="px-5 py-3 text-right">Fisik (On-Hand)</th>
                                <th class="px-5 py-3 text-right">Reservasi QRIS</th>
                                <th class="px-5 py-3 text-right">Tersedia Dijual</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($balances as $product)
                                @php
                                    $onHand = (float) ($product->stockBalance?->on_hand ?? 0);
                                    $reserved = (float) ($product->stockBalance?->reserved ?? 0);
                                    $available = max(0.0, $onHand - $reserved);
                                    $isLow = $available <= (float) $product->min_stock;
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-5 py-3 text-xs font-mono font-bold text-slate-600">
                                        {{ $product->sku }}
                                    </td>
                                    <td class="px-5 py-3 font-semibold text-slate-900">
                                        {{ $product->name }}
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-500">
                                        {{ $product->category?->name ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs text-slate-500">
                                        {{ number_format($product->min_stock, 0, ',', '.') }} {{ $product->unit?->symbol }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-slate-800">
                                        {{ number_format($onHand, 0, ',', '.') }} {{ $product->unit?->symbol }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs font-semibold {{ $reserved > 0 ? 'text-amber-600' : 'text-slate-400' }}">
                                        {{ number_format($reserved, 0, ',', '.') }} {{ $product->unit?->symbol }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-black text-sm {{ $isLow ? 'text-red-600' : 'text-emerald-700' }}">
                                        {{ number_format($available, 0, ',', '.') }} {{ $product->unit?->symbol }}
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        @if($isLow)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">Menipis</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Aman</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <button type="button" wire:click="openAdjustModal({{ $product->id }})"
                                            class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg border border-indigo-200 transition">
                                            Sesuaikan
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-12 text-center text-slate-400">
                                        Tidak ada produk dengan pelacakan stok.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-100">
                    {{ $balances->links() }}
                </div>
            </div>
        </div>
    @else
        <!-- MOVEMENTS TAB -->
        <div class="space-y-4">
            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row gap-3">
                <div class="w-full sm:w-64">
                    <select wire:model.live="filterProductId" class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
                        <option value="">Semua Produk</option>
                        @foreach ($allTrackedProducts as $tp)
                            <option value="{{ $tp->id }}">{{ $tp->name }} ({{ $tp->sku }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-64">
                    <select wire:model.live="filterMovementType" class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
                        <option value="">Semua Tipe Mutasi</option>
                        <option value="OPENING">Stok Awal (OPENING)</option>
                        <option value="PURCHASE_IN">Pembelian Masuk (PURCHASE_IN)</option>
                        <option value="SALE_OUT">Penjualan Terkonfirmasi (SALE_OUT)</option>
                        <option value="RETURN_IN">Retur Pengembalian (RETURN_IN)</option>
                        <option value="ADJUSTMENT">Penyesuaian Fisik (ADJUSTMENT)</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Waktu</th>
                                <th class="px-5 py-3">Produk</th>
                                <th class="px-5 py-3">Tipe Mutasi</th>
                                <th class="px-5 py-3 text-right">Sebelum</th>
                                <th class="px-5 py-3 text-right">Perubahan (Delta)</th>
                                <th class="px-5 py-3 text-right">Sesudah</th>
                                <th class="px-5 py-3">Referensi</th>
                                <th class="px-5 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($movements as $m)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-5 py-3 text-xs text-slate-500 font-mono">
                                        {{ $m->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-5 py-3 font-semibold text-slate-900 text-xs">
                                        {{ $m->product?->name ?? 'Produk Dihapus' }}
                                    </td>
                                    <td class="px-5 py-3 text-xs">
                                        @if($m->type === 'OPENING')
                                            <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-slate-100 text-slate-700">OPENING</span>
                                        @elseif($m->type === 'PURCHASE_IN')
                                            <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-blue-100 text-blue-800">PURCHASE_IN</span>
                                        @elseif($m->type === 'SALE_OUT')
                                            <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-amber-100 text-amber-800">SALE_OUT</span>
                                        @elseif($m->type === 'RETURN_IN')
                                            <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-purple-100 text-purple-800">RETURN_IN</span>
                                        @elseif($m->type === 'ADJUSTMENT')
                                            <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-emerald-100 text-emerald-800">ADJUSTMENT</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-slate-100 text-slate-700">{{ $m->type }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs text-slate-500">
                                        {{ number_format($m->before_qty, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs font-black {{ $m->qty_delta > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $m->qty_delta > 0 ? '+' : '' }}{{ number_format($m->qty_delta, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs font-bold text-slate-800">
                                        {{ number_format($m->after_qty, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-500 font-mono">
                                        {{ $m->ref_type ? class_basename($m->ref_type) . ' #' . $m->ref_id : '-' }}
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-600">
                                        {{ $m->note ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                        Belum ada rekaman riwayat mutasi stok.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-100">
                    {{ $movements->links() }}
                </div>
            </div>
        </div>
    @endif

    <!-- ADJUSTMENT MODAL -->
    @if($showAdjustModal && $adjustingProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Penyesuaian Stok Fisik (Stock Opname)</h3>
                        <p class="text-xs text-slate-500">{{ $adjustingProduct->name }} ({{ $adjustingProduct->sku }})</p>
                    </div>
                    <button type="button" wire:click="$set('showAdjustModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Saldo Fisik Saat Ini:</span>
                        <span class="font-bold text-slate-800">{{ number_format($adjustingProduct->stockBalance?->on_hand ?? 0, 0, ',', '.') }} {{ $adjustingProduct->unit?->symbol }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Sedang Direservasi (QRIS Pending):</span>
                        <span class="font-bold text-amber-600">{{ number_format($adjustingProduct->stockBalance?->reserved ?? 0, 0, ',', '.') }} {{ $adjustingProduct->unit?->symbol }}</span>
                    </div>
                </div>

                @if($errorMessage)
                    <div class="p-3 rounded-xl bg-red-50 text-xs text-red-700">{{ $errorMessage }}</div>
                @endif

                <form wire:submit="saveAdjustment" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Perubahan Kuantitas (Delta) *</label>
                        <input wire:model="adjustDelta" type="number" step="1" required placeholder="Contoh: +5 untuk menambah, -3 untuk mengurangi"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold focus:ring-indigo-500">
                        <p class="text-[10px] text-slate-400 mt-1">Gunakan angka positif (+) untuk penambahan atau negatif (-) untuk pengurangan fisik.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Alasan Penyesuaian *</label>
                        <textarea wire:model="adjustReason" rows="2" required placeholder="Contoh: Barang rusak saat display, selisih stock opname bulanan..."
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="$set('showAdjustModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">
                            Catat Penyesuaian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
