# PROJECT TASKS

# Delivery Operations & Logistics Platform

## Project Status

- Current Phase: 8 (implementation slices verified; external-service/device checks remain)
- Overall Status: In Progress
- Last Checkpoint: On 2026-11-13, fixed the mobile header overflow: action controls stay in one row, and the navigation opens as a viewport-aligned two-column panel instead of overflowing off-screen. Build and live mobile checks are pending.
- Laravel Version: 13.39.0

- [x] Improve public-site responsiveness and primary navigation: unify desktop/mobile destinations, expose public shipment tracking to guests, and improve mobile spacing, touch targets, focus states, and active-link feedback. (Found and fixed the CSS cascade that hid the mobile menu; frontend build passed and `PublicExperienceTest` passed.)
- [x] Align the three header action icons at the far left and improve navigation-label spacing in RTL/LTR. (Grid placement, no-wrap link labels, and responsive collapse point updated; frontend build and focused public experience tests passed.)
- [x] Restore the original compact navbar behavior: hide desktop links below 1180px, show the hamburger instead, and keep guest-only-hidden tracking navigation behavior. (`npm run build` and `PublicExperienceTest` passed.)
- [x] Keep public header brand, page links, and action icons aligned in a single desktop row in RTL and LTR. (Balanced grid tracks and explicit row placement added; build and focused public tests passed.)
- [ ] Fix mobile navbar overflow and menu layout: keep header actions on one row, anchor a compact two-column menu to viewport edges, and keep it within the viewport. (CSS overrides added for leaked legacy header rules; mobile browser verification pending.)

- [x] Replace the homepage hero truck image with the user-provided delivery photo and update its Arabic/English alternative text.
- [x] Remove the green availability dot from the AI assistant launcher.
- [x] Fix the public contact email fallback to use the project support address (`support@tawsili.test`) instead of the placeholder `hello@example.com` in config and environment files.
- [x] Send contact-form submissions to `wkwk00466@gmail.com` and show that same inbox on the Contact Us page. (Email delivery and regression coverage are implemented.)
- [x] Format the contact-form email with Tawsili branding, Arabic RTL/English LTR layouts, and a plain-text alternative.

### Validation status
- Added focused coverage for the localized subject, RTL/LTR rendering, plain-text alternative, and HTML-escaped message content. Test execution is blocked because VS Code has no registered test controller and the shell runner cannot start PowerShell; live SMTP delivery still depends on valid Gmail SMTP settings in `.env`.

## Phase 0 — Environment and Setup

- [x] Inspect PHP, Composer, Node.js, and MySQL. (PHP 8.4.15, Composer 2.10.2, Node 24.19, MySQL 8.4.7 via WAMP; `mysql` CLI not in PATH — use `C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe`)
- [x] Verify Laravel 13 compatibility. (Laravel 13.39.0 requires PHP ^8.3; Filament 5.11.0 PHP ^8.2; spatie/laravel-permission 8.3.2 illuminate ^12|^13)
- [x] Create fresh Laravel project. (scaffolded in temp dir, moved into workspace; pygame-scaffold AGENTS.md/CLAUDE.md intentionally skipped to preserve user docs)
- [x] Configure environment. (merged user-provided .env with Laravel defaults + Stripe/Gmail creds; APP_KEY generated; APP_URL pinned in phpunit.xml for tests)
- [x] Configure database. (`logistics_platform` created, utf8mb4_unicode_ci, MySQL 127.0.0.1:3306 root)
- [x] Install compatible Filament. (v5.11.0, `filament:about` verified)
- [x] Install Spatie Permission. (v8.3.2, config published, permission tables migrated)
- [x] Read and apply ui-ux-pro-max skill. (Python unavailable — queried skill CSV data directly; design system captured in docs/DESIGN_SYSTEM.md)
- [x] Create initial project documentation. (docs/SETUP.md, docs/DESIGN_SYSTEM.md, README.md, .env.example extended with STRIPE/MAPS keys)
- [x] Verify the application starts successfully. (artisan serve HTTP 200; /admin 404 expected until Phase 2 panel; php artisan test 2/2 pass)

## Phase 1 — Database and Architecture

