# PROJECT SPECIFICATION
# Delivery Operations & Logistics Management Platform

## 1. Project Overview

Build a complete professional Delivery Operations & Logistics Management Platform using Laravel 13.

This is a functional logistics system, not a static website or dashboard-only project.

The platform consists of:
1. A public website for customers.
2. ONE unified internal dashboard for Admin, Drivers, and Employees.

The customer-facing website and the internal dashboard are separate experiences.

## 2. Technology Stack

- Laravel 13.
- PHP version compatible with Laravel 13.
- MySQL.
- Blade templates.
- Bootstrap or Tailwind CSS, selected consistently.
- JavaScript.
- Filament: use the latest stable version compatible with the selected Laravel version.
- Spatie Laravel Permission.
- Laravel Notifications.
- Laravel Queues for background tasks when needed.
- Laravel Broadcasting for real-time features where practical.
- Stripe Checkout.
- Smalot/PdfParser for PDF text extraction.
- QR code generation and scanning.
- Google Maps API.
- Gmail SMTP.
- PDF generation for invoices and shipping labels.

Before installing packages, verify compatibility with Laravel 13 and the installed PHP version. Never force an incompatible package version.

## 3. User Types and Access Control

There are four roles:

### Admin
- Created only through database Seeder.
- Must never appear in public registration.
- Has full access to the internal dashboard.
- Manages users, drivers, employees, shipments, payments, settings, reviews, and reports.

### Driver
- Registers through the public website.
- Registration requires Admin approval.
- Cannot access internal operations before approval.
- After approval, accesses the SAME unified internal dashboard.
- Can view assigned shipments and perform permitted delivery operations.
- Cannot access Admin-only or Employee-only functions unless explicitly permitted.

### Employee
- Registers through the public website.
- Registration requires Admin approval.
- After approval, accesses the SAME unified internal dashboard.
- Admin assigns permissions to each employee.
- Employee navigation and available actions depend on assigned permissions.

### Customer
- Registers through the public website.
- Uses public customer pages and customer account pages only.
- MUST NEVER access Filament or the internal dashboard.
- Can create shipments, track shipments, pay, view shipment history, and submit reviews for eligible shipments.

## 4. Registration and Approval

Public registration must provide a role selection dropdown with exactly these options:

- Customer
- Driver
- Employee

Admin is excluded from public registration.

Customer:
- Can use the account after successful registration.
- Can create and manage their own shipments.

Driver and Employee:
- Register with the required profile information.
- Their accounts start with pending approval status.
- Admin reviews and approves or rejects their accounts.
- Pending and rejected users cannot access internal operations.
- Approval status must be checked using secure middleware/policies.

Admin accounts are created through Seeder only.

## 5. Unified Internal Dashboard

Build ONE Filament internal panel.

Do not create separate dashboards or panels for Admin, Driver, and Employee.

All three roles use the same dashboard URL and panel.

The dashboard must dynamically show navigation items, pages, widgets, data, and actions according to the authenticated user's role and permissions.

Admin:
- Full management access.

Driver:
- Assigned shipments.
- Shipment details.
- Pickup and delivery operations.
- QR scanning.
- OTP confirmation.
- Wallet and commission information.
- Relevant notifications.

Employee:
- Only the modules and actions assigned by Admin.

Customer:
- Must not enter this dashboard under any circumstances.

Use Spatie permissions, middleware, policies, and server-side authorization. Hiding navigation items alone is not security.

## 6. Public Website

Build a complete modern responsive public website.

Required pages:

- Homepage.
- About Us.
- Services.
- Contact Us.
- Shipment Tracking.
- Customer Registration.
- Login.
- Customer Dashboard / Account.
- Create Shipment.
- Shipment Details.
- Shipment History.
- Payment Result.
- Customer Reviews and Ratings.
- Profile Settings.
- FAQ / Help.

The customer interface must be independent from the internal Filament dashboard.

### Homepage

Include:
- Professional hero section.
- Shipment tracking form.
- Services overview.
- How it works.
- Why choose us.
- Statistics.
- Customer testimonials and ratings.
- Contact section.
- Footer.

### Design Direction

Use the following corporate identity:

