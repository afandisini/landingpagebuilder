# Landing Page Builder

Landing page & payment gateway builder written in plain PHP native. Includes admin panel for creating pages from templates, publishing static HTML, and optional QRIS payments via Midtrans.

## Quick start

1. Clone/copy this project into your web root (example: `D:/Laragon/www/landingpagebuilder`).
2. Copy `.env.example` to `.env` and fill your Midtrans credentials:
   - `MIDTRANS_SERVER_KEY`
   - `MIDTRANS_CLIENT_KEY`
   - `MIDTRANS_MERCHANT_ID`
3. Import the database schema from `database/full_schema_templates.sql` (or your own dump).
4. Set the base URL in `src/config/config.php` to match your host (default: `http://localhost/landingpagebuilder/public`).
5. Serve the `public/` directory via your web server (Laragon/Apache/Nginx).

## Folder structure (high level)

- `public/` – front controller (`index.php`), tracking pixel (`tracker.php`), static assets, and published pages under `public/page/{slug}.html`.
- `src/Core/` – Env loader, router, logger, DB connection, auth/session helper, misc helper.
- `src/Controllers/` – Request handlers for auth, dashboard, pages, and payments.
- `src/Models/` – Database access for users, pages, templates, visits.
- `src/Views/` – Blade-less PHP views (layouts, admin, auth).
- `database/` – SQL schema dumps for initial setup.
- `log/` – Application log output (`log_error.txt`).
- `vendor/` – Composer dependencies.

## Security review (initial checklist)

- Secrets in repo: `.env` currently stores live Midtrans keys and merchant ID. Rotate the keys, keep only placeholders in `.env.example`, and ensure `.env` never leaves the server.
- DB creds: `src/config/config.php` hardcodes `root` with an empty password. Move DB credentials to env vars, use a dedicated DB user with a strong password and least privilege.
- Payment integrity: `api/payments/qris` accepts client-supplied `amount`/`product_name`, so anyone can undercharge/overcharge for any `page_id`. Lock these values to server-side product config and ensure the page is in gateway mode before charging.
- TLS hardening: Midtrans client disables TLS verification when `cacert.pem` is missing. Fail closed instead of turning off `CURLOPT_SSL_VERIFYPEER/SSL_VERIFYHOST`.
- Admin protections: No CSRF tokens on admin POSTs and no session hardening (`session_regenerate_id`, secure/HttpOnly/SameSite cookies, HTTPS). Add CSRF tokens and tighten session settings.
- Auth resilience: Login lacks rate limiting/brute-force protection; consider basic throttling and logging of failed attempts.
- Content safety: Published `html_content` renders unfiltered; if untrusted authors exist, add sanitization or a safer editing mode to prevent stored XSS.

## Notes

- `.env` is ignored by git to avoid leaking secrets. Keep your real keys only in `.env`.
- Admin UI lives under `?r=login` or `/admin/dashboard` after authentication.
- Publish a page from the admin list to generate a static file under `public/page/{slug}.html`.

## Documentation

Detailed documentation is available in `doc/` (see `doc/application-guide.md`).

## License

MIT License (see `LICENSE`).