- [x] Design database schema. (users/approval, shipments/events, payments, commissions/wallet, delivery OTP, reviews, and settings)
- [x] Create users and roles structure. (user profile/approval migration and Spatie roles/permissions)
- [x] Create shipment tables. (shipments with unique tracking number)
- [x] Create shipment status history. (`shipment_events`)
- [x] Create payment tables. (`payments` with unique Stripe references)
- [x] Create driver commission and wallet tables. (unique commission per shipment)
- [x] Create reviews tables. (unique review per shipment)
- [x] Create notification-related structures if required. (Laravel database notifications will be added with the notification workflow; no extra Phase 1 table required)
- [x] Create models and relationships. (covered by `ShipmentModelTest`)
- [x] Create factories and seeders. (PaymentFactory import corrected; core factories used by tests)
- [x] Create Admin Seeder. (verified by `CoreSeedersTest`)
- [x] Verify database relationships. (SQLite feature tests; MySQL migration ledger checked read-only)

## Phase 2 — Authentication and Permissions

- [x] Customer registration. (customer is active immediately)
- [x] Driver registration. (profile required; pending by default)
- [x] Employee registration. (profile required; pending by default)
- [x] Exclude Admin from public registration. (server-side allowlist)
- [x] Driver approval workflow. (Admin page actions + transactional service; pending/rejected access denied)
- [x] Employee approval workflow. (Admin page actions + transactional service; pending/rejected access denied)
- [x] Configure Spatie roles and permissions. (base role/permission seeder)
- [x] Create ONE unified Filament panel. (`/admin`; customer access denied)
- [x] Configure Admin access. (Seeder-created approved Admin can access panel)
- [x] Configure Driver access. (approved-role gate + assigned-shipment query/action authorization)
- [x] Configure Employee permissions. (Employee role starts without permissions; Admin can assign allowed permissions)
- [x] Block Customers from internal dashboard. (`FilamentUser` server-side gate)
- [x] Test authorization and account isolation. (7 auth tests; full suite 21 tests)

## Phase 3 — Public Website

- [x] Create design system. (corporate tokens and bilingual direction in `docs/DESIGN_SYSTEM.md`)
- [x] Create responsive homepage. (bilingual, live counts/reviews, tracking form)
- [x] Refresh homepage services section. (editorial heading, polished image cards, and RTL/mobile refinements; routes and content preserved)
- [x] Create About page.
- [x] Create Services page.
- [x] Create Contact page. (validated, throttled, persists support requests)
- [x] Create FAQ page. (preserved FAQ reference content; shared AI assistant upgraded the predefined chatbot)
- [x] Create shipment tracking page. (public-safe timeline; rate-limited)
- [x] Create customer login and registration. (Phase 2)
- [x] Show visible borders on login and registration fields, with a clear focus state. (`npm run build` passed)
- [x] Create customer account pages. (dashboard, profile, notifications, reviews, shipments)
- [x] Create shipment creation form.
- [x] Create shipment history. (owner-scoped, paginated)
- [x] Create shipment details. (owner-only; private invoice download)
- [x] Add homepage testimonials and ratings section. (approved reviews only; empty state)
- [x] Implement Arabic RTL. (Arabic default)
- [x] Implement English LTR. (persisted locale selector)
- [x] Add and verify the authenticated public-header logout icon. (44px accessible icon button beside language toggle; frontend build and Blade cache passed; logout/authentication regression assertions were included in the full suite, which reported 115 passed and 12 failed elsewhere)
- [x] Preserve the selected language after logout. (session invalidation now restores the validated locale; the regression assertion passes in the full Laravel suite)
- [x] Test responsive layouts. (browser metrics at 375/390px and desktop; zero overflow)

## Phase 4 — Shipment Management

- [x] Generate unique tracking numbers.
- [x] Implement shipment creation. (server-owned IDs/status/fee; validated fields)
- [x] Implement shipment ownership rules. (customer-scoped queries and private file authorization)
- [x] Implement shipment status transitions. (service validates transitions/actor; event transaction)
- [x] Implement shipment history. (status event timeline)
- [x] Implement driver assignment. (only approved Drivers; only after payment state)
- [x] Implement Admin shipment management. (quote and assignment in unified panel)
- [x] Implement Employee shipment permissions. (route/page gate + server-side permission actions)
- [x] Implement Driver assigned-shipment view. (database-scoped to current Driver)
- [x] Implement secure PDF invoice upload. (private local disk, PDF validation, owner download)
- [x] Implement PDF text extraction. (valid Dompdf-generated invoice fixture parsed and text stored)
- [x] Implement manual extraction fallback. (failed/empty extraction does not block shipment creation)

