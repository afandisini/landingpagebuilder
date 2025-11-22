# Landing Page Builder
Landing page & payment gateway builder written in plain PHP (no framework). Includes admin panel for creating pages from templates, publishing static HTML, and optional QRIS payments via Midtrans.

## Quick start
1. Clone/copy this project into your web root (example: `D:/Laragon/www/landingpagebuilder`).
2. Copy `.env.example` to `.env` and fill your Midtrans credentials:
   - `MIDTRANS_SERVER_KEY`
   - `MIDTRANS_CLIENT_KEY`
   - `MIDTRANS_MERCHANT_ID`
3. Import the database schema from `database/full_schema_templates.sql` (or your own dump).
4. Set the base URL in `src/config/config.php` to match your host (default: `http://localhost/landingpagebuilder/public`).
5. Serve the `public/` directory via your web server (Laragon/Apache/Nginx).

## Notes
- `.env` is ignored by git to avoid leaking secrets. Keep your real keys only in `.env`.
- Admin UI lives under `?r=login` or `/admin/dashboard` after authentication.
- Publish a page from the admin list to generate a static file under `public/page/{slug}.html`.

## Documentation
Detailed documentation is available in `documentations/` (see `documentations/application-guide.md`).

## License
MIT License (see `LICENSE`).
