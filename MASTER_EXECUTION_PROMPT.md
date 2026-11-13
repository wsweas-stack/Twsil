# MASTER EXECUTION PROMPT
# Autonomous Logistics Platform Development

## ROLE

Act as a Senior Laravel Architect, Backend Developer, Frontend Developer, Database Engineer, Filament Developer, UI/UX Designer, Security Engineer, and QA Engineer.

Your task is to build the complete project described in PROJECT_SPEC.md.

Read PROJECT_SPEC.md carefully. It is the source of truth for project requirements.

## 1. PROJECT CREATION

- Build a fresh Laravel 13 project.
- Use the currently selected workspace folder.
- Inspect the installed PHP, Composer, Node.js, and database environment.
- Verify all package compatibility before installation.
- Use stable compatible package versions.
- Do not downgrade Laravel to an older major version just to install an incompatible package.
- If a required package is incompatible, find a compatible alternative and document the decision.
- Configure the project for local development.

Do not overwrite or delete unrelated user files.

## 2. MANDATORY DESIGN SKILL

Use the installed `ui-ux-pro-max` skill.

Before implementing interfaces:
- Read the skill instructions.
- Follow its design recommendations.
- Establish a consistent design system.
- Define colors, typography, spacing, layout, buttons, forms, tables, cards, and responsive behavior.
- Avoid generic unfinished dashboard designs.
- Build polished, consistent, professional interfaces.

Use the corporate colors and bilingual RTL/LTR requirements from PROJECT_SPEC.md.

Do not copy another company's logo or proprietary assets.

## 3. ARCHITECTURE RULES

There are only TWO interface experiences:

A. Public website:
- Customer-facing pages.
- Customer account pages.
- Shipment creation, tracking, payment, and reviews.

B. ONE unified internal dashboard:
- Admin.
- Driver.
- Employee.

Never create three separate internal dashboards.

Use one Filament panel and one dashboard architecture with role-based navigation, widgets, pages, resources, and permissions.

Customers must never access the Filament panel.

Use server-side authorization. Never rely only on hiding menu items.

## 4. IMPLEMENTATION WORKFLOW

Work in small, verifiable phases.

For each phase:

1. Read the relevant requirements.
2. Inspect existing project state.
3. Plan only the current phase.
4. Implement the phase.
5. Run relevant tests and checks.
6. Fix errors.
7. Update TASKS.md.
8. Update TEST_REPORT.md.
9. Record important decisions.
10. Proceed to the next phase.

Do not generate the entire application in one giant operation.

Do not stop after creating a plan, database, or basic UI.

## 5. REQUIRED PHASES

### Phase 0: Environment and Project Setup
- Inspect environment.
- Create fresh Laravel 13 project.
- Configure .env.example.
- Configure MySQL.
- Install compatible dependencies.
- Install and verify Filament.
- Install and configure Spatie Permission.
- Read ui-ux-pro-max skill.
- Create initial documentation.

### Phase 1: Database and Core Architecture
- Design migrations.
- Create models and relationships.
- Define enums or constants for roles and statuses.
- Add factories and seeders.
- Create Admin Seeder.
- Add database constraints and indexes.
- Run migrations in the development database only after checking the target connection.

### Phase 2: Authentication and Authorization
- Customer, Driver, and Employee registration.
- Admin excluded from public registration.
- Approval workflow for Driver and Employee.
- Unified internal Filament panel.
- Role and permission configuration.
- Middleware, policies, and access tests.
- Verify customer dashboard isolation.

### Phase 3: Public Website and Customer Experience
- Build homepage.
- Build services, about, contact, FAQ.
- Build tracking page.
- Build login and registration.
- Build customer account pages.
- Build create shipment and shipment history pages.
- Implement Arabic RTL and English LTR.
- Implement responsive design.
- Add public testimonials and ratings section.

### Phase 4: Shipment Management
- Shipment creation and validation.
- Tracking numbers.
- Shipment status history.
- Shipment assignment.
- Admin and Employee shipment operations.
- Driver shipment views.
- PDF invoice upload and extraction.
- Manual fallback when extraction fails.

### Phase 5: Payment, QR, and Delivery
- Stripe Checkout test mode.
- Server-side Stripe Checkout Session retrieval for customer-confirmed payment.
- Stripe Checkout Session expiration for customer cancellation.
- Payment status management with idempotent confirmation and cancellation.
- Unique QR per shipment.
- Shipping label PDF.
- QR scan workflow.
- Driver pickup workflow.
- OTP delivery confirmation.
- Google Maps integration structure.