- Primary Red: #D71920
- Dark Red: #A80F18
- White: #FFFFFF
- Background: #F5F6F8
- Dark Text: #20242B
- Secondary Text: #737B87
- Border: #E5E7EB

Design must be modern, clean, professional, responsive, and suitable for a logistics company.

Use the UI UX Pro Max skill for design decisions and implementation.

Reference for general layout inspiration:
https://fleet.aramex.com/en

Use original branding and assets. Do not copy another company's logo or proprietary identity.

Support Arabic as the primary language and English as a secondary language, with correct RTL and LTR layouts.

## 7. Shipment Management

Customers can:
- Create a shipment.
- Enter sender and receiver information.
- Enter receiver phone number.
- Enter delivery address.
- Add shipment description and relevant details.
- Upload an invoice PDF when required.
- View shipment status and history.
- Track shipments using a unique tracking number.

Admin and authorized Employees can:
- View all shipments.
- Search and filter shipments.
- Update shipment details.
- Assign drivers.
- Change permitted shipment statuses.
- View shipment documents.
- Manage shipment operations.

Drivers can:
- View only shipments assigned to them.
- View pickup and delivery information.
- Update permitted statuses.
- Confirm pickup and delivery.

Suggested shipment statuses:
- Pending.
- Awaiting Payment.
- Paid.
- Assigned.
- Picked Up.
- In Transit.
- Out for Delivery.
- Delivered.
- Cancelled.
- Failed.

Define valid status transitions and prevent unauthorized changes.

## 8. PDF Invoice Processing

- Allow customers to upload PDF invoices.
- Validate file type and size.
- Store files securely.
- Use Smalot/PdfParser to extract available text.
- Display extracted information for review.
- Do not assume every PDF has the same format.
- If extraction fails, allow manual data entry.
- Never execute uploaded files.
- Protect uploaded documents from public unauthorized access.

## 9. Payments with Stripe

Use official Stripe Checkout hosted payment pages.

- Customer selects a shipment and proceeds to payment.
- Create a Stripe Checkout Session on the server.
- Store the payment reference.
- Redirect the customer to Stripe Checkout.
- Return to a payment-result page with explicit Confirm Payment and Cancel Payment actions.
- On confirmation, retrieve the Checkout Session directly from Stripe on the server and verify its paid status, amount, currency, customer, shipment, and payment references before updating local records.
- On cancellation, expire the open Checkout Session through Stripe and update local payment status only after Stripe confirms it is expired.
- Do not use webhooks for payment confirmation.
- Never trust the browser return URL or a submitted button action alone as proof of payment.
- Make confirmation and cancellation safe to retry without duplicate payment or shipment-state updates.
- Use Stripe test mode during development.
- Never store card details.
- Never trust the browser success URL as proof of payment.

## 10. QR Code and Shipping Label

- Generate exactly ONE unique QR code per shipment.
- QR code must identify the shipment securely.
- Generate a professional shipping label PDF.
- Include tracking number, sender, receiver, destination, and QR code.
- Allow authorized staff and assigned drivers to scan the QR code.
- Validate the shipment and current status before processing any scan.
- Prevent duplicate pickup or delivery operations.

## 11. Driver Operations

Drivers can:
- View their assigned shipments.
- View shipment details and delivery address.
- Confirm pickup using QR scanning.
- Update permitted delivery statuses.
- Confirm successful delivery using OTP.
- Receive shipment assignment notifications.
- View delivery history.

Drivers must not access shipments assigned to other drivers unless explicitly authorized.

## 12. OTP Delivery Confirmation

- Generate a secure, time-limited OTP.
- Send it to the authorized recipient through the configured notification channel.
- Verify OTP on the server.
- Limit verification attempts.
- Expire OTP after the configured period.
- Prevent reuse after successful verification.
- Record delivery confirmation time and related shipment event.

## 13. Google Maps

- Integrate Google Maps for shipment addresses and delivery locations.
- Support address selection and location display.
- Store latitude and longitude when available.
- Show the assigned driver relevant destination information.
- Keep API keys in environment configuration.
- If API credentials are unavailable, build the integration structure and document setup requirements.

## 14. Simplified Financial System

Build a basic financial system. Do not implement complex accounting.

Default distribution:
- Driver commission: 30%.
- Company share: 70%.

Admin can change the percentages through settings.

