# Landing Page Builder – Documentation

## Ringkasan

Aplikasi PHP Native (tanpa framework) untuk membuat landing page, mem-publish HTML statis, dan opsi pembayaran QRIS via Midtrans. Admin mengelola halaman dari panel, memilih template, dan men-generate file statis di `public/page`.

## Kebutuhan sistem

- PHP 8.1+ (cURL, PDO MySQL)
- MySQL/MariaDB
- Web server yang mengarahkan root ke `public/`
- Laragon/WAMP/Nginx apa pun

## Struktur penting

- `public/` – front controller (`index.php`), assets, file publik, dan hasil publish di `public/page`.
- `src/Controllers/` – controller admin & payment.
- `src/Core/` – router, auth, logger, env loader, database helper.
- `src/Views/` – view admin (PHP).
- `database/` – skema & seed contoh.
- `documentations/` – dokumen ini dan bahan referensi lain.

## Setup awal

1. Clone/copy project ke web root (misal `D:/Laragon/www/landingpagebuilder`).
2. Import database:
   - Gunakan `database/full_schema_templates.sql` (berisi contoh data template & page), atau
   - Gunakan `database/payments.sql` bila hanya butuh tabel pembayaran.
3. Salin `.env.example` menjadi `.env`, lalu isi kredensial Midtrans (sandbox/production):
   ```
   MIDTRANS_SERVER_KEY=your-server-key
   MIDTRANS_CLIENT_KEY=your-client-key
   MIDTRANS_MERCHANT_ID=your-merchant-id
   ```
   File `.env` otomatis diload di `public/index.php`. **Jangan commit `.env`** (sudah di-ignore).
4. Sesuaikan `src/config/config.php` jika base URL atau kredensial database berbeda.
5. Arahkan virtual host/root web server ke folder `public/`.

## Kredensial Midtrans

- Wajib diisi di `.env`; jika kosong, endpoint payment/webhook akan membalas error.
- Endpoint charge memakai sandbox URL (`https://api.sandbox.midtrans.com/v2/charge`); ganti ke production bila diperlukan.
- Signature webhook divalidasi menggunakan `MIDTRANS_SERVER_KEY`.

## Alur penggunaan admin

1. Login (`/public/?r=login`). (Gunakan user pada tabel `users`; sesuaikan password di DB).
2. Buat halaman:
   - Pilih template → isi judul, slug, konten (CanvasEditor), CTA/produk (untuk order_type `link`/`gateway`), serta link sosial/marketplace.
   - Link sosial/marketplace boleh kosong; jika semua kosong, section `<!--SOCIAL_LINKS-->` tidak ditampilkan saat publish.
3. Publish halaman:
   - Dari daftar halaman pilih “publish” → file statis dibuat di `public/page/{slug}.html`.
   - CTA/link yang tersimpan ikut dipayload ke JS global `window.landingPageLinks`.
4. Dashboard menampilkan daftar halaman beserta URL publish.

## Alur pembayaran (QRIS Midtrans)

- Endpoint `POST /api/payments/qris` (route `api/payments/qris`) menerima `page_id` dan optional `amount`/`product_name`.
- Order dicatat di tabel `payments`, lalu charge Midtrans dilakukan via server key.
- Response berisi `order_id`, `qr_url`, `expiry_time`.
- Polling status: `GET /api/payments/{order_id}/status`.
- Webhook: `POST /webhook/midtrans` memverifikasi signature (`order_id + status_code + gross_amount + serverKey`).
- Status mapping: `settlement|capture` → settlement, `pending`, `expire`, `cancel`, `deny|failure` → failure.

## Logging & error

- Log disimpan di folder `log/` (misal `log_error.txt`). Folder ini di-ignore; simpan log sensitif di luar git.
- Jika kredensial Midtrans hilang, server memberi HTTP 500 dengan pesan konfigurasi belum diset.

## Keamanan & praktik

- Jangan commit `.env` atau file log (sudah di `.gitignore`).
- Isi slug hanya huruf kecil/angka/tanda hubung (validasi di controller).
- Saat deploy production, pastikan HTTPS aktif dan `base_url` sesuai domain publik.

## Maintenance cepat

- Tambah template: letakkan file HTML di `public/assets/templates/`, daftarkan metadata di tabel `templates`.
- Hapus halaman: gunakan aksi delete di admin; file statis terhapus jika berada di dalam `public/`.
- Uji webhook: gunakan `ngrok` atau tunnel lain lalu set URL `https://<tunnel>/webhook/midtrans` di dashboard Midtrans.
