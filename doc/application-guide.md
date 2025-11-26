# Landing Page Builder – Application Guide

Concise, English-first guide for running, maintaining, and understanding the mini framework that powers this landing page builder.

## What’s new

- **Final kernel** coordinates boot: load `.env`, logger, container, router, then emits a `Response`.
- **Final router** supports dynamic patterns (`/api/payments/{orderId}/status`) and still honors legacy `?r=` query routes.
- **Request/Response wrappers** give structured access to method/route/headers/JSON body plus helpers for JSON/redirect responses.
- **Middleware pipeline** keeps global/route middleware simple before the handler runs.
- **Final container** (PSR-11 friendly) for singletons and auto-resolved classes, used by the kernel and router.
- **Centralized error handler** logs via `Logger`, returns JSON for API, and short text for web.
- **CLI `php aitictl`**: list routes, check DB health, or peek at the loaded environment.

## Quick setup

1. Clone/copy the project to your web root (example `D:/Laragon/www/landingpagebuilder`).
2. Import database:
   - `database/full_schema_templates.sql` (complete with sample templates/pages), or
   - `database/payments.sql` if you only need payment tables.
3. Copy `.env.example` → `.env`, then fill credentials:
   ```
   DB_HOST=127.0.0.1
   DB_NAME=landingpagebuilder
   DB_USER=root
   DB_PASS=your-db-pass
   DB_CHARSET=utf8mb4
   MIDTRANS_SERVER_KEY=your-server-key
   MIDTRANS_CLIENT_KEY=your-client-key
   MIDTRANS_MERCHANT_ID=your-merchant-id
   ```
   `Env::load()` runs in `public/index.php`, so `.env` values are immediately available.
4. Set `base_url` in `src/config/config.php` to match your public host.
5. Point your virtual host/web server to the `public/` directory.

## Core architecture

- `public/index.php` – front controller: load env/logger, start session, capture `Request`, run `Kernel`, send `Response`.
- `src/Core/Kernel.php` – registers routes to controllers, prepares the container + global middleware, and returns formatted responses.
- `src/Core/Router.php` – method-based matching; supports `{param}` placeholders and `?r=` fallback when present.
- `src/Core/Request.php` & `src/Core/Response.php` – lightweight HTTP wrappers (headers/query/body access, JSON/redirect helpers).
- `src/Core/MiddlewarePipeline.php` – chained middleware execution `fn(Request $r, $next)`.
- `src/Core/Container.php` – simple service locator/DI; auto-instantiates classes if not manually bound.
- `src/Core/ErrorHandler.php` – registers error/exception handlers; JSON for API, plain text for web/CLI.
- `src/Controllers/*` – business handlers (auth, dashboard, page builder, payments).
- `src/Views/*` – PHP views without a templating engine.
- `public/page/*` – published static outputs.
- `database/*` – SQL schema and seed data.

## Admin flow (short)

1. Log in (`/public/?r=login`).
2. Create a page: choose a template → fill title/slug/content → add CTA/product (for `order_type` `link`/`gateway`) + social/marketplace links.
3. Publish: choose “publish” from the page list → static file generated at `public/page/{slug}.html` plus link payload in `window.landingPageLinks`.
4. Dashboard shows page list and published URLs.

## Payment flow (QRIS Midtrans)

- `POST /api/payments/qris` → accepts `page_id`, optional amount/product name; stores in `payments`, then charges Midtrans.
- `GET /api/payments/{orderId}/status` or `GET /api/payments/status?order_id=` for status polling.
- `POST /webhook/midtrans` verifies signature (`order_id + status_code + gross_amount + serverKey`) before updating status.
- Status mapping: `settlement|capture` → settlement; `pending`; `expire`; `cancel`; `deny|failure` → failure.

## Logging & error handling

- JSON logs live in `log/log_error.txt` (auto-created). The directory is git-ignored.
- Errors/exceptions are recorded via `Logger`; `ErrorHandler` renders JSON for `Accept: application/json`/XHR callers.

## CLI: `php aitictl`

- `php aitictl routes` – list method/path → handler.
- `php aitictl health` – check database connection (`SELECT 1`).
- `php aitictl env` – show env snapshot (base URL, DB host/name/user, merchant ID).

## Support / Donate

- [Buy us coffee, cigarettes, and snacks on Saweria](https://saweria.co/aitisolutions)

## Fast maintenance

- Add template: drop HTML into `public/assets/templates/`, register metadata in the `templates` table.
- Delete page: use delete action in admin; static file is removed if inside `public/`.
- Test webhook: use `ngrok`/tunnel and point Midtrans to `https://<tunnel>/webhook/midtrans`.
