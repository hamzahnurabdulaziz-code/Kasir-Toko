<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Shift & Rekonsiliasi Kas Kasir</h1>
            <p class="text-sm text-slate-500">Kelola sesi kasir, modal awal kas, mutasi uang fisik laci, dan tutup shift.</p>
        </div>

        @if(!$activeShift)
            <button type="button" wire:click="$set('showOpenShiftModal', true)"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-200 transition">
                💼 Buka Shift Baru
            </button>
        @endif
    </div>

    <!-- Active Shift Card (if any) -->
    @if($activeShift)
        <div class="bg-white rounded-2xl border-2 border-indigo-500/30 p-6 shadow-md space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-slate-900 text-lg">Shift Sedang Aktif</h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">OPEN</span>
                        </div>
                        <p class="text-xs text-slate-500">Terminal: <span class="font-semibold text-slate-800 font-mono">{{ $activeShift->terminal_label }}</span> • Dibuka: {{ $activeShift->opened_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" wire:click="$set('showCashMovementModal', true)"
                        class="px-3.5 py-2 rounded-xl font-bold text-xs text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                        ± Kas Masuk / Keluar
                    </button>
                    <button type="button" wire:click="$set('showCloseShiftModal', true)"
                        class="px-4 py-2 rounded-xl font-bold text-xs text-white bg-red-600 hover:bg-red-700 shadow-sm transition">
                        Tutup Shift Kasir
                    </button>
                </div>
            </div>

            <!-- Formula Grid BR-15 -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Modal Awal</span>
                    <div class="text-base font-bold text-slate-900 mt-1">Rp {{ number_format($activeShift->opening_cash, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 bg-emerald-50/50 rounded-xl border border-emerald-100">
                    <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">+ Penjualan Tunai</span>
                    <div class="text-base font-bold text-emerald-800 mt-1">Rp {{ number_format($cashSales, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 bg-blue-50/50 rounded-xl border border-blue-100">
                    <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">+ Kas Masuk</span>
                    <div class="text-base font-bold text-blue-800 mt-1">Rp {{ number_format($cashIn, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-100">
                    <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider">- Kas Keluar</span>
                    <div class="text-base font-bold text-amber-800 mt-1">Rp {{ number_format($cashOut, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-200 col-span-2 sm:col-span-1">
                    <span class="text-[11px] font-black text-indigo-700 uppercase tracking-wider">= Kas Harapan Fisik</span>
                    <div class="text-lg font-black text-indigo-900 mt-1">Rp {{ number_format($activeShift->expected_cash, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="text-xs text-slate-400 bg-slate-50 p-3 rounded-xl flex items-center justify-between">
                <span>ℹ️ Penjualan Non-Tunai (QRIS/Transfer/Kartu) tercatat <strong>Rp {{ number_format($nonCashSales, 0, ',', '.') }}</strong> (tidak menambah uang di laci fisik).</span>
            </div>
        </div>
    @endif

    <!-- Shift History Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-sm">Riwayat Sesi Shift Kasir</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Terminal</th>
                        <th class="px-5 py-3">Waktu Buka</th>
                        <th class="px-5 py-3">Waktu Tutup</th>
                        <th class="px-5 py-3 text-right">Modal Awal</th>
                        <th class="px-5 py-3 text-right">Kas Harapan</th>
                        <th class="px-5 py-3 text-right">Kas Aktual</th>
                        <th class="px-5 py-3 text-right">Selisih</th>
                        <th class="px-5 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($shifts as $shift)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-3 font-semibold text-slate-900 text-xs font-mono">
                                {{ $shift->terminal_label }}
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                {{ $shift->opened_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                {{ $shift->closed_at ? $shift->closed_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-5 py-3 text-right font-medium text-xs">
                                Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-xs text-slate-800">
                                Rp {{ number_format($shift->expected_cash, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-xs">
                                {{ $shift->actual_cash !== null ? 'Rp ' . number_format($shift->actual_cash, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-xs">
                                @if($shift->difference === null)
                                    -
                                @elseif($shift->difference == 0)
                                    <span class="text-emerald-600">Rp 0 (Pas)</span>
                                @elseif($shift->difference > 0)
                                    <span class="text-blue-600">+Rp {{ number_format($shift->difference, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-red-600">-Rp {{ number_format(abs($shift->difference), 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($shift->status === 'OPEN')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">OPEN</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">CLOSED</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-400">
                                Belum ada riwayat shift.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $shifts->links() }}
        </div>
    </div>

    <!-- MODAL BUKA SHIFT -->
    @if($showOpenShiftModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Buka Shift Kasir Baru</h3>
                    <button type="button" wire:click="$set('showOpenShiftModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                @if($errorMessage)
                    <div class="p-3 rounded-xl bg-red-50 text-xs text-red-700">{{ $errorMessage }}</div>
                @endif

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Label Terminal / Kasir</label>
                        <input wire:model="terminalLabel" type="text" required placeholder="Kasir-01"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 font-mono text-xs text-slate-900 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Modal Kas Awal di Laci (Rp)</label>
                        <input wire:model="openingCash" type="number" step="any" required placeholder="100000"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 font-bold text-slate-900 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Catatan</label>
                        <input wire:model="openShiftNote" type="text" placeholder="Shift Pagi / Reguler"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showOpenShiftModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Batal</button>
                    <button type="button" wire:click="openShift" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">Buka Shift</button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL CASH MOVEMENT -->
    @if($showCashMovementModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Catat Kas Masuk / Kas Keluar</h3>
                    <button type="button" wire:click="$set('showCashMovementModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                @if($errorMessage)
                    <div class="p-3 rounded-xl bg-red-50 text-xs text-red-700">{{ $errorMessage }}</div>
                @endif

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tipe Mutasi</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="$set('movementType', 'CASH_IN')"
                                class="py-2 rounded-xl text-xs font-bold border transition {{ $movementType === 'CASH_IN' ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-600' }}">
                                📥 Kas Masuk (In)
                            </button>
                            <button type="button" wire:click="$set('movementType', 'CASH_OUT')"
                                class="py-2 rounded-xl text-xs font-bold border transition {{ $movementType === 'CASH_OUT' ? 'border-amber-600 bg-amber-50 text-amber-700' : 'border-slate-200 text-slate-600' }}">
                                📤 Kas Keluar (Out)
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nominal (Rp)</label>
                        <input wire:model="movementAmount" type="number" step="any" required placeholder="50000"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 font-bold text-slate-900 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Alasan / Keperluan *</label>
                        <input wire:model="movementNote" type="text" placeholder="Misal: Tambah kembalian / Beli lakban & kresek"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showCashMovementModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Batal</button>
                    <button type="button" wire:click="recordCashMovement" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">Simpan Mutasi</button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL TUTUP SHIFT -->
    @if($showCloseShiftModal && $activeShift)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Tutup Shift & Rekonsiliasi Kas</h3>
                    <button type="button" wire:click="$set('showCloseShiftModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                @if($errorMessage)
                    <div class="p-3 rounded-xl bg-red-50 text-xs text-red-700">{{ $errorMessage }}</div>
                @endif

                <div class="p-3 bg-slate-50 rounded-xl space-y-1 text-xs">
                    <div class="flex justify-between text-slate-500"><span>Kas Harapan Menurut Sistem:</span><span class="font-bold text-slate-900">Rp {{ number_format($activeShift->expected_cash, 0, ',', '.') }}</span></div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Kas Aktual Fisik di Laci (Dihitung Manual) *</label>
                        <input wire:model.live="actualCash" type="number" step="any" required placeholder="0"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-lg font-black text-slate-900 focus:ring-indigo-500">
                    </div>

                    @php
                        $diff = $actualCash - (float)$activeShift->expected_cash;
                    @endphp
                    <div class="p-3 rounded-xl border flex items-center justify-between text-xs {{ $diff == 0 ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($diff > 0 ? 'bg-blue-50 border-blue-200 text-blue-800' : 'bg-red-50 border-red-200 text-red-800') }}">
                        <span class="font-bold">Estimasi Selisih Kas:</span>
                        <span class="font-extrabold text-sm">
                            {{ $diff >= 0 ? '+' : '' }}Rp {{ number_format($diff, 0, ',', '.') }}
                            ({{ $diff == 0 ? 'Seimbang' : ($diff > 0 ? 'Lebih' : 'Kurang') }})
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Catatan Penutupan Shift</label>
                        <input wire:model="closeShiftNote" type="text" placeholder="Semua aman / Ada selisih karena..."
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showCloseShiftModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Batal</button>
                    <button type="button" wire:click="closeShift" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-red-600 hover:bg-red-700 shadow-sm">Konfirmasi Tutup Shift</button>
                </div>
            </div>
        </div>
    @endif
</div>

