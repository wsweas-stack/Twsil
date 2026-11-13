# Setup Guide — توصيلي Logistics Platform

## Requirements

- PHP 8.4+ with `pdo_mysql`, `mbstring`, `openssl`, `gd`, `zip`, `fileinfo`, `curl` (all enabled on this machine)
- Composer 2.x
- MySQL 8.x (WAMP — `C:\wamp64\bin\mysql\mysql8.4.7`, root / empty password locally, port 3306)
- Node.js 20+ and npm (for Vite / Tailwind asset builds)

## Installed Stack (verified)

| Package | Version | Notes |
|---|---|---|
| Laravel | 13.39.0 | PHP ^8.3 — running on PHP 8.4.15 |
| Filament | 5.11.0 | One unified internal panel (Phase 2) |
| Spatie Laravel Permission | 8.3.2 | Explicit Laravel 13 support; migrated |
| Stripe PHP | 22.1.0 | Test-mode Checkout Session creation, retrieval, and expiration |
| Endroid QR Code | 6.1.0 | PHP ^8.4; private PNG QR generation |
| Laravel Dompdf wrapper | 3.1.2 | Explicit Illuminate 13 compatibility; A6 labels |
| Smalot PDF Parser | 2.13.0 | Invoice text extraction with manual fallback |
| ZXing Browser | 0.2.1 | Camera QR decoding with manual token fallback |
| PHPUnit | 12.5.42 | Focused assistant/public tests pass; see `TEST_REPORT.md` for the latest full-suite result |
| Vite + Tailwind | via npm | Public site frontend |

All listed packages were compatibility-checked before installation. QR camera access requires browser permission and a secure context; localhost is allowed.

## Local Setup Steps

1. Create the database (already created on this machine):
   ```sql
   CREATE DATABASE IF NOT EXISTS logistics_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Copy `.env.example` to `.env`; set `APP_KEY` (`php artisan key:generate`), MySQL credentials, `ADMIN_PASSWORD`, and any available service credentials. Contact-form submissions are sent to `CONTACT_TO_EMAIL` (defaults to `wkwk00466@gmail.com`); configure the Gmail SMTP sender and app password for delivery. The example defaults to MySQL `logistics_platform`, Arabic, and SAR.
3. Install dependencies: `composer install` and `npm install` (`npm run build` for assets).
4. Migrate: `php artisan migrate` (never `migrate:fresh` without owner approval).
5. Set a strong private `ADMIN_PASSWORD`, then run `php artisan db:seed`. Admin is created only by `AdminSeeder`; do not share the password or commit `.env`.
6. Run `php artisan serve --host=127.0.0.1 --port=8000` or serve through WAMP (`http://localhost/LogisticsPlatform/public`).
7. Tests: `php artisan test`.

**Note:** `phpunit.xml` pins `APP_URL=http://localhost` (without subdirectory) so Laravel's HTTP test client doesn't prepend the WAMP subdirectory path.

## External Credentials Required (documented, not invented)

| Service | Status | Where | How to provide |
|---|---|---|---|
| Stripe test keys | **Provided in `.env`** (pk_test_…/sk_test_…) | `STRIPE_KEY`, `STRIPE_SECRET` | Test mode keys only |
| Gmail SMTP | **Provided in `.env`** (app password) | `MAIL_USERNAME`, `MAIL_PASSWORD`, `CONTACT_TO_EMAIL` | Google account → 2FA → App Password; smtp.gmail.com:587 TLS. Contact-form mail is sent to `CONTACT_TO_EMAIL`. Never commit credentials |
| Google Maps API key | Not configured | `GOOGLE_MAPS_API_KEY=` | Configure a restricted Maps Embed API key; address-search links remain available without it |
| Gemini API | Default provider; API key required for live replies | `AI_PROVIDER=gemini`, `GEMINI_API_KEY`, `GEMINI_MODEL` | Set a valid key in server-side `.env`; default model is `gemini-2.5-flash` |
| OpenAI API | Supported alternative; current account has no API credits | `AI_PROVIDER=openai`, `OPENAI_API_KEY`, `OPENAI_MODEL` | Add API credits to enable live replies; default model is `gpt-4o-mini` |

Never expose provider keys in browser code or commit them. After changing provider configuration, run `php artisan config:clear` and restart WAMP so Apache loads the current PHP CA settings.

Stripe Checkout explicitly rejects non-test secret keys. The customer uses the payment-result page to confirm or cancel: confirmation retrieves the Checkout Session from Stripe on the server, while cancellation expires an open session through Stripe. Neither the browser return URL nor the submitted button is trusted as proof of payment. Automated tests use fake Checkout Session API responses.

The shared AI assistant supports Gemini by default and OpenAI as an alternative through Laravel's HTTP client, with provider keys kept server-side. Set `GEMINI_API_KEY` in `.env` for live Gemini replies; `AI_PROVIDER` can be set to `openai` to select OpenAI instead. After changing provider configuration, run `php artisan config:clear` and restart the web server if configuration remains cached. The shared chat page is `/assistant` (`assistant.show`); the Contact page links to it beside the support email. The same assistant is also available from the public site and the existing Filament panel. Shipment and financial context is fetched only after checking the signed-in user's existing role, approval state, and permissions; guests receive general FAQ help only. The FAQ page content remains the assistant's reference knowledge. Automated assistant tests use mocked provider responses; a valid provider API key is needed for live replies.

## Verification

- Latest `php artisan test`: 112 passed, 7 failed (see `TEST_REPORT.md`; the failure traces include location-picker UI assertions).
- `npm run build`: passes; Vite reports an optional `fontaine` optimized-font-fallback warning only.
- `php artisan view:cache`: passes.
- MySQL migration ledger: all 14 migrations applied to local `logistics_platform`.
- Browser QA: desktop and 375/390px public layouts, Arabic RTL and English LTR, registration role fields, tracking, review/profile pages; no horizontal overflow.

Remaining external/manual checks: real Gmail SMTP delivery, live Stripe Checkout Session confirmation/cancellation, Google Maps embed key, and physical-device camera permission/decoding.

## Phase Status

See `TASKS.md` for progress and `TEST_REPORT.md` for test results.
UI conventions are documented in `docs/DESIGN_SYSTEM.md`.