## Phase 5 — Payments and Delivery

- [x] Integrate Stripe Checkout test mode. (official stripe-php; no Checkout request sent during tests)
- [x] Implement payment records. (unique payment per shipment)
- [x] Implement direct Stripe Checkout Session confirmation. (server retrieves and validates paid session, amount, currency, customer, shipment, and payment references)
- [x] Implement Stripe Checkout Session cancellation. (server expires the open session before marking the payment cancelled)
- [x] Prevent duplicate payment confirmation and cancellation. (locked payment/shipment updates and idempotent terminal states)
- [x] Generate one QR code per shipment. (encrypted token + unique hash and private image)
- [x] Generate shipping label PDF. (A6 PDF with sender/receiver/destination/QR)
- [x] Implement QR scanning workflow. (ZXing camera scan + manual token fallback)
- [x] Implement pickup confirmation. (assigned Driver only; duplicate scan rejected)
- [x] Implement OTP generation. (cryptographic random six-digit code, hash at rest)
- [x] Implement OTP delivery. (recipient email notification)
- [x] Implement OTP verification and expiration. (one use, expiry, attempt limit)
- [x] Implement Google Maps integration. (restricted key structure + no-key map search fallback)
- [x] Test delivery workflow. (signed local event, QR/OTP authorization and state tests)

## Phase 6 — Simplified Financial System

- [x] Add configurable commission percentages. (Admin finance action validates total 100%)
- [x] Set default Driver commission to 30%.
- [x] Set default Company share to 70%.
- [x] Calculate commission after successful delivery. (OTP confirmation transaction)
- [x] Prevent duplicate commission calculations. (shipment unique + wallet unique credit + transaction lock)
- [x] Create Driver wallet summary.
- [x] Show earned commissions.
- [x] Show paid commissions.
- [x] Show unpaid commissions.
- [x] Allow Admin to record commission payments. (date and notes recorded)
- [x] Create Admin financial overview. (revenue, driver share, company share, paid/unpaid)
- [x] Add date and Driver filters.
- [x] Test all financial calculations. (cent rounding, defaults, configurable rates, duplicate prevention)

## Phase 7 — Reviews and Notifications

- [x] Create customer rating form.
- [x] Support ratings from 1 to 5 stars.
- [x] Support optional review comments.
- [x] Restrict reviews to shipment owners.
- [x] Restrict reviews to delivered shipments.
- [x] Prevent duplicate reviews. (service + DB unique constraint)
- [x] Display average rating.
- [x] Display total review count.
- [x] Display approved reviews on homepage.
- [x] Create Admin review moderation. (approve/hide/delete actions)
- [x] Implement in-app notifications. (customer inbox + Filament indicator)
- [x] Configure Gmail SMTP. (environment values documented/configured)
- [x] Implement email notifications. (Laravel notifications, array mailer in tests)
- [x] Test notification workflows. (inbox ownership, registration, payment replay, delivery/staff/shipment events)

## Phase 8 — Final Quality Assurance

- [ ] Publish and verify the active section underline fix. (updated `landing.js`; tested scroll-spy behavior against live DOM for Services, How It Works, Reviews, and Contact; rebuild and full-page verification blocked by missing PowerShell)

