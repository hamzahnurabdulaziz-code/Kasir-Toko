<div class="max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pengaturan Toko & Identitas Struk</h1>
        <p class="text-sm text-slate-500">Konfigurasi profil toko, nomor kontak, format nomor nota kasir, dan persentase pajak.</p>
    </div>

    @if($errorMessage)
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700">{{ $errorMessage }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Toko / Outlet *</label>
                <input wire:model="name" type="text" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:ring-indigo-500">
                @error('name') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">No Telepon / WhatsApp</label>
                    <input wire:model="phone" type="text" placeholder="0812-3456-7890" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Prefix Nomor Faktur (Struk) *</label>
                    <input wire:model="receipt_prefix" type="text" required placeholder="INV-" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-mono font-bold text-slate-900 focus:ring-indigo-500">
                    <p class="text-[10px] text-slate-400 mt-1">Contoh hasil: INV-20261101-0001</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Alamat Lengkap Toko</label>
                <textarea wire:model="address" rows="2" placeholder="Jl. Sudirman No. 45, Jakarta Pusat" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 focus:ring-indigo-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Pesan Footer Struk Nota</label>
                <textarea wire:model="receipt_footer" rows="2" placeholder="Terima kasih telah berbelanja! Barang yang sudah dibeli..." class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 focus:ring-indigo-500"></textarea>
                <p class="text-[10px] text-slate-400 mt-1">Ditampilkan di baris paling bawah saat struk dicetak.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Merchant QRIS *</label>
                    <input wire:model="qris_merchant_name" type="text" required placeholder="TOKO KASIR MODERN" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-bold text-slate-900 focus:ring-indigo-500">
                    <p class="text-[10px] text-slate-400 mt-1">Ditampilkan pada header QRIS dinamis.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Merchant ID (NMID / MPAN) *</label>
                    <input wire:model="qris_merchant_id" type="text" required placeholder="ID1020000000000" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-mono text-slate-900 focus:ring-indigo-500">
                    <p class="text-[10px] text-slate-400 mt-1">Nomor identitas merchant payment gateway.</p>
                </div>
            </div>

            <div class="pt-2">
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tarif Pajak Toko (%)</label>
                <div class="flex items-center gap-2">
                    <input wire:model="tax_percentage" type="number" step="0.01" min="0" max="100" class="w-32 rounded-xl border border-slate-200 px-3.5 py-2 text-sm font-bold text-slate-900 focus:ring-indigo-500">
                    <span class="text-xs text-slate-500">persen (diterapkan jika kasir mencentang opsi pajak)</span>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-200 transition">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

