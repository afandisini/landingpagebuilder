# Security Policy

## Reporting a Vulnerability
- Email security@landingpagebuilder.local with a detailed report (proof of concept, impact, logs); please avoid public issues.
- We acknowledge within 72 hours and coordinate fixes and disclosure privately.

## Best Practices and Scope
- Secrets: never commit .env files, payment keys, or log files that may contain sensitive data; rotate exposed credentials immediately.
- Production hygiene: disable display_errors and verbose stack traces in production; log errors to files outside the web root and keep permissions tight.
- Slug safety: validate and sanitize page slugs before writing public/page/{slug}.html to prevent traversal, collisions, or script injection.
- Webhooks: verify Midtrans (and other payment) webhook signatures, timestamps, and idempotency tokens before processing; audit and rate-limit webhook endpoints.
- Public hardening: keep public/ restricted (deny sensitive files, block PHP in uploads, avoid serving backups/exports from webroot), and patch servers promptly.

## Internal References
Refer to the internal documentation in doc/ and team runbooks for deployment, key rotation, and webhook verification specifics.
