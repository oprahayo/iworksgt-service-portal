# Academy Sprint 1 (draft — do not deploy until release gates pass)

## Scope
Dedicated student role, registration using existing email OTP, profile, admin-approved existing graduates, certificate preview, NGN 102,000 Paystack checkout (initial amount; includes a processing-cost allowance, not a guarantee of net settlement), verified webhook/callback issuance, PDF download and number-based verification. Course title, colour, duration and student-confirmed name are frozen at application; certificate number is random and unique. Existing service payments continue through the original gateway flow.

The dashboard does not display a certification price. The Get Certificate button opens a name confirmation/preview; exact amount is disclosed before Pay Now. Do not automatically add another processing fee in application code; verify the merchant Paystack fee-bearer configuration and provider checkout before live release.

## Initial course assignments
Orange: Digital Marketing. Purple: WordPress/CMS, basic and advanced frontend. Blue: Backend. Green: Full-stack and hosting. Admin may change the colour for future applications.

## Production prerequisites
- Preserve and stage the four exact approved SVG files from iWorks_Certificate_Templates.zip under storage/academy-templates/ (non-public, no modification). The PHP loader checks their SHA-256 hashes. The public repository deliberately does not contain these signed certificate artworks.
- Install Inkscape at /usr/bin/inkscape, Lato and Liberation Serif, and enable proc_open; confirm on the cPanel PHP environment. Checkout fails closed if PDF cannot render.
- Back up database, inspect live users.role schema, run php scripts/migrate_academy.php --backup-confirmed. Validate old user roles and both site roots. Ensure existing Paystack charge.success webhook reaches the existing handler.
- Configure verified email delivery. PDF attachment delivery and queue retries are implemented; verify mail transport and delivery on staging. Delivery is at-least-once if a process crashes after accepting mail but before recording success.
- Resume Checkout reuses the existing Paystack reference; initialization failures with no checkout URL and expired sessions still need operator reconciliation. Never allocate a new reference without establishing the old one cannot settle.
- Admin sidebar now links to Academy. Student pages use native header/footer and role-specific navigation; public registration link remains to be added.
- Confirm full DB/integration tests, role isolation, PDF visual fit for long names, email delivery, and Paystack test-mode transactions including wrong amount/currency/signature, replay, concurrent requests, revoked cert and cross-student download.
- Use private staging/test mode. Do not charge live students until release gates pass.

## Local rendering evidence
All four exact approved SVG hashes were checked and all four variants rendered to PDF in PHP 8.4/Inkscape 1.4 using a local copy of the rendering logic and sample student data. The purple WordPress result was visually inspected, and its QR decoded to https://portal.iworksgt.com/verify. This is not a deployed repository integration or Paystack test. Course creation and enrollment requests are implemented in the draft; admin approval controls enrollment and certificate eligibility.

## Routes (not deployed)
- /student/register
- /student
- /academy.php?action=admin
- /verify

Admin first approves the three students after they register and verify email, assigning course and actual duration. No student details have been fabricated.

## Release controls added
- Checkout is disabled by default (`academy_checkout_enabled=0`). Only enable after staging, end-to-end Paystack test, mail delivery, private-file denial and backup verification. Existing verified pending transactions can still be confirmed with checkout disabled.
- `scripts/academy_host_check.sh` downloads a pinned source archive to a temporary private home directory and checks the exact SVGs, PHP, fonts, PDF generation and existing database/mail/payment configuration without modifying production. It is a diagnostic, not a deployment command.
- CI covers PHP lint, existing portal regression tests, Academy validation and a simulated payment lifecycle; a separate isolated MySQL job exercises baseline schema, repeated migration, enrollment, ownership, unique graduate certificate constraint and revocation. This does not represent real Paystack or production delivery.
- Retain current `b.sh` for its prior invoice release; do not use it to deploy Academy.
