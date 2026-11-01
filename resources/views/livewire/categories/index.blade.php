<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Kategori & Satuan Produk</h1>
        <p class="text-sm text-slate-500">Pengelompokan barang dagangan dan konvensi satuan ukuran (pcs, kg, box, dll).</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Categories Panel -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Kategori Toko</h3>
                    <p class="text-xs text-slate-500">Kelompok kategori untuk navigasi kasir</p>
                </div>
                <button type="button" wire:click="createCategory"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition">
                    + Tambah Kategori
                </button>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach ($categories as $cat)
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-slate-800 text-sm">{{ $cat->name }}</div>
                            <div class="text-[11px] text-slate-400">{{ $cat->products_count }} produk terdaftar</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $cat->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <button type="button" wire:click="editCategory({{ $cat->id }})" class="p-1.5 text-indigo-600 hover:text-indigo-800 font-semibold">
                                Edit
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Units Panel -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Satuan Ukuran (Units)</h3>
                    <p class="text-xs text-slate-500">Satuan dasar kuantitas stok barang</p>
                </div>
                <button type="button" wire:click="createUnit"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    + Tambah Satuan
                </button>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach ($units as $u)
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-slate-800 text-sm">{{ $u->name }}</div>
                            <div class="text-[11px] text-slate-400">Simbol: <span class="font-mono font-bold text-slate-700">{{ $u->symbol }}</span></div>
                        </div>
                        <span class="text-xs text-slate-500">{{ $u->products_count }} produk</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- MODAL KATEGORI -->
    @if($showCategoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">
                        {{ $editingCategoryId ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
                    </h3>
                    <button type="button" wire:click="$set('showCategoryModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit="saveCategory" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Kategori *</label>
                        <input wire:model="categoryName" type="text" required placeholder="Contoh: Frozen Food"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
                        @error('categoryName') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                            <input wire:model="categoryIsActive" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500">
                            <span>Status Kategori Aktif</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showCategoryModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL SATUAN -->
    @if($showUnitModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Tambah Satuan Baru</h3>
                    <button type="button" wire:click="$set('showUnitModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit="saveUnit" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Satuan *</label>
                        <input wire:model="unitName" type="text" required placeholder="Contoh: Renteng / Lusin"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
                        @error('unitName') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Simbol / Singkatan *</label>
                        <input wire:model="unitSymbol" type="text" required placeholder="Contoh: rtg / lsn"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-mono focus:ring-indigo-500">
                        @error('unitSymbol') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showUnitModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

