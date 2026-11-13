# AGENT RULES
# Logistics Platform

These rules apply to every development task in this project.

## 1. Source of Truth

- PROJECT_SPEC.md contains the functional requirements.
- MASTER_EXECUTION_PROMPT.md contains the implementation workflow.
- TASKS.md tracks project progress.
- TEST_REPORT.md tracks testing and known issues.
- If requirements conflict, follow the latest explicit project decisions and document the resolution.

## 2. Project Rules

- Laravel 13 is required.
- Use PHP compatible with Laravel 13.
- Use MySQL.
- Use compatible stable dependencies.
- Verify package compatibility before installation.
- Follow Laravel best practices.
- Keep controllers focused.
- Use Form Requests for complex validation.
- Use service classes for reusable business logic when appropriate.
- Use policies and middleware for authorization.
- Use database transactions for critical multi-step operations.

## 3. Dashboard Rules

There must be ONE internal Filament panel.

Roles:
- Admin.
- Driver.
- Employee.

Customers use the public website only.

Never create separate Filament panels or separate internal dashboards for these roles.

The same dashboard must display role-specific navigation and functionality.

Every protected action must have server-side authorization.

## 4. User and Permission Rules

- Admin accounts are created through Seeder only.
- Admin must not appear in public registration.
- Customer registration must not require Admin approval.
- Driver registration requires Admin approval.
- Employee registration requires Admin approval.
- Pending and rejected Drivers and Employees cannot enter internal operations.
- Employees receive permissions from Admin.
- Drivers can access only their assigned shipments unless explicitly authorized otherwise.
- Customers can access only their own data and shipments.

Never trust a submitted user ID or role from the browser without server-side verification.

## 5. Financial Rules

- Default Driver commission: 30%.
- Default Company share: 70%.
- Percentages must be configurable.
- Calculate commissions only after successful delivery.
- Calculate each shipment once.
- Prevent duplicate commission records.
- Use decimal database columns.
- Keep the financial module simple.
- Never calculate earnings for cancelled or undelivered shipments.

## 6. Reviews Rules

- Ratings are integers from 1 to 5.
- Only the shipment owner can submit a review.
- Only delivered shipments can be reviewed.
- One review per shipment.
- Publicly display only approved reviews.
- Customers cannot moderate reviews.
- Admin controls review visibility.

## 7. Payment and Security Rules

- Use Stripe Checkout.
- Use test mode during development.
- Confirm payment by retrieving the Checkout Session directly from Stripe on the server.
- Validate the session's paid status, amount, currency, customer, shipment, and payment references before changing local payment status.
- Cancel an open Checkout Session through Stripe; do not treat a browser return or button click alone as proof of payment or cancellation.
- Do not use Stripe webhooks for this project.
- Prevent duplicate payment confirmation and shipment-state changes.
- Never store card details.
- Never expose API secrets.
- Keep secrets in .env.
- Validate uploads and restrict file access.
- Use rate limits for sensitive operations.

## 8. UI/UX Rules

- Use the installed ui-ux-pro-max skill.
- Use the agreed corporate color palette.
- Support Arabic RTL and English LTR.
- Keep the public website and internal dashboard visually consistent but functionally separate.
- Build responsive pages.
- Include loading, empty, success, validation, and error states.
- Do not leave decorative buttons without working actions.
- Do not use fake statistics as real production data.

## 9. Code Quality

- Do not rewrite working code without a reason.
- Make small, focused changes.
- Avoid unnecessary packages.
- Avoid duplicated business logic.
- Use clear naming.
- Keep database relationships consistent.
- Add tests for important business rules.
- Do not hide errors by disabling validation or tests.

## 10. Database Protection

Never run these operations without explicit user approval:
- migrate:fresh
- db:wipe
- Database drop commands.
- Truncate commands.
- Bulk deletion of user data.

Before running migrations, inspect the database connection and confirm the intended development database.

Never overwrite unrelated project files.

## 11. Work Continuity

At each milestone:
- Update TASKS.md.
- Update TEST_REPORT.md.
- Record completed work.
- Record unresolved issues.
- Record the exact next task.

If a session stops, read the project documentation and continue from the latest verified checkpoint.

Do not claim that a task is complete unless its implementation has been checked.

## 12. Communication

Do not interrupt the user for ordinary implementation decisions.

Ask only when:
- An external credential is required.
- A destructive action is necessary.
- A genuinely important requirement is ambiguous.

When blocked by credentials, document the missing values and continue with unrelated work.