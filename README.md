# Landing Page Builder

Landing page & payment gateway builder written in plain PHP native. Includes admin panel for creating pages from templates, publishing static HTML, and optional QRIS payments via Midtrans.

## Quick start

1. Clone/copy this project into your web root (example: `D:/Laragon/www/landingpagebuilder`).
2. Copy `.env.example` to `.env` and fill your DB + Midtrans credentials:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`
   - `MIDTRANS_SERVER_KEY`
   - `MIDTRANS_CLIENT_KEY`
   - `MIDTRANS_MERCHANT_ID`
3. Import the database schema from `database/full_schema_templates.sql` (or your own dump).
4. Set the base URL in `src/config/config.php` to match your host (default: `http://localhost/landingpagebuilder/public`).
5. Serve the `public/` directory via your web server (Laragon/Apache/Nginx).

## Architecture highlights

- **Kernel-driven boot**: front controller loads env/logger, captures `Request`, runs the `Kernel`, and sends a `Response`.
- **Router**: supports `{param}` paths plus legacy `?r=` routing, mapped to controllers.
- **Middleware & container**: lightweight pipeline plus a PSR-11-friendly container for auto-resolving dependencies.
- **Request/Response wrappers**: helpers for JSON/redirects and structured request access.
- **Central error handling**: logs via `Logger`, returns JSON for APIs.

## Folder structure (high level)

- `public/` – front controller (`index.php`), tracking pixel (`tracker.php`), static assets, and published pages under `public/page/{slug}.html`.
- `src/Core/` – env loader, kernel, router, container, middleware, logger, DB helper, auth/session helper, misc helper.
- `src/Controllers/` – request handlers for auth, dashboard, pages, and payments.
- `src/Models/` – database access for users, pages, templates, visits.
- `src/Views/` – Blade-less PHP views (layouts, admin, auth).
- `database/` – SQL schema dumps for initial setup.
- `log/` – application log output (`log_error.txt`).
- `vendor/` – Composer dependencies.

## Notes

- `.env` is ignored by git to avoid leaking secrets. Keep your real keys only in `.env`.
- Admin UI lives under `?r=login` or `/admin/dashboard` after authentication.
- Publish a page from the admin list to generate a static file under `public/page/{slug}.html`.

## Documentation

- See `doc/application-guide.md` for the updated English guide (architecture, setup, CLI, payments).

## Support / Donate

- [Buy us coffee, cigarettes, and snacks on Saweria](https://saweria.co/aitisolutions)  🤟😎

## License

MIT License (see `LICENSE`).