### Phase 6: Financial System
- Configurable driver commission percentage.
- Company percentage.
- Default 30% driver and 70% company.
- Commission calculated after successful delivery.
- Duplicate calculation prevention.
- Driver wallet.
- Paid and unpaid commissions.
- Admin financial overview.
- Payment recording for driver commissions.

Keep this module simple. Do not build complex accounting.

### Phase 7: Reviews and Notifications
- Customer rating from 1 to 5 stars.
- Optional review comments.
- One review per delivered shipment.
- Ownership authorization.
- Public approved reviews.
- Homepage average rating and total reviews.
- Admin review moderation.
- Email and in-app notifications.
- Gmail SMTP configuration.

### Phase 8: Final UI and Quality Assurance
- Complete missing UI states.
- Responsive testing.
- Arabic and English testing.
- Security review.
- Authorization review.
- Run automated tests.
- Fix bugs.
- Verify all major user journeys.
- Update documentation.

## 6. PERFORMANCE AND CONTEXT MANAGEMENT

To avoid hanging or losing progress:

- Work on one bounded task at a time.
- Avoid generating huge files unnecessarily.
- Avoid repeatedly rewriting working files.
- Prefer small focused changes.
- Use existing Laravel conventions.
- Run targeted tests after each module.
- Run the full test suite at appropriate milestones.
- Do not repeat the same failed command indefinitely.
- If a command fails twice for the same reason, investigate the cause and change approach.
- If a test fails, inspect the error and fix the root cause.
- Avoid unnecessary dependency installations.
- Avoid unnecessary frontend build repetitions.
- Do not run long-running commands without checking their output.
- Save progress before the context becomes too large.

## 7. CHECKPOINT AND RESUME

After each meaningful milestone, update:

- TASKS.md
- TEST_REPORT.md
- AGENTS.md when permanent project rules or discoveries change.

Record:
- Completed work.
- Current phase.
- Current task.
- Files changed.
- Commands executed.
- Tests passed.
- Tests failed.
- Known issues.
- External credentials still required.
- Exact next action.

If the conversation or session stops, read these files and resume from the last verified checkpoint.

Do not restart completed work unnecessarily.

## 8. AUTONOMY

Do not ask the user to approve ordinary development steps.

You may:
- Create files.
- Edit files.
- Install compatible dependencies.
- Create migrations.
- Implement models and controllers.
- Implement interfaces.
- Run local tests.
- Fix errors.
- Update documentation.

Ask for user intervention only when genuinely necessary, such as:
- Missing external credentials.
- An irreversible or destructive operation.
- A decision that cannot safely be inferred.
- A required external account or service action.

If credentials are unavailable, build the integration and provide clear setup instructions. Continue with other tasks.

## 9. SAFETY AND DATA PROTECTION

Never:
- Run migrate:fresh without explicit approval.
- Drop or truncate databases without explicit approval.
- Delete user data.
- Overwrite unrelated files.
- Use real Stripe payment credentials during development.
- Publish or deploy to production without approval.
- Commit secrets.
- Invent API keys or passwords.
- Claim an integration works without testing it.

Before database migrations:
- Inspect the configured database connection.
- Confirm it is the intended development database.
- Preserve existing data.

## 10. QUALITY STANDARD

A feature is not complete just because its interface exists.

A feature is complete only when:
- Its UI exists.
- Backend logic exists.
- Validation exists.
- Authorization exists.
- Data persistence works.
- Relevant tests pass.
- Errors are handled.
- User-facing states are clear.

Do not use fake hardcoded data as a substitute for functional backend features.

## 11. FINAL DELIVERY

At completion, provide:
- Setup instructions.
- Database migration and seeding instructions.
- Admin login setup instructions without exposing secrets.
- Required .env configuration.
- Stripe test configuration.
- Gmail App Password configuration.
- Google Maps API configuration.
- Queue and scheduler instructions if used.
- Test results.
- Known limitations.
- List of completed modules.
- List of remaining tasks, if any.

Start with Phase 0 and continue sequentially.

Do not stop after planning.

Your goal is a working, secure, professional logistics platform that follows PROJECT_SPEC.md.