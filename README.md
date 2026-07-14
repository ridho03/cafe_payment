# Payment Cafe

Payment Cafe adalah aplikasi POS coffee shop berbasis Laravel 12. Fitur utama:

- QR meja untuk pelanggan memesan langsung dari meja.
- Menu digital dengan foto, kategori, ketersediaan, dan varian Hot/Ice.
- Checkout pelanggan dengan subtotal, biaya layanan, dan status order.
- Kasir untuk menandai pembayaran lunas dan mencetak struk.
- Kitchen display untuk memproses antrian dapur.
- Admin untuk dashboard, menu, pesanan, QR meja, laporan, dan maintenance.
- Integrasi Midtrans Snap opsional untuk pembayaran cashless.
- Laporan penjualan, export CSV, export SQL, auto-refresh, dan notifikasi suara untuk operasional.

## Kebutuhan

- PHP 8.2 atau lebih baru
- Composer
- Node.js 20+ dan npm
- SQLite untuk lokal, atau MySQL/MariaDB untuk hosting/VPS
- Extension PHP umum Laravel: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd` atau `imagick`

## Menjalankan Lokal

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Buka:

```text
http://127.0.0.1:8000/login
```

Untuk mode frontend development:

```bash
npm run dev
```

## Akun Awal

Catatan: akun awal tidak ditampilkan di halaman login agar tampilan production tetap aman dan rapi.
Ganti password semua akun sebelum dipakai transaksi real.

```text
Developer: isi `DEVELOPER_EMAIL` dan `DEVELOPER_PASSWORD` di `.env`, lalu jalankan seeder.
Admin    : admin@payment-cafe.test / password
Kasir    : kasir@payment-cafe.test / password
Dapur    : dapur@payment-cafe.test / password
```

Contoh URL order pelanggan setelah seed:

```text
http://127.0.0.1:8000/order/MEJA-01
```

## Testing

```bash
php artisan test
npm run build
```

## Deploy Ringkas

1. Upload project ke server.
2. Arahkan document root domain/subdomain ke folder `public`.
3. Buat `.env` production dari `.env.example`.
4. Jalankan:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Untuk detail VPS/cPanel dan variabel environment, lihat [DEPLOYMENT.md](DEPLOYMENT.md).

## Konfigurasi Penting

Production wajib:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-kamu.com
```

Database MySQL/MariaDB contoh:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=user_database
DB_PASSWORD=password_database
```

Midtrans opsional:

```env
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

Jika memakai Midtrans production, set `MIDTRANS_IS_PRODUCTION=true` dan isi key production.

## Catatan Penggunaan Operasional

Alur standar:

1. Admin login dan memastikan meja QR aktif.
2. Admin membuka Admin > Meja lalu cetak semua QR atau export SVG per meja.
   Mode cetak tersedia sebagai kartu meja 4 QR per A4 atau label kecil 8 QR per A4.
3. Pelanggan scan QR meja, pilih menu, varian, dan jumlah.
4. Pelanggan checkout, lalu bayar lewat mode yang tersedia.
5. Kasir membuka halaman Kasir, menandai order lunas, dan mencetak struk 58mm/80mm.
6. Dapur membuka halaman Dapur, memproses pesanan dari Diterima ke Diproses, Siap, lalu Selesai.
7. Waiter/kasir klik tombol Aktifkan suara agar notifikasi perubahan order terdengar.

Maintenance:

- Admin > Maintenance hanya bisa dibuka akun developer/penyedia aplikasi.
- Developer bisa export SQL manual untuk backup database.
- Developer bisa membuat user internal baru untuk admin cafe, kasir, dapur, dan developer.
- Tombol Clear Cache tersedia untuk membersihkan cache config, route, view, dan aplikasi setelah perubahan deploy.

Hal yang wajib diganti sebelum production:

- Password akun developer, admin, kasir, dan dapur.
- Nama cafe di `APP_NAME` jika brand final sudah ada.
- Data menu, harga, kategori, varian, dan foto menu.
- `APP_URL` sesuai domain.
- Database user/password production, jangan memakai root.
- Key Midtrans jika pembayaran live dipakai.

Hal yang disarankan ditambahkan setelah launching awal:

- Setting profil cafe untuk alamat, nomor WA, dan teks footer struk.
- Backup database otomatis terjadwal selain export SQL manual.
- Diskon, pajak, dan service charge yang bisa diatur dari admin.
- Stok bahan/menu jika cafe ingin kontrol inventory.

## Catatan Asset

Foto login disimpan lokal di `public/images/login-coffee.jpg` agar halaman login tidak bergantung pada hotlink eksternal. Sumber foto: Unsplash, coffee shop counter by Haberdoedas.
