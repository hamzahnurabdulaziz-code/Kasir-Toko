# Urutan Implementasi Laravel V4 — Web Kasir Tanpa Login & QR Profil DANA

Dokumen ini merangkum tahapan implementasi teknis sistem Web Kasir V4.0 sesuai Product Requirements Document (PRD V4.0, 1 November 2026).

---

## 1. Fondasi & Konfigurasi Tanpa Login (No Auth)
- Hapus middleware autentikasi dan form login.
- Rute `/` dan `/login` langsung dialihkan ke `/dashboard` (`Route::redirect('/', '/dashboard')`).
- Hapus tabel `users`, `staff_invitations`, `password_reset_tokens`, dan `sessions`.
- Seluruh Foreign Key yang merujuk ke pengguna dihilangkan dari seluruh tabel transaksi dan audit.

---

## 2. Struktur Basis Data 24 Tabel
Tabel database terbagi atas 24 tabel bersih (InnoDB / SQLite teruji):
1. `cache`
2. `cache_locks`
3. `jobs`
4. `job_batches`
5. `failed_jobs`
6. `stores`
7. `store_settings`
8. `units`
9. `categories`
10. `products`
11. `stock_balances`
12. `stock_movements`
13. `customers`
14. `suppliers`
15. `cashier_shifts`
16. `purchases`
17. `purchase_items`
18. `sales`
19. `sale_items`
20. `stock_reservations`
21. `payment_intents` (dengan kolom `method = DANA_PROFILE_QR` & `qr_payment_profile_id`)
22. `payment_transactions`
23. `qr_payment_profiles` (Tabel Baru V4: foto QR DANA asli, sha256, status scan)
24. `manual_payment_confirmations` (Tabel Baru V4: pencatatan referensi transaksi DANA penerima & verifikasi kasir)

---

## 3. Pembayaran QR Profil DANA Asli Pemilik
- File gambar QR asli tersimpan di disk lokal: `public/assets/QR_Profil_DANA_Asli.jpeg` (dan `.png`).
- Nomor penerima: `62-813***2119`.
- Nilai SHA-256 diverifikasi pada basis data (`qr_payment_profiles.image_sha256`).
- Tampilan checkout `/payments/{sale}` menyajikan foto QR asli secara utuh beserta total tagihan belanja dalam ukuran font besar di luar gambar QR.

---

## 4. Alur Verifikasi Kasir & Pemotongan Stok Atomik
1. **Checkout POS**: Kasir memilih barang, menekan `📱 BAYAR QR DANA`. Status sale: `PENDING_PAYMENT`, stok barang direservasi (`reserved` bertambah, `available` berkurang).
2. **Pemeriksaan Mutasi**: Pembeli mentransfer via DANA. Kasir memeriksa uang masuk langsung pada aplikasi DANA akun penerima (tangkapan layar pembeli bukan bukti sah).
3. **Verifikasi Kasir**: Kasir menginput ID Transaksi / Nomor Referensi DANA dan nominal.
4. **Idempoten & Keunikan**: Nomor referensi DANA yang sama ditolak jika pernah dipakai transaksi lain.
5. **Eksekusi Atomik (`DB::transaction`)**:
   - `manual_payment_confirmations` dibuat dengan status `VERIFIED`.
   - `payment_intents.status` diubah ke `PAID`.
   - `sales.status` diubah ke `COMPLETED`.
   - Saldo fisik `on_hand` dan `reserved` dipotong 1 kali secara atomik.
   - `stock_movements` mencatat mutasi `SALE_OUT`.
6. **Pembatalan**: Jika transaksi dibatalkan, reservasi dilepas (`RELEASED`) dan stok kembali tersedia ke rak.

---

## 5. Navigasi & Halaman Livewire
- `/dashboard`: Ringkasan penjualan neto, kuantitas item keluar, dan peringatan stok menipis.
- `/pos`: Layar kasir responsif dengan tombol `📱 BAYAR QR DANA` dan `💵 BAYAR TUNAI`.
- `/payments/{sale}`: Halaman QR DANA asli, countdown, status pembayaran, dan form verifikasi kasir.
- `/settings/payment-qr`: Pengaturan profil QR DANA toko, uji scan, dan aktivasi.
- `/sales`: Riwayat faktur penjualan dan pencetakan nota/struk kasir.
- `/inventory`: Saldo on hand, reserved, sisa stok, dan riwayat mutasi barang.
- `/products`: CRUD master produk, harga, SKU unik, satuan, dan kategori.

