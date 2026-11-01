<div class="h-[calc(100vh-6rem)] flex flex-col lg:flex-row gap-6">
    <!-- LEFT COLUMN: Product Catalog & Search (60%) -->
    <div class="flex-1 flex flex-col bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <!-- Top Toolbar: Search & Barcode Scan -->
        <div class="p-4 border-b border-slate-100 space-y-3">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" autofocus
                    class="block w-full rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 text-sm placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-hidden transition"
                    placeholder="Scan Barcode atau ketik nama produk / SKU...">
            </div>

            <!-- Category Pills Filter -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs scrollbar-thin">
                <button wire:click="$set('selectedCategoryId', null)"
                    class="px-3 py-1.5 rounded-lg font-semibold flex-shrink-0 transition {{ is_null($selectedCategoryId) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Semua Kategori
                </button>
                @foreach ($categories as $cat)
                    <button wire:click="$set('selectedCategoryId', {{ $cat->id }})"
                        class="px-3 py-1.5 rounded-lg font-semibold flex-shrink-0 transition {{ $selectedCategoryId === $cat->id ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $cat->name }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Product Grid -->
        <div class="flex-1 p-4 overflow-y-auto">
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                @forelse ($products as $p)
                    @php
                        $available = $p->available_stock;
                        $isOutOfStock = $p->track_stock && $available <= 0;
                    @endphp
                    <button type="button" wire:click="addToCart({{ $p->id }})" {{ $isOutOfStock ? 'disabled' : '' }}
                        class="text-left p-3.5 rounded-xl border {{ $isOutOfStock ? 'border-slate-200 bg-slate-50 opacity-60 cursor-not-allowed' : 'border-slate-200 bg-white hover:border-indigo-400 hover:shadow-md hover:scale-[1.01]' }} flex flex-col justify-between transition group">
                        <div>
                            <div class="flex items-center justify-between gap-1 mb-1.5">
                                <span class="text-[10px] font-mono font-bold text-slate-400 truncate">{{ $p->sku }}</span>
                                @if($p->track_stock)
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md {{ $available <= $p->min_stock ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        Tersedia: {{ number_format($available, 0, ',', '.') }} {{ $p->unit->symbol }}
                                    </span>
                                @else
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600">Jasa</span>
                                @endif
                            </div>
                            <h4 class="font-bold text-slate-800 text-xs sm:text-sm line-clamp-2 leading-tight group-hover:text-indigo-600 transition">{{ $p->name }}</h4>
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs sm:text-sm font-extrabold text-indigo-700">Rp {{ number_format($p->sale_price, 0, ',', '.') }}</span>
                            <span class="text-xs text-indigo-500 opacity-0 group-hover:opacity-100 font-bold transition">+ Tambah</span>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full py-16 text-center text-slate-400">
                        <svg class="mx-auto h-12 w-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <p class="font-medium">Tidak ada produk ditemukan.</p>
                        <p class="text-xs mt-1">Coba kata kunci lain atau pilih kategori Semua.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Cart, Checkout & Payment (40%) -->
    <div class="w-full lg:w-[420px] xl:w-[480px] flex flex-col bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden flex-shrink-0">
        <!-- Cart Header -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Keranjang Transaksi</h3>
                <p class="text-xs text-slate-500">{{ count($cart) }} macam produk</p>
            </div>
            @if(count($cart) > 0)
                <button type="button" wire:click="clearCart" class="text-xs font-semibold text-red-600 hover:text-red-800 transition">
                    Kosongkan
                </button>
            @endif
        </div>

        <!-- Alert messages in Cart -->
        @if($errorMessage)
            <div class="mx-4 mt-3 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ $errorMessage }}</span>
            </div>
        @endif

        <!-- Cart Items List -->
        <div class="flex-1 p-4 overflow-y-auto divide-y divide-slate-100">
            @forelse ($cart as $item)
                <div class="py-3 first:pt-0 last:pb-0">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1">
                            <h5 class="text-xs sm:text-sm font-semibold text-slate-900 leading-tight">{{ $item['name'] }}</h5>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                @Rp {{ number_format($item['price'], 0, ',', '.') }} / {{ $item['unit'] }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs sm:text-sm font-bold text-slate-900">
                                Rp {{ number_format(($item['price'] * $item['quantity']) - ($item['discount'] ?? 0), 0, ',', '.') }}
                            </div>
                            <button type="button" wire:click="removeFromCart({{ $item['product_id'] }})" class="text-[11px] text-red-500 hover:text-red-700 transition">
                                Hapus
                            </button>
                        </div>
                    </div>

                    <!-- Quantity Control Buttons -->
                    <div class="flex items-center justify-between mt-2">
                        <div class="inline-flex items-center border border-slate-200 rounded-lg overflow-hidden bg-white">
                            <button type="button" wire:click="decrementQuantity({{ $item['product_id'] }})" class="px-2.5 py-1 text-slate-600 hover:bg-slate-100 font-bold text-xs transition">
                                -
                            </button>
                            <input type="number" step="any" min="1"
                                value="{{ $item['quantity'] }}"
                                wire:change="updateQuantity({{ $item['product_id'] }}, $event.target.value)"
                                class="w-12 text-center text-xs font-bold border-0 focus:ring-0 p-1 text-slate-800">
                            <button type="button" wire:click="incrementQuantity({{ $item['product_id'] }})" class="px-2.5 py-1 text-slate-600 hover:bg-slate-100 font-bold text-xs transition">
                                +
                            </button>
                        </div>

                        @if($item['track_stock'])
                            <span class="text-[10px] text-slate-400">Tersedia: {{ number_format($item['stock'], 0, ',', '.') }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-16 text-center text-slate-400">
                    <svg class="mx-auto h-10 w-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <p class="text-xs font-medium">Keranjang masih kosong.</p>
                    <p class="text-[11px] mt-0.5">Klik produk di sebelah kiri atau scan barcode.</p>
                </div>
            @endforelse
        </div>

        <!-- Checkout Calculation & Pay Buttons -->
        <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-3">
            <!-- Customer selection -->
            <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-slate-600">Pelanggan:</span>
                <select wire:model="customerId" class="rounded-lg border-slate-200 text-xs py-1 px-2.5 bg-white text-slate-800 focus:ring-indigo-500">
                    @foreach ($customers as $cust)
                        <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Discount field -->
            <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-slate-600">Diskon Transaksi (Rp):</span>
                <input wire:model.live.debounce.300ms="cartDiscount" type="number" min="0" placeholder="0"
                    class="w-28 text-right rounded-lg border-slate-200 text-xs py-1 px-2.5 bg-white text-slate-800 focus:ring-indigo-500">
            </div>

            <!-- Tax toggle -->
            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-1.5 cursor-pointer text-slate-600 font-semibold">
                    <input wire:model.live="applyTax" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500">
                    <span>Pajak Toko ({{ $store->tax_percentage ?? 0 }}%)</span>
                </label>
                <span class="font-medium text-slate-700">Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
            </div>

            <!-- Subtotal & Grand Total -->
            <div class="pt-2 border-t border-slate-200/80 space-y-1">
                <div class="flex justify-between text-xs text-slate-500">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-baseline pt-1">
                    <span class="text-sm font-bold text-slate-800">TOTAL TAGIHAN</span>
                    <span class="text-xl sm:text-2xl font-black text-indigo-700 tracking-tight">
                        Rp {{ number_format($totalAmount, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Pay Now Button: Bayar Tunai (Cash Only) -->
            <div class="pt-1">
                <button type="button" wire:click="openPaymentModal('CASH')" {{ count($cart) === 0 ? 'disabled' : '' }}
                    class="w-full py-3.5 px-4 rounded-xl font-black text-sm tracking-wide text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-200 hover:shadow-lg transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>BAYAR TUNAI (CASH)</span>
                </button>
            </div>
        </div>
    </div>

    <!-- PAYMENT MODAL (CASH ONLY) -->
    @if($showPaymentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Pembayaran Tunai</h3>
                        <p class="text-xs text-slate-500">Hitung uang yang diterima dan kembalian pelanggan.</p>
                    </div>
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                @if($errorMessage)
                    <div class="p-3 rounded-xl bg-red-50 text-xs text-red-700">{{ $errorMessage }}</div>
                @endif

                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-center">
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Total Tagihan Belanja</span>
                    <div class="text-3xl font-black text-emerald-900 mt-1">Rp {{ number_format($totalAmount, 0, ',', '.') }}</div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Uang Tunai Diterima (Rp)</label>
                        <input wire:model.live="tenderedAmount" type="number" step="any"
                            class="w-full rounded-xl border border-slate-200 text-xl font-bold px-3.5 py-2 text-slate-900 focus:ring-emerald-500">
                    </div>

                    <!-- Quick Cash Pills -->
                    <div class="flex flex-wrap gap-1.5 text-xs">
                        <button type="button" wire:click="setQuickCash({{ $totalAmount }})" class="px-2.5 py-1.5 rounded-lg bg-emerald-100 font-bold text-emerald-800 hover:bg-emerald-200">Uang Pas</button>
                        <button type="button" wire:click="setQuickCash(20000)" class="px-2.5 py-1.5 rounded-lg bg-slate-100 font-medium text-slate-700 hover:bg-slate-200">20.000</button>
                        <button type="button" wire:click="setQuickCash(50000)" class="px-2.5 py-1.5 rounded-lg bg-slate-100 font-medium text-slate-700 hover:bg-slate-200">50.000</button>
                        <button type="button" wire:click="setQuickCash(100000)" class="px-2.5 py-1.5 rounded-lg bg-slate-100 font-medium text-slate-700 hover:bg-slate-200">100.000</button>
                        <button type="button" wire:click="setQuickCash(200000)" class="px-2.5 py-1.5 rounded-lg bg-slate-100 font-medium text-slate-700 hover:bg-slate-200">200.000</button>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Kembalian:</span>
                        <span class="text-lg font-black {{ $changeAmount >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                            Rp {{ number_format($changeAmount, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="w-1/3 py-2.5 px-4 rounded-xl font-semibold text-xs text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="button" wire:click="processCheckout" wire:loading.attr="disabled"
                        class="flex-1 py-3 px-4 rounded-xl font-bold text-sm text-white bg-emerald-600 hover:bg-emerald-700 shadow-emerald-200 shadow-md transition disabled:opacity-50">
                        <span wire:loading.remove>Selesaikan Pembayaran Tunai</span>
                        <span wire:loading class="inline-flex items-center gap-2">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- RECEIPT MODAL (CASH TRANSACTION) -->
    @if($showReceiptModal && $completedSale)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 space-y-4 my-8">
                <div id="receipt-area" class="font-mono text-xs text-slate-800 space-y-3 p-4 bg-slate-50/50 rounded-xl border border-slate-200">
                    <div class="text-center pb-2 border-b border-dashed border-slate-300">
                        <h4 class="font-black text-sm uppercase text-slate-900">{{ $store?->name }}</h4>
                        <p class="text-[10px] text-slate-500">{{ $store?->address }}</p>
                        <p class="text-[10px] text-slate-500">Telp: {{ $store?->phone }}</p>
                    </div>

                    <div class="text-[10px] space-y-0.5 pb-2 border-b border-dashed border-slate-300">
                        <div class="flex justify-between"><span>No Nota:</span><span class="font-bold">{{ $completedSale->invoice_no }}</span></div>
                        <div class="flex justify-between"><span>Tanggal:</span><span>{{ $completedSale->created_at->format('d/m/Y H:i') }}</span></div>
                        @if($completedSale->customer)
                            <div class="flex justify-between"><span>Pelanggan:</span><span>{{ $completedSale->customer->name }}</span></div>
                        @endif
                    </div>

                    <!-- Items -->
                    <div class="space-y-1.5 pb-2 border-b border-dashed border-slate-300 text-[11px]">
                        @foreach ($completedSale->items as $item)
                            <div>
                                <div class="font-semibold">{{ $item->product_name_snapshot }}</div>
                                <div class="flex justify-between text-slate-600 text-[10px]">
                                    <span>{{ number_format($item->quantity, 0, ',', '.') }} x {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                                    <span>Rp {{ number_format($item->line_total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Totals -->
                    <div class="space-y-1 text-[11px] pb-2 border-b border-dashed border-slate-300">
                        <div class="flex justify-between"><span>Subtotal:</span><span>Rp {{ number_format($completedSale->subtotal, 0, ',', '.') }}</span></div>
                        @if($completedSale->discount_amount > 0)
                            <div class="flex justify-between text-red-600"><span>Diskon:</span><span>-Rp {{ number_format($completedSale->discount_amount, 0, ',', '.') }}</span></div>
                        @endif
                        @if($completedSale->tax_amount > 0)
                            <div class="flex justify-between"><span>Pajak:</span><span>Rp {{ number_format($completedSale->tax_amount, 0, ',', '.') }}</span></div>
                        @endif
                        <div class="flex justify-between font-bold text-xs pt-1 border-t border-slate-200">
                            <span>TOTAL:</span>
                            <span>Rp {{ number_format($completedSale->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="text-[10px] space-y-0.5 pb-2 border-b border-dashed border-slate-300">
                        <div class="flex justify-between"><span>Metode:</span><span class="font-bold">{{ $completedSale->payment_method }}</span></div>
                    </div>

                    <div class="text-center text-[9px] text-slate-400 pt-1">
                        {{ $store?->receipt_footer ?? 'Terima kasih atas kunjungan Anda!' }}
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button type="button" onclick="window.print()" class="flex-1 py-2.5 px-3 rounded-xl font-bold text-xs text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 flex items-center justify-center gap-2 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Cetak Struk</span>
                    </button>
                    <button type="button" wire:click="closeReceipt" class="flex-1 py-2.5 px-3 rounded-xl font-bold text-xs text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-200 transition">
                        Transaksi Baru (Esc)
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