Example:
Delivery fee = $10.

- Driver commission = $3.
- Company profit = $7.

Rules:
- Calculate commission only after successful delivery.
- Calculate each shipment only once.
- Cancelled and undelivered shipments do not earn commission.
- Store delivery fee, driver commission, and company share.
- Use accurate decimal database columns.
- Prevent duplicate commission records.

Driver wallet:
- Total earned commissions.
- Paid commissions.
- Unpaid commissions.
- Simple transaction history.

Admin financial overview:
- Total delivery revenue.
- Total driver commissions.
- Total company profit.
- Total paid commissions.
- Total unpaid commissions.

Admin can mark driver commissions as paid and record payment date and notes.

Allow filtering by date and driver.

Do not implement:
- Double-entry accounting.
- Complex tax accounting.
- Payroll.
- Bank reconciliation.
- Complex accounting ledgers.

## 15. Customer Reviews and Ratings

This feature is REQUIRED.

Customers can:
- Rate a successfully delivered shipment from 1 to 5 stars.
- Write an optional review comment.
- View their submitted reviews.

Rules:
- Only the customer who owns the shipment can review it.
- Shipment must have Delivered status.
- One review per shipment.
- Prevent duplicate reviews.
- Validate rating between 1 and 5.

Public website:
- Display average customer rating.
- Display total number of reviews.
- Display recent customer reviews in an attractive testimonials section on the homepage.
- Provide a clean reviews page or section.
- Use pagination or a limited recent-review display.
- Display only approved and visible reviews.

Admin:
- View all reviews.
- Approve, hide, or delete inappropriate reviews.
- Filter reviews.
- Manage review visibility.

Review submission belongs to the public customer website, not the internal dashboard.

## 16. Notifications and Email

Use Laravel Notifications.

Required notifications:
- Registration received.
- Driver/Employee approval or rejection.
- Shipment created.
- Payment confirmed.
- Driver assigned.
- Shipment status changed.
- Pickup confirmation.
- Delivery confirmation.
- OTP delivery message.
- Other relevant operational events.

Configure Gmail SMTP through environment variables.

Required environment variables:
- MAIL_MAILER
- MAIL_HOST
- MAIL_PORT
- MAIL_USERNAME
- MAIL_PASSWORD
- MAIL_ENCRYPTION
- MAIL_FROM_ADDRESS
- MAIL_FROM_NAME

Use smtp.gmail.com, port 587, and TLS by default.

Never invent Gmail credentials. Document how to use a Google App Password.

## 17. Chatbot

Provide a simple customer help chatbot interface.

- Answer common questions using predefined FAQ content.
- Help customers understand shipment tracking and services.
- Provide a contact/support fallback.
- Do not pretend to access shipment information unless securely authenticated and authorized.
- Do not require paid AI services for the basic implementation.

## 18. Security

- Authentication and authorization.
- Role-based permissions.
- Policies and middleware.
- CSRF protection.
- Server-side validation.
- Secure file uploads.
- Rate limiting for login, OTP, and tracking where appropriate.
- Secure password hashing.
- Protect private shipment data.
- Verify payment and cancellation status directly with Stripe's Checkout Session API.
- Prevent IDOR and unauthorized record access.
- Keep secrets in .env.
- Never expose private credentials in frontend code or Git.

## 19. Testing

Test:
- Registration for all public roles.
- Admin Seeder account.
- Driver and Employee approval.
- Customer dashboard isolation.
- Unified dashboard access.
- Role and permission restrictions.
- Shipment creation and ownership.
- Driver assignment.
- Shipment status transitions.
- PDF upload and extraction fallback.
- Direct Stripe payment confirmation, session cancellation, and duplicate prevention.
- QR uniqueness and scan validation.
- OTP expiration and attempt limits.
- Financial calculations and duplicate prevention.
- Driver wallet.
- Customer review ownership and one-review rule.
- Review display and moderation.
- Notifications.
- Arabic RTL and English LTR.
- Responsive layouts.

## 20. Completion Requirements

The system must be functional, tested, documented, and runnable locally.

Do not consider a feature complete merely because its page or button exists.

Every major action must be connected to backend logic, validation, authorization, and database persistence.

Document any feature that requires external credentials or services before it can be fully tested.