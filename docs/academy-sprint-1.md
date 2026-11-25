# Academy Sprint 1 (draft — do not deploy until release gates pass)

## Scope
Dedicated student role, registration using existing email OTP, profile, admin-approved existing graduates, certificate preview, NGN 102,000 Paystack checkout (initial amount; includes a processing-cost allowance, not a guarantee of net settlement), verified webhook/callback issuance, PDF download and number-based verification. Course title, colour, duration and student-confirmed name are frozen at application; certificate number is random and unique. Existing service payments continue through the original gateway flow.

The dashboard does not display a certification price. The Get Certificate button opens a name confirmation/preview; exact amount is disclosed before Pay Now. Do not automatically add another processing fee in application code; verify the merchant Paystack fee-bearer configuration and provider checkout before live release.

## Initial course assignments
Orange: Digital Marketing. Purple: WordPress/CMS, basic and advanced frontend. Blue: Backend. Green: Full-stack and hosting. Admin may change the colour for future applications.

## Production prerequisites
- Preserve and stage the four exact approved SVG files from iWorks_Certificate_Templates.zip under storage/academy-templates/ (non-public, no modification). The PHP loader checks their SHA-256 hashes. The public repository deliberately does not contain these signed certificate artworks.
- Install Lato and Liberation Serif in the account font directory, enable proc_open, and use either the private ReportLab renderer in ~/.iworks-academy-renderer or /usr/bin/inkscape. Checkout fails closed if PDF cannot render.
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

## cPanel dependency correction
Host report verified PHP, database access, existing roles, mail configuration, Paystack configuration, all template hashes and installed fonts. Inkscape was not installed. Added a private Python/ReportLab fallback that consumes the same hash-verified SVG, uses explicit Lato Regular/Bold/Black and Liberation Serif Bold files, preserves its background and QR geometry, and embeds vector text. The four locally generated preview PDFs decoded to the original verification URL. Server-side Python availability and end-to-end render remain unverified; no payment activation or deployment has been performed.

## Host verification and beta deployment
On 25 November 2026 the operator supplied terminal output: Python 3.11.18 and ReportLab 4.4.9 installed, all four approved template renders passed, all PHP/database/mail/Paystack configuration checks passed, preflight blocks: 0. The ea-php-cli cache warning did not prevent completion. Fixed the new registration and verification rate-limit scope allowlist, added a login registration link, and moved signed templates outside the HTTP document roots. The pinned application release f9bba44224b29f41e6215e828d5b3d6f2f810e41 passed PHP 8.2/8.3 and isolated MySQL CI (run 45661596925). The new scripts/deploy_academy_beta.sh refuses source drift, verifies pinned CI and local preflight, requires DEPLOY confirmation, creates an encrypted database dump and source backup, applies the additive migration, deploys only the Academy-related source files, and checks guest HTTP routes. It restores modified source files if post-copy checks fail; it does not automatically restore the database or remove additive schema. This beta procedure has not yet been executed on the hosting account; backup integrity is not an isolated restore rehearsal. Checkout must remain off until real OTP, student, test-mode gateway webhook, PDF email delivery, PHP web runtime and rollback rehearsal pass. PR #161 remains draft and unmerged.

## Beta deployment verified by operator — 25 November 2026
Operator terminal reported successful deployment of pinned application f9bba44224b29f41e6215e828d5b3d6f2f810e41 using deployment operator f2a1a11729e0abfb90d286a56434d08e569f0c0e. Existing live Apache config preserved by exact-hash reconciliation. Encrypted database and source backups passed integrity checks (restore rehearsal outstanding); Academy schema ready; all four PDFs rendered; preflight blocks 0; /student/register and /verify HTTP checks passed; unauthenticated admin route denied; private app/academy.php and renderer source denied. Checkout remains disabled. No live payment, OTP registration, authenticated student UI, certificate email delivery or webhook end-to-end test verified yet. Next: controlled real student test registration using an owner-controlled mailbox, admin graduate approval and preview, then isolated Paystack test-mode checkout/webhook without changing existing service gateway configuration. Do not invite paying students before those checks pass.

## Automated post-deployment acceptance
Added scripts/academy_beta_acceptance.php for the deployed beta. Runs actual deployed PHP, rollback-only student/graduate/certificate database checks, four marked PDF renders, guest HTTP authorization and a signed harmless non-charge webhook probe. Optional explicit operator-controlled PDF mail transport test. No live payment, real certificate issuance, persistent fixture account or payment activation. Actual mailbox receipt, browser OTP and Paystack sandbox transaction/provider delivery remain separate gates. Run through cPanel CLI from a private path; no upload of certificate assets needed.

## Production acceptance results — 25 November 2026
Operator ran the pinned acceptance script (527e67ea10c8937cd742e40e1e0bb2cb23652674) on production. Every automated check passed: transactional tables, no fixture triggers, authentication/password hashing, graduate/enrollment data, purple course mapping, invalid preview, payment amount validation (synthetic), owner/revocation restrictions, rollback of all fixture rows, all four deployed PDF renders, registration form CSRF, rejection of unknown certificate number, guest admin/download authorization, invalid webhook signature rejection, and valid signed harmless webhook event acceptance. Mail transport accepted a clearly marked PDF sent to the operator-supplied test alias. Actual mailbox receipt not confirmed; no matching test message was found in the connected Gmail search. No actual payment, valid test certificate or persistent test account was created. Checkout remains disabled. Remaining release gates: real OTP/browser flow, actual Paystack test-mode checkout and provider webhook delivery, issued-certificate PDF mail workflow, backup restoration rehearsal.
