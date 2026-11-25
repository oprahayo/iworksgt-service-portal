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
- Configure verified email delivery. Current code sends a private authenticated PDF download link on dashboard/download; automatic PDF attachment and durable retry/outbox are still required.
- Implement safe recovery of abandoned/failed Paystack initialization without creating duplicate paid attempts. Current pending record prevents double payment but needs operator reconciliation if checkout fails or is abandoned.
- Add navigation links on public site and admin dashboard; current branch provides direct routes.
- Confirm full DB/integration tests, role isolation, PDF visual fit for long names, email delivery, and Paystack test-mode transactions including wrong amount/currency/signature, replay, concurrent requests, revoked cert and cross-student download.
- Use private staging/test mode. Do not charge live students until release gates pass.

## Routes (not deployed)
- /student/register
- /student
- /academy.php?action=admin
- /verify

Admin first approves the three students after they register and verify email, assigning course and actual duration. No student details have been fabricated.
