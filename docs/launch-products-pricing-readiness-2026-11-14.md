# iWORKS GLOBAL — Launch Products, Pricing & Checkout Readiness

**Prepared:** 14 November 2026. **Status:** proposed commercial tariff, safe code implementation and required production acceptance tests. All prices are service fees/indicative starting quotes unless the live final checkout explicitly states additional official charges and taxes.

## 1. Nigeria and international tariff

These are intentionally separate regional tariffs—not a user-toggle FX converter. Nigeria is billed NGN; supported residents abroad are billed USD. For anonymous visitors, coarse network geography can only preselect a visible catalogue; **the verified billing/residence documentation must decide the market before final payment**.

| Product | Nigeria (NGN) | Diaspora / overseas (USD) | Order pathway |
| --- | ---: | ---: | --- |
| Business Name / Enterprise, one proprietor | ₦35,000 | Guided CAC service, 1.6x support fee converted into USD at configured checkout rate | CAC guided intake; add partner fees as configured |
| Limited Company, up to ₦1m share capital | ₦75,000 | Guided CAC service, 1.6x service support fee converted into USD | CAC guided intake; official filing/stamp duty may be additional |
| Limited Company, ₦2m–₦5m capital | ₦150,000 | Guided CAC service, 1.6x support fee converted into USD | CAC guided intake, capital verified against applicant data |
| NGO / Incorporated Trustees | Indicative from ₦180,000 | Specialist quote | No instant invoice; filing eligibility/publication needs review |
| Landing Page | From ₦180,000 | From $320 | Self-service scoped enquiry, written quote before payment |
| Business Website, up to 5 pages | From ₦350,000 | From $650 | Self-service scoped enquiry, written quote before payment |
| Online Store | From ₦750,000 | From $1,400 | Self-service scoped enquiry; payment gateway fees extra |
| Custom Website / Portal | Typically from ₦1,500,000 | Typically from $3,000 | Negotiated scope, no auto-charge |
| SEO / SMM / Digital Marketing | ₦150,000 | $450 | Standard new-order invoice; exact period/deliverables must be clarified |
| Domain & Web Hosting | ₦55,000 | $180 | Standard new-order invoice; renewal periods, domain costs, usage limits must be clarified |

**Important:** CAC fees and stamp duty are set by government, not iWORKS. Do not represent the service fee above as the entire official registration cost. Where actual official fees differ by entity/share capital, show an itemized final bill before payment. Do not guarantee regulatory approval, specific processing time or eligibility of foreign directors. The NGO range is a quote starting point, not a universal fixed price. All tax percentages must be reviewed; the existing CAC checkout baseline is intentionally 0% until tax advice is confirmed.

## 2. Independent market research

Public 2026 competitors / price guides:
- Companies.ng lists Business Name ₦25k standard / ₦50k fast-track, private company ₦75k standard / ₦150k fast-track and NGO ₦100k–₦200k: https://www.companies.ng/pricing
- Business Support by Megatrust lists CAC Business Name ₦45k and company ₦60k service fees: https://businesssupport.com.ng/pricing
- FastCAC's 16 October 2026 CAC fee breakdown illustrates official charges separately from agency fees: https://fastcac.com/blog/cac-registration-cost
- Etchpoint's 2026 comparison puts basic professional Nigerian websites at ₦150k–₦500k with ecommerce/custom projects into seven figures: https://etchpoint.com/what-a-website-costs/
- DPX public 2026 agency guide lists landing ₦80k–₦150k, business ₦150k–₦300k and ecommerce ₦400k–₦1.5m+: https://dpx.com.ng/how-much-does-a-web-designer-charge-in-nigeria/
- Tekora's October 2026 published entry prices are ₦250k landing, ₦500k business and ₦850k ecommerce: https://tekoraservices.com/insights/website-cost-nigeria

These external figures support the proposed NGN tiers. USD international pricing is an iWORKS commercial proposal, **not** a published foreign competitor rate or the result of a live FX quote. Review USD payment processing costs and profitability before accepting paid orders.

## 3. Implemented architecture and flow

1. Public catalogue lists only published services. Duplicate historic Business Name records are excluded; old jobs/IDs are preserved.
2. CAC chooser has Business Name, Ltd (₦1m), Ltd (₦2m–₦5m) and NGO (specialist) paths.
3. Guided CAC data, owner details, source documents and share capital generate a **server-quoted** total. Applicants cannot change billing currency using a field. Overseas CAC service support includes a configurable multiplier; no unsupported fee for capital beyond ₦5m.
4. Website visitors choose among four packages, enter name/email/short brief and submit directly into the existing lead inbox without a chat agent. Other quote-only services have the same self-service enquiry.
5. Website projects and other quote-only products **cannot be charged an arbitrary generic service price**. An admin must approve a written scope and create an invoice using the existing custom-order workflow.
6. Standard paid services use a fixed customer-country billing currency. Server-side quote/invoice formation rejects opposite-market requests and uses explicit USD tariffs for supported overseas products.
7. New paid-order transactions add in-app notifications and email-outbox entries for the customer and active admins/agents/finance. CAC guided orders and public website leads also alert staff. **Queued/sent-to-transport does not prove inbox delivery.**

## 4. Critical no-go tests before paid public launch

- [ ] Confirm the live configured services, actual official filing charges and commercial price list; run safe catalogue price `--check` before any DB writes.
- [ ] Verify independent Nigerian and diaspora identities with test accounts and residence evidence; test attempted API/POST tampering and country-change denial. Country profile declaration/IP hints are **not** strong proof of residency.
- [ ] Test domestic CAC sole proprietor, partners, single/multiple directors, capital ₦1m, ₦2m–₦5m, >₦5m error, NGO quote without instant payment; ensure no mismatch in final invoice, tax, official filing charges.
- [ ] Test all website packages and one custom portal request end-to-end: quote reaches staff, no invoice before approval, approved quote generates a payable invoice.
- [ ] Test NGN and USD standard product prices with actual signed-in users; invoice cannot silently use a converted domestic price as a diaspora tariff.
- [ ] Test account creation, OTP, payment gateways, invoice creation, idempotent callbacks, bank transfer, refunds and pending-versus-paid status transitions.
- [ ] Test email and app notifications to customer, admin, **every active agent**, retries and outbox worker/cron processing; inspect real inboxes, not only database rows.
- [ ] Verify tax treatment, invoice terms, cancellation/refund rights, published legal and privacy pages, secure handling of CAC IDs, data retention and any required government filing partner credentials.
- [ ] Execute a verified private DB backup, controlled migration status/preflight and restricted-file security checks before production code deployment.
- [ ] Review all other published product slugs in live DB: services not mapped to verified overseas tariffs must be quote-only, never silently FX converted.
- [ ] Run desktop/mobile, service links, onboarding and checkout smoke tests; capture test order IDs and redacted mail logs.

**Go/no-go conclusion:** Code review and automated CI cannot substitute for real accounts, configured email, statutory fees, verified billing-country documentation or live gateway tests. Do not advertise "fully tested production launch" until the checks above are evidenced and passed.
