# توصيلي (Tawsili) — Delivery Operations & Logistics Management Platform

A Laravel 13 logistics platform with verified customer, operations, payment, delivery, finance, and review workflows:

1. **Public website** — customer registration, shipment creation, tracking, Stripe payments, reviews (Arabic RTL primary / English LTR).
2. **ONE unified internal dashboard** (Filament 5) — for Admin, Drivers, and Employees with role/permission-driven navigation and server-side authorization.

The verified implementation includes registration/approval, customer shipment creation and tracking, private PDF invoice handling and text extraction, test-mode Stripe Checkout with explicit server-verified payment confirmation/cancellation, QR labels and pickup scans, delivery OTP, Driver wallets, finance tools, review moderation, and database/email notifications. External SMTP delivery, live Stripe Checkout Session confirmation/cancellation, Google Maps embeds, and physical camera use still need environment/device verification; see `TEST_REPORT.md`.

## Documentation

| File | Purpose |
|---|---|
| `AGENTS.md` | Permanent agent/development rules |
| `PROJECT_SPEC.md` | Functional requirements (source of truth) |
| `MASTER_EXECUTION_PROMPT.md` | Implementation workflow and phases |
| `TASKS.md` | Phase-by-phase progress |
| `TEST_REPORT.md` | Testing status and known issues |
| `docs/SETUP.md` | Environment, packages, setup, credentials status |
| `docs/DESIGN_SYSTEM.md` | UI/UX design tokens and conventions (ui-ux-pro-max) |

## Quick Start

See `docs/SETUP.md` — summary:

```bash
composer install
npm install && npm run build
php artisan key:generate      # if .env has no APP_KEY
php artisan migrate           # additive migrations; never use migrate:fresh without approval
php artisan db:seed           # set a private ADMIN_PASSWORD first; Admin comes from the seeder
php artisan test
```

Run the local site with `php artisan serve --host=127.0.0.1 --port=8000`; the development URL is `http://127.0.0.1:8000`.

Development database: MySQL `logistics_platform` (see `.env`).
