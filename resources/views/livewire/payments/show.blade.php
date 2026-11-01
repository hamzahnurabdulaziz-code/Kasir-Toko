<div wire:poll.3s="checkStatus" class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center justify-between no-print">
        <a href="{{ route('pos.checkout') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
            ← Kembali ke Kasir (POS)
        </a>
        <span class="text-xs text-slate-400 font-mono">Invoice: {{ $sale->invoice_no }}</span>
    </div>

    <!-- QRIS Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden text-center">
        <!-- QRIS Header Banner -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 p-4 text-white">
            <div class="flex items-center justify-between">
                <span class="font-black text-lg tracking-wider">QRIS</span>
                <span class="text-[10px] uppercase font-bold tracking-widest bg-white/20 px-2 py-0.5 rounded-full">STANDAR PEMBAYARAN NASIONAL</span>
            </div>
            <div class="mt-2 text-sm font-bold tracking-tight">
                {{ \App\Models\Store::first()?->qris_merchant_name ?? 'TOKO BERKAH POS' }}
            </div>
            <div class="text-[11px] text-red-100 font-mono">NMID: {{ \App\Models\Store::first()?->qris_merchant_id ?? 'ID1020000123456' }} • A01</div>
        </div>

        <div class="p-6 space-y-5">
            <!-- Payment Status Badge -->
            <div>
                @if($sale->status === 'COMPLETED')
                    <div class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200 animate-bounce">
                        ✓ PEMBAYARAN QRIS LUNAS & TERKONFIRMASI
                    </div>
                @elseif($sale->status === 'PENDING_PAYMENT')
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>MENUNGGU PEMBAYARAN PELANGGAN</span>
                    </div>
                @else
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                        STATUS: {{ $sale->status }}
                    </div>
                @endif
            </div>

            <!-- Total Amount -->
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Pembayaran</span>
                <div class="text-3xl sm:text-4xl font-black text-slate-900 mt-1 tracking-tight">
                    Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                </div>
            </div>

            <!-- Dynamic QR Code Container -->
            @if($sale->status === 'PENDING_PAYMENT')
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 inline-block mx-auto shadow-inner">
                    <!-- Standard High-Density QR code visual matrix representation -->
                    <div class="w-56 h-56 bg-white p-3 rounded-xl border border-slate-300 shadow-sm mx-auto flex flex-col justify-between items-center relative">
                        <div class="w-full flex justify-between">
                            <div class="w-12 h-12 border-4 border-slate-900 flex items-center justify-center p-1">
                                <div class="w-6 h-6 bg-slate-900"></div>
                            </div>
                            <div class="w-12 h-12 border-4 border-slate-900 flex items-center justify-center p-1">
                                <div class="w-6 h-6 bg-slate-900"></div>
                            </div>
                        </div>

                        <!-- Central QR visual pattern -->
                        <div class="space-y-1.5 w-full px-2 text-center">
                            <div class="flex justify-center gap-1">
                                <span class="w-2 h-2 bg-slate-900 inline-block"></span>
                                <span class="w-4 h-2 bg-slate-900 inline-block"></span>
                                <span class="w-2 h-2 bg-slate-900 inline-block"></span>
                                <span class="w-6 h-2 bg-slate-900 inline-block"></span>
                            </div>
                            <div class="flex justify-center gap-1.5">
                                <span class="w-4 h-2 bg-slate-900 inline-block"></span>
                                <span class="w-2 h-2 bg-slate-900 inline-block"></span>
                                <span class="w-5 h-2 bg-slate-900 inline-block"></span>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-red-600 text-white font-black text-xs flex items-center justify-center mx-auto shadow-sm">
                                QRIS
                            </div>
                            <div class="flex justify-center gap-1">
                                <span class="w-5 h-2 bg-slate-900 inline-block"></span>
                                <span class="w-2 h-2 bg-slate-900 inline-block"></span>
                                <span class="w-4 h-2 bg-slate-900 inline-block"></span>
                            </div>
                        </div>

                        <div class="w-full flex justify-between">
                            <div class="w-12 h-12 border-4 border-slate-900 flex items-center justify-center p-1">
                                <div class="w-6 h-6 bg-slate-900"></div>
                            </div>
                            <div class="w-6 h-6 bg-slate-900 self-end mr-3 mb-2"></div>
                        </div>
                    </div>

                    <div class="mt-3 text-[11px] text-slate-500 font-mono truncate max-w-xs mx-auto">
                        Ref: {{ $intent?->provider_reference }}
                    </div>
                </div>

                <div class="text-xs text-slate-500">
                    Buka aplikasi GoPay, OVO, Dana, BCA, Mandiri, atau m-Banking Anda, lalu scan kode QR di atas.
                </div>

                <!-- Simulation & Actions -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center gap-2 justify-center no-print">
                    <button type="button" wire:click="simulateGatewayPayment"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl font-bold text-xs text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-200 transition">
                        ⚡ Simulasikan Pelanggan Scan & Bayar Sukses
                    </button>
                    <button type="button" wire:click="cancelPayment"
                        class="w-full sm:w-auto px-4 py-2.5 rounded-xl font-semibold text-xs text-red-600 hover:bg-red-50 border border-red-200 transition">
                        Batalkan Transaksi
                    </button>
                </div>
            @else
                <!-- Completed State -->
                <div class="p-6 bg-emerald-50 rounded-2xl border border-emerald-200 space-y-3">
                    <div class="w-14 h-14 bg-emerald-600 text-white rounded-full flex items-center justify-center mx-auto text-2xl font-black shadow-md">
                        ✓
                    </div>
                    <h4 class="font-bold text-emerald-900 text-base">Transaksi Berhasil Selesai!</h4>
                    <p class="text-xs text-emerald-700">Stok barang telah dipotong secara otomatis dan tercatat pada buku besar mutasi.</p>
                    <div class="pt-2 flex items-center justify-center gap-3 no-print">
                        <button type="button" onclick="window.print()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition">
                            🖨️ Cetak Struk
                        </button>
                        <a href="{{ route('sales.history') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition">
                            Lihat di Riwayat
                        </a>
                        <a href="{{ route('pos.checkout') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition">
                            Transaksi Baru
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
