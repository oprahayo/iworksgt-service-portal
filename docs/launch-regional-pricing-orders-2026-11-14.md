# iWORKS GLOBAL — Launch Catalogue, Pricing & Self-Service Order Readiness

**Date:** 14 November 2026  
**Scope:** New iWORKS customer transactions only. Historical jobs, paid/issued invoices, client documents, admin assignments and legacy prices are not rewritten.

## Live code's proposed launch prices

The following are *iWORKS commercial rates*, not official CAC charges, regulatory guarantees or foreign exchange conversions. Regional products have independently set prices. Account country Nigeria → NGN; other listed customer countries → USD. Account billing country is selected/confirmed on registration, then locked from self-service changes; any corrections require support review. Edge geolocation is merely a first-visit display hint and is **not strong KYC**.

| Product / scope | Nigeria | International | Checkout mode |
| --- | ---: | ---: | --- |
| Business Name, one proprietor | ₦49,000 | $95 | Guided CAC intake → invoice |
| Nigerian private Ltd, capital up to ₦1m | ₦60,000 | $175 | Guided CAC intake → invoice |
| Nigerian private Ltd, capital above ₦1m up to ₦5m | ₦150,000 | $325 | Guided CAC intake → invoice |
| NGO/Incorporated Trustees | Indicative ₦180,000 | Indicative $495 | Scoped specialist quote (no instant invoice) |
| Starter website, up to 5 pages | Indicative ₦250,000 | $480 | Quote-first |
| Business CMS website, up to 10 pages | Indicative ₦500,000 | $950 | Quote-first |
| Online shop, up to 30 products | Indicative ₦900,000 | $1,900 | Quote-first |
| Complex portal/marketplace/app | Custom | Custom | Quote-first |
| Graphics/brand design | ₦95,000 | $195 | Fixed server-calculated invoice |
| SEO/digital marketing setup | ₦180,000 | $370 | Fixed server-calculated invoice |
| Domain/managed hosting | ₦55,000 | $115 | Fixed server-calculated invoice |
| Business email/SMTP setup | ₦95,000 | $195 | Fixed server-calculated invoice |
| Professional training | ₦90,000 | $190 | Fixed server-calculated invoice |
| Vehicle/asset tracking and US LLC formations | Scope-dependent | Scope-dependent | Specialist quote, no fixed checkout |
| Trademark filing | Existing configured USD/class rate, NGN conversion for Nigerian customers | Existing USD/class rate | Guided trademark document/class workflow, not a universal flat filing price |

**Recommended commercial approach:** Keep tax/regulated fees itemised; publish exactly what is included before collection; do not introduce unsupported strikethrough discounts. No government filing, regulated approval, banking, SLA or payment provider eligibility is guaranteed. Website scope, hosting, renewal and ongoing maintenance require signed quotes; not automatically included beyond stated package scope. Check VAT/tax treatment with finance.

**Admin control:** Services → Regional launch price book. Separate 24 price fields (12 product groups × NGN/USD), validated and committed transactionally. Altering this price book changes *new* orders only. Legacy CAC base settings remain for historical compatibility and some optional add-on pricing.

## Self-service experience

1. Visitor sees public catalog and topic-specific service detail. The CAC private Ltd listing offers the four pathways (BN, small Ltd, larger Ltd, NGO). Website detail offers Starter, Business, Store and Custom quotes.
2. Visitor chooses package and signs up or signs in. The original service/package intent survives account registration and OTP.
3. Registration preselects the indicated country (when an edge hint exists) but the person must confirm their actual billing country; this is the immutable account country used to enforce currency server-side.
4. Fixed-price services: server derives approved regional rates, calculates tax and creates customer job/invoice. Posted amount, arbitrary currency and pricing fields cannot override server rates.
5. CAC: wizard collects owner/director details, permitted office address, ID/signatures, checks completeness, provides price for chosen capital band, and issues a current-country invoice only after final review.
6. Complex website and NGO: self-service project brief with package, scope, timing, optional company name → restricted quotation ticket → client and staff notifications. No invoice until staff verifies detailed requirements and creates an approved custom quote.
7. Existing payment, job tracking, identity-document safeguards, agent review and mail-outbox infrastructure remain in place.

## Notification design

- **Customer:** after a successful order commit, app notification and queued email with order reference, service, invoice and amount, payable status and secure link.
- **Admin and finance:** new-order notification with reference and internal job link.
- **All active agents:** new-order notice without personal identification documents; customer-specific details are available only when assigned/authorized. All active agents may see the distinct quotation queue, but ordinary support and billing tickets remain private.
- **Quotation:** staff and agents receive notice; customer gets receipt, can follow ticket and communicate.
- Email dispatch depends on configured SMTP transport, `email_notifications_enabled()`, `notification_email_outbox` migration and actual scheduled outbox processor. This implementation alone does not prove email was delivered.

## Mandatory launch test matrix

- [ ] Verify real account-country selection, Nigeria→NGN and GB/US/CA/DE/AU→USD with independent rates, on iPhone/Android/desktop.
- [ ] Try forged POST NGN and USD in opposite regions; server must block.
- [ ] Try changing profile country and old `set_pay_currency` endpoint; server must block for customer.
- [ ] Test all four CAC paths, share capital 1m, 2m, 5m; custom >5m rejected/quoted; verify quote/base/add-ons/tax/invoice agreement.
- [ ] Check account creation, verification OTP and returning to the originally selected product/package.
- [ ] Check NGO and website quote requests appear for admin and authorized agents, client receives reference, no invoice created before accepted quote.
- [ ] Check all five digital fixed-rate services at both account regions, server source rates, taxes and invoice.
- [ ] Test trademark per-class payment in both NGN and USD, including payment-provider currency availability.
- [ ] Create *non-payable test* orders in staging and inspect customer, admin, finance and all-agent notification rows and outbox queue; verify actual inbox receipt, deduplication and retries. Then test provider sandbox payment callbacks and webhook idempotency with no double booking.
- [ ] Verify private document storage/protection, real database backup restoration, migrations, invoice PDF/receipt, cancellation/refund notices and role authorization.
- [ ] Confirm official CAC charges and share-capital-dependent regulatory cost schedule are correctly accounted for in every offered fixed package before collecting real money.
- [ ] Confirm VAT treatment, exchange-rate setting for optional NGN add-ons and trademark class rate. Independently verify provider gateway availability for NGN and USD.
- [ ] Screen for false billing location at sign-up (payment-provider billing info, address/KYC, sanctions and fraud checks where legally necessary). Locking a self-declared profile field alone is **not** sufficient location verification.
- [ ] Secure final product/legal/finance approval. Do not claim unrestricted production launch solely from passing repository tests.

## Market and official research checkpoints

- Nigeria CAC 2025/2026 official fee schedule: https://cac.gov.ng/fee-schedule/
- CAC public services and online registry: https://cac.gov.ng/services/company-registration and https://icrp.cac.gov.ng/
- Foreign-owned US entity tax filing: https://www.irs.gov/instructions/i5472
- Nigerian agency example price menus: market comparisons are indicative; inspect current scope and government-fee inclusion prior to price changes.
- Nigeria Data Protection Act and FCCPC consumer rules need compliance review for identity collection and pre-payment disclosures.

## Deployment

Source code and release script `scripts/deploy_launch_catalogue.sh` use an immutable commit SHA, `--check` then `--apply`, read-only release preflight, private backup, file rollback and public HTTP smoke test. Source files change only. No silent DB seed, no deletion of services, no changes to prior invoices. If the production safety gate fails, **stop**; do not bypass it to publish commerce.
