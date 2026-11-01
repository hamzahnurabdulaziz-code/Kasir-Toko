<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Master Data Produk</h1>
            <p class="text-sm text-slate-500">Katalog barang dagangan, SKU unik, barcode scanner, reservasi stok, dan batas stok minimum.</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" wire:click="$toggle('showArchived')"
                class="px-3.5 py-2.5 rounded-xl text-xs font-bold border transition {{ $showArchived ? 'bg-amber-50 text-amber-800 border-amber-300' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                {{ $showArchived ? 'Lihat Produk Aktif' : 'Lihat Arsip Terhapus' }}
            </button>
            <button type="button" wire:click="create"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-200 transition">
                + Tambah Produk Baru
            </button>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama barang, barcode, atau SKU..."
                class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
        </div>
        <div class="w-full sm:w-64">
            <select wire:model.live="filterCategory" class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">SKU & Barcode</th>
                        <th class="px-5 py-3">Nama Produk</th>
                        <th class="px-5 py-3">Kategori</th>
                        <th class="px-5 py-3 text-right">Harga Beli</th>
                        <th class="px-5 py-3 text-right">Harga Jual</th>
                        <th class="px-5 py-3 text-center">Stok Tersedia</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($products as $p)
                        @php
                            $availableStock = (float) $p->available_stock;
                            $onHand = (float) ($p->stockBalance?->on_hand ?? 0);
                            $reserved = (float) ($p->stockBalance?->reserved ?? 0);
                            $isLow = $p->track_stock && $availableStock <= (float) $p->min_stock;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition {{ !$p->is_active ? 'opacity-60 bg-slate-50/50' : '' }}">
                            <td class="px-5 py-3 text-xs font-mono">
                                <div class="font-bold text-slate-800">{{ $p->sku }}</div>
                                <div class="text-[10px] text-slate-400">{{ $p->barcode ?: '-' }}</div>
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-900">
                                {{ $p->name }}
                                @if($p->trashed())
                                    <span class="ml-1 text-[10px] bg-red-100 text-red-700 font-bold px-1.5 py-0.5 rounded">Diarsip</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                {{ $p->category?->name ?? '-' }}
                            </td>
                            <td class="px-5 py-3 text-right text-xs text-slate-500">
                                Rp {{ number_format($p->cost_price, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-slate-900">
                                Rp {{ number_format($p->sale_price, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($p->track_stock)
                                    <div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $isLow ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ number_format($availableStock, 0, ',', '.') }} {{ $p->unit?->symbol }}
                                        </span>
                                    </div>
                                    @if($reserved > 0)
                                        <div class="text-[10px] text-amber-600 font-semibold mt-0.5">
                                            (Fisik: {{ number_format($onHand, 0, ',', '.') }}, Dipesan: {{ number_format($reserved, 0, ',', '.') }})
                                        </div>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">Tanpa Stok</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if(!$p->trashed())
                                    <button type="button" wire:click="toggleActive({{ $p->id }})"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold transition {{ $p->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200' }}">
                                        {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400">Arsip</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right space-x-2">
                                @if(!$p->trashed())
                                    <button type="button" wire:click="edit({{ $p->id }})" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="archiveProduct({{ $p->id }})" wire:confirm="Arsipkan produk ini? Histori penjualan lama tetap aman." class="text-xs font-semibold text-red-500 hover:text-red-700 transition">
                                        Arsip
                                    </button>
                                @else
                                    <button type="button" wire:click="restoreProduct({{ $p->id }})" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 transition">
                                        Pulihkan
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                Belum ada produk terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $products->links() }}
        </div>
    </div>

    <!-- CREATE / EDIT MODAL -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 space-y-4 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">
                        {{ $editingProductId ? 'Edit Data Produk' : 'Tambah Produk Baru' }}
                    </h3>
                    <button type="button" wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                @if($errorMessage)
                    <div class="p-3 rounded-xl bg-red-50 text-xs text-red-700">{{ $errorMessage }}</div>
                @endif

                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">SKU (Kode Unik) *</label>
                            <input wire:model="sku" type="text" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-mono font-bold focus:ring-indigo-500">
                            @error('sku') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Barcode (EAN-13 dll)</label>
                            <input wire:model="barcode" type="text" placeholder="Scan barcode produk..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-mono focus:ring-indigo-500">
                            @error('barcode') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Produk *</label>
                        <input wire:model="name" type="text" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-indigo-500">
                        @error('name') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Kategori *</label>
                            <select wire:model="category_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-indigo-500">
                                <option value="">Pilih Kategori</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Satuan Unit *</label>
                            <select wire:model="unit_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-indigo-500">
                                <option value="">Pilih Unit</option>
                                @foreach ($units as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                                @endforeach
                            </select>
                            @error('unit_id') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Harga Modal (Rp) *</label>
                            <input wire:model="cost_price" type="number" step="0.01" min="0" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-indigo-500">
                            @error('cost_price') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Harga Jual (Rp) *</label>
                            <input wire:model="sale_price" type="number" step="0.01" min="0" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-indigo-600 focus:ring-indigo-500">
                            @error('sale_price') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Stok Minimum</label>
                            <input wire:model="min_stock" type="number" step="1" min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-indigo-500">
                        </div>
                    </div>

                    @if(!$editingProductId && $track_stock)
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Stok Fisik Awal (Opening Stock)</label>
                            <input wire:model="initial_stock" type="number" step="1" min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold">
                            <p class="text-[10px] text-slate-400 mt-1">Stok awal akan langsung dicatat sebagai mutasi masuk OPENING.</p>
                        </div>
                    @endif

                    <div class="flex items-center gap-6 pt-2">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                            <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Aktif Dijual di POS
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                            <input wire:model="track_stock" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Lacak Saldo Stok
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">
                            Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