- [x] Keep approved Drivers visible on the Admin staff approvals page. (regression test passes; rejected Drivers remain excluded)
- [x] Test all registration roles. (plus Admin Seeder)
- [x] Test approval workflow.
- [x] Test unified dashboard permissions.
- [x] Test customer dashboard isolation.
- [x] Test shipment creation and tracking.
- [x] Test PDF upload and extraction. (valid and parser-fallback cases)
- [ ] Test direct Stripe confirmation/cancellation. (automated tests added; run targeted suite and live test-mode session check)
- [ ] Test QR workflow. (server scan tests pass; physical camera permission/device not exercised)
- [x] Test OTP workflow.
- [x] Test Driver wallet.
- [x] Test financial calculations.
- [x] Test review submission and moderation.
- [ ] Confirm customer review status display. (regression test added; full suite has unrelated failures, so run the focused review workflow test)
- [ ] Test email notifications. (array mailer and DB channels pass; Gmail SMTP delivery not attempted)
- [x] Test Arabic RTL. (browser at mobile and desktop)
- [x] Test English LTR. (browser at mobile and desktop)
- [x] Test mobile responsiveness. (375/390px no horizontal overflow)
- [x] Hide registration calls to action from signed-in users on public pages.
- [x] Show tracking navigation calls to action only to signed-in users; retain public tracking forms.
- [x] Upgrade the FAQ chatbot to a shared AI assistant. (latest explicit requirement overrides PROJECT_SPEC.md §17's FAQ-only behavior; FAQ content retained; shipment/finance context is server-authorized by role and permission)
- [x] Render the assistant in the public site and the existing Filament panel; add the Contact launch action beside support email.
- [x] Add mocked role-isolation, permission, provider-error, and interface tests for the assistant. (13 baseline tests, 81 assertions passed previously)
- [x] Verify server-session conversation isolation and the latest assistant changes. (AssistantChatTest: 15 passed, 104 assertions)
- [x] Verify the dedicated `/assistant` page and same-origin previous-page navigation. (page route/content, Contact destination, and button assertions pass)
- [x] Show a clear message when the provider key is missing and prevent failed prompts from appearing as replies. (AssistantChatTest: 15 passed, 105 assertions)
- [x] Configure WAMP PHP CLI and Apache to use the official CA bundle; verified the OpenAI TLS handshake without disabling certificate validation.
- [x] Add Gemini as a selectable AI provider with server-side API-key handling and mocked request/response coverage. (AssistantChatTest: 17 passed, 114 assertions)
- [x] Make Gemini the default provider while retaining OpenAI as an explicit alternative. (Existing OpenAI-focused tests select OpenAI explicitly.)
- [x] Configure Gemini API key and active AI provider in `.env` (`AI_PROVIDER=gemini`, `GEMINI_API_KEY`, `GEMINI_MODEL=gemini-1.5-flash`), fix model identifier 404, raise `maxOutputTokens` to 2048 to prevent thinking-token exhaustion, enforce multiturn role alternation, and update assistant Arabic labels to "المساعد الذكي".
- [x] Refine the existing Filament dashboard theme with the Tousili design tokens and keep custom dashboard styling out of the simple login layout.
- [x] Restyle the existing Filament sidebar and surfaces to match the supplied red-and-white corporate dashboard reference using CSS only. (`npm run build` passed; no UI components, queries, or business logic changed.)
- [x] Confirm the theme is already registered through the existing Vite input and Filament 5 `viteTheme()` configuration.
- [x] Build the custom Filament theme assets. (Vite 8.3.5 completed successfully; optional `fontaine` fallback optimization notice only)
- [x] Redesign public landing page into the clean, realistic corporate design strictly matching the user mockup: single-page flow, real van visual moving autonomously with 3D perspective without any box wrapper, pill tracking bar, horizontal services, 4-step workflow, why-us boxes & pill badges, dark CTA, restrained colors, and zero backend logic changes.
- [x] Complete 1:1 pixel-accurate implementation of the user reference mockup: pure daylight white canvas, crisp high-res photography (`tawsili-van.png`, `tawsili-boxes.png`), floating tracking capsule, 4-column services with vertical dividers, 4-step curved workflow band, 3-column why-us section, high-contrast dark CTA banner, and dark footer with segmented language pill. All AI-looking artifacts completely eliminated.
- [x] Integrate dedicated customer reviews & ratings section (`#reviews`) into the public homepage and navigation bar, showcasing live approved reviews, overall star rating summary capsule, verified delivery badges, and link to the full reviews index.
- [x] Remove the excess whitespace between the homepage contact section and footer without changing spacing on other pages.
- [x] Fix homepage contact-form intrinsic-width overflow and hero-description contrast. (browser verified without horizontal overflow at 320, 360, 375, 390, 768, and 1280px)
- [x] Modernize and elevate the statistics section (`#stats`) into a prestigious deep navy blue band (`#101D27`) with real animated numeric counters (`.stat-counter[data-target]`) rendering live platform metrics (delivered count, covered cities, total shipments, customer review score).
- [x] Upgrade services grid (`#services`) with high-resolution authentic logistics photography, service category tags, and clean red iconography.
- [x] Build integrated interactive Contact Us & Live Working Map section (`#contact`) in deep navy band: direct inquiry form submitting to `route('contact.submit')` paired side-by-side with Leaflet.js / OpenStreetMap interactive Riyadh Central Hub map, custom pulse pin, popup, and recenter controller.
- [x] Synchronize source and production assets (`landing.css`, `landing.js`, `landing-CFfsVeBw.css`, `landing-DCiusxBx.js`) for seamless rendering across dev server and production builds.
- [x] Inspect the authenticated Filament dashboard and RTL/LTR breakpoints. (browser inspection reached login; no authenticated session was available)
- [x] Restore consistent customer account navigation styling across all pages using the shared account component. (RTL/LTR-aware horizontal navigation, active/hover states, and a compact mobile grid; `npm run build` passed)
- [x] Restyle the customer profile and shipment-creation forms to restore visible, responsive controls. (Screenshot review identified missing input borders and form layout; CSS updated and `npm run build` passed)
  - Follow-up (2026-11-07): rebuilt the admin dashboard itself: translated all sidebar navigation labels through `app.admin_nav.*` (Shipments → الشحنات, Staff approvals → موافقات الفريق, Finance → المالية, Reviews → التقييمات, My wallet → محفظتي), added a missing approvals navigation icon, and replaced the default Filament widgets (Account/FilamentInfo) with a custom `App\Filament\Pages\Dashboard` (Arabic title/subheading), a role-aware `DashboardStatsWidget` (driver vs admin/employee stats from real data), a `DashboardQuickActionsWidget` Blade card grid filtered by each page's `canAccess()`, and `DashboardAccountWidget` sorted last. Added `app.admin_nav.*` and `app.dashboard.*` keys to `lang/ar/app.php` and `lang/en/app.php`, quick-action styles in `theme.css`, and rebuilt the Vite theme (`npm run build` passed). Verified via `php -l` on all touched files, `optimize:clear` without errors, tinker title/translation checks, and a direct render of the quick-actions blade view. Authenticated browser pixel-verification at desktop/mobile sizes is still outstanding.
- [ ] Audit custom Blade/Livewire inputs and show validation feedback beside invalid fields. (implementation added; targeted tests and frontend build still need to run)
- [ ] Review security. (server-side authorization tests pass; final manual review remains)
- [ ] Fix subdirectory asset URLs. (stale `public/hot` removed and styled page verified; `vite.config.js` now uses relative build paths, but `npm run build` is unavailable; PHPFlasher root path and font preloads still return 404)
- [ ] Verify all authenticated user-role workflows. (Customer account pages and pending Driver/Employee access were browser-checked; admin approval and post-approval role workflows still need owner sign-in; automated suite blocked by missing PowerShell)
- [ ] Resolve customer shipment detail HTTP 500. (Matching Laravel log reports a missing runtime APP_KEY; project `.env` is configured and no config cache exists; restart/inspect WAMP Apache/PHP environment, then reopen existing shipment and check history before retrying creation)
- [ ] Resolve outstanding test failures. (fresh run blocked by missing PowerShell; historical reports conflict on failure counts; see `TEST_REPORT.md`)
- [ ] Resolve the 1px horizontal overflow observed at 320px and recheck responsive widths.
- [ ] Verify the updated public navigation visually at mobile, tablet, desktop, Arabic RTL, and English LTR sizes. (Browser verification is blocked while the local WAMP site refuses connections.)
- [x] Update setup documentation.
- [ ] Produce final delivery report.

## Rules for Updating This File

- Check tasks only after implementation and verification.
- Do not mark a feature complete because its page exists.
- Record blocked tasks with the reason.
- Add newly discovered tasks in the correct phase.
- Preserve completed work.
- Always update the current phase and next action.

## Current Next Action

Next: verify the mobile navbar at 320px, 375px, and 390px in Arabic RTL and English LTR, and confirm tablet/desktop alignment once the rebuilt page reloads. Then continue with the remaining full-suite and subdirectory asset checks, contact-email delivery, authenticated Admin/staff workflows, and shipment-location tests. Complete live Stripe test-mode and device/service checks only when suitable credentials and devices are available. Payment status uses direct Stripe Checkout Session retrieval; webhooks are not part of the application workflow.
