<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pembelian Barang (Purchase Order)</h1>
            <p class="text-sm text-slate-500">Pencatatan pengadaan dari supplier, draft pesanan, dan penerimaan stok masuk.</p>
        </div>

        <button type="button" wire:click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-200 transition">
            + Buat Pesanan Pembelian (PO)
        </button>
    </div>

    <!-- Purchases Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">No PO</th>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Pemasok (Supplier)</th>
                        <th class="px-5 py-3">Catatan</th>
                        <th class="px-5 py-3 text-right">Total Pembelian</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($purchases as $po)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-3 font-semibold text-indigo-600 font-mono text-xs">
                                {{ $po->purchase_no }}
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                {{ $po->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-900 text-xs">
                                {{ $po->supplier->name }}
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600">
                                {{ $po->note ?: '-' }}
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-slate-900">
                                Rp {{ number_format($po->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($po->status === 'DRAFT')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">DRAFT</span>
                                @elseif($po->status === 'RECEIVED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">RECEIVED</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">CANCELLED</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button type="button" wire:click="viewDetails({{ $po->id }})" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                    Detail & Terima
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                Belum ada transaksi pembelian barang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $purchases->links() }}
        </div>
    </div>

    <!-- CREATE PO MODAL -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 space-y-4 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Buat Pesanan Pembelian (PO) Baru</h3>
                    <button type="button" wire:click="$set('showCreateModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                @if($errorMessage)
                    <div class="p-3 rounded-xl bg-red-50 text-xs text-red-700">{{ $errorMessage }}</div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Pemasok (Supplier) *</label>
                        <select wire:model="supplierId" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-indigo-500">
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Catatan PO</label>
                        <input wire:model="note" type="text" placeholder="Pengiriman armada truk / no faktur supplier"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-indigo-500">
                    </div>
                </div>

                <!-- PO Item Rows -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold uppercase text-slate-500">Daftar Barang Dipesan</label>
                        <button type="button" wire:click="addItemRow" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">+ Tambah Baris</button>
                    </div>

                    <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                        @foreach ($items as $index => $row)
                            <div class="flex items-center gap-2 p-2 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                                <div class="flex-1">
                                    <select wire:model="items.{{ $index }}.product_id"
                                        wire:change="onProductSelected({{ $index }}, $event.target.value)"
                                        class="w-full rounded-lg border-slate-200 px-2.5 py-1.5 text-xs bg-white">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach ($products as $prod)
                                            <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->sku }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-20">
                                    <input type="number" step="any" min="1" placeholder="Qty" wire:model="items.{{ $index }}.quantity"
                                        class="w-full rounded-lg border-slate-200 px-2 py-1.5 text-xs text-center font-bold bg-white">
                                </div>
                                <div class="w-28">
                                    <input type="number" step="any" min="0" placeholder="Harga Beli" wire:model="items.{{ $index }}.unit_cost"
                                        class="w-full rounded-lg border-slate-200 px-2 py-1.5 text-xs text-right font-medium bg-white">
                                </div>
                                <button type="button" wire:click="removeItemRow({{ $index }})" class="p-1 text-red-500 hover:text-red-700">✕</button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Batal</button>
                    <button type="button" wire:click="savePurchase" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">Simpan Draft PO</button>
                </div>
            </div>
        </div>
    @endif

    <!-- DETAIL & RECEIVE PO MODAL -->
    @if($showDetailModal && $selectedPurchase)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Rincian {{ $selectedPurchase->purchase_no }}</h3>
                        <p class="text-xs text-slate-500">Supplier: {{ $selectedPurchase->supplier->name }} • Dibuat: {{ $selectedPurchase->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <button type="button" wire:click="$set('showDetailModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Produk</th>
                                <th class="px-3 py-2 text-center">Qty Pesan</th>
                                <th class="px-3 py-2 text-right">Harga Beli</th>
                                <th class="px-3 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($selectedPurchase->items as $item)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $item->product->name }}</td>
                                    <td class="px-3 py-2 text-center font-bold">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product->unit->symbol }}</td>
                                    <td class="px-3 py-2 text-right">Rp {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-slate-900">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl flex items-center justify-between text-xs font-bold text-slate-900">
                    <span>Total Biaya Pengadaan:</span>
                    <span class="text-base font-black text-indigo-700">Rp {{ number_format($selectedPurchase->total_amount, 0, ',', '.') }}</span>
                </div>

                <div class="flex items-center justify-between pt-2">
                    @if($selectedPurchase->status === 'DRAFT')
                        <button type="button" wire:click="receivePurchase({{ $selectedPurchase->id }})"
                            class="px-5 py-2.5 rounded-xl font-bold text-xs text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-200 transition">
                            ✓ Konfirmasi Terima Barang (Update Stok)
                        </button>
                    @else
                        <span class="text-xs text-emerald-600 font-bold">✓ Barang sudah diterima pada {{ $selectedPurchase->received_at?->format('d/m/Y H:i') }}</span>
                    @endif

                    <button type="button" wire:click="$set('showDetailModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

