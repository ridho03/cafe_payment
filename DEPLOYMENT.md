# Deployment Guide - Payment Cafe

## Checklist Sebelum Deploy

- PHP 8.2+ tersedia di hosting/VPS.
- Composer tersedia, atau dependency `vendor/` di-build lokal lalu di-upload.
- Node.js/npm tersedia untuk build asset, atau folder `public/build` di-build lokal lalu di-upload.
- Domain/subdomain diarahkan ke folder `public`.
- `.env` production sudah dibuat dan tidak ikut dipublikasi.
- Database production sudah dibuat.
- `APP_KEY` sudah ada.
- `APP_DEBUG=false`.

## Environment Production

Minimal `.env` production:

```env
APP_NAME="Payment Cafe"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://domain-kamu.com

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=user_database
DB_PASSWORD=password_database

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@domain-kamu.com"
MAIL_FROM_NAME="${APP_NAME}"

MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

Generate key jika belum ada:

```bash
php artisan key:generate
```

## Deploy VPS

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan web server mengarah ke:

```text
/path/to/payment-cafe/public
```

Permission folder yang harus bisa ditulis:

```text
storage/
bootstrap/cache/
public/uploads/
```

Contoh Linux:

```bash
chmod -R ug+rw storage bootstrap/cache public/uploads
```

## Deploy cPanel

Opsi paling aman:

1. Upload seluruh project ke luar `public_html`, misalnya:

```text
/home/username/payment-cafe
```

2. Set document root domain/subdomain ke:

```text
/home/username/payment-cafe/public
```

3. Jika cPanel tidak mengizinkan document root diubah, upload isi folder `public` ke `public_html`, lalu edit `public_html/index.php` agar path `vendor/autoload.php` dan `bootstrap/app.php` mengarah ke folder project asli. Opsi ini lebih rawan, jadi gunakan hanya jika terpaksa.

4. Jalankan command Laravel dari Terminal cPanel:

```bash
cd /home/username/payment-cafe
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika Node.js tidak tersedia di cPanel, jalankan lokal:

```bash
npm ci
npm run build
```

Lalu upload folder `public/build`.

## Midtrans

Notification URL di dashboard Midtrans:

```text
https://domain-kamu.com/midtrans/notification
```

Catatan:

- Route notification sudah dikecualikan dari CSRF.
- Untuk sandbox, gunakan key sandbox dan `MIDTRANS_IS_PRODUCTION=false`.
- Untuk production, gunakan key production dan `MIDTRANS_IS_PRODUCTION=true`.
- `APP_URL` harus domain publik yang bisa diakses Midtrans.

## Setelah Deploy

Jalankan smoke test:

```text
/up
/login
/order/MEJA-01
```

Login demo:

```text
Admin  : admin@payment-cafe.test / password
Kasir  : kasir@payment-cafe.test / password
Dapur  : dapur@payment-cafe.test / password
```

Segera ganti password demo sebelum dipakai production.

## Maintenance

Setelah mengubah `.env`:

```bash
php artisan config:clear
php artisan config:cache
```

Setelah deploy kode baru:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
