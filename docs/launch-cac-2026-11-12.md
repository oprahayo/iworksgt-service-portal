# iWorks Launch Readiness — CAC Business Name and Company Filing

Release scope: 12 November 2026 — production requires explicit cPanel deployment and real acceptance tests.

## Customer journey
1. Choose **Business Name** (sole proprietor or partnership) or **Private Limited Company** in published customer catalogue, including order resume after Google/password authentication.
2. Complete all applicant details on a draft and reuse lead proprietor residence as business address using an explicit checkbox. Add/remove additional partners or director/shareholder individuals.
3. Select identification type, upload government ID, photo (business name) and drawn/uploaded signature per applicant. Each file is stored outside web roots and **only displays green success after the server has verified upload and ownership**. Replacing an applicant's identity invalidates their old file attachments. Max file size 5 MB.
4. Validate all required fields and attachments server-side; review the draft and calculated quote BEFORE creating the job/invoice.
5. Confirm application -> create one unpaid job and one invoice (skip redundant generic intake form), then pay via existing gateway flow. Payment verification triggers normal processing.

## Pricing
- Business Name base **NGN 26,000** for first proprietor.
- Partnership **NGN 15,000** per additional partner beyond first. A partnership must have at least two people or the customer must choose sole.
- Company private limited price and additional director/shareholder prices editable in Admin -> Settings -> **CAC Registration Pricing**. Base defaults to active Company Registration service price until an admin overrides it, while extra director and shareholder fees default to zero. Statutory duties and special-regulated cases must be confirmed by staff. All new quotes recalculate from saved admin rules at checkout; previously issued invoices retain amounts.
- USD quote uses configured NGN/USD rate, not a live third-party feed.

## Security
- CAC applications and document file rows are scoped by authenticated client_id in every read/write.
- Private document files use random storage names outside web roots, MIME validation, 5 MB max; downloads are owner or staff job-access checked.
- New application data and files require CSRF/session checks.
- No automatic submission to CAC government API is implemented or claimed. Staff download applicant documents and file manually through CAC's authorized process.
- Customer's drawn signature is collected as evidence for intake; an official CAC template or additional wet signature may still be needed.
- Foreign business-name proprietor eligibility, regulated objects, minors, required shares/beneficial ownership and government stamp duties must receive professional staff review before filing. This workflow does not replace staff legal/compliance review.

## Customer workspace and public site
- My Services displays only one entry for each wizard application and linked job. Draft applications deletable after confirmation, unpaid generic pending requests deletable after client ownership and payment check; paid and active work not silently erased.
- Billing includes saved Stripe payment methods on the same page as invoices; no separate Saved cards navigation. Existing Stripe token vault remains available via URL. NGN card vault for Flutterwave/Paystack is **not implemented** and provider tokens cannot be shared.
- Customer Support ticket form selects up to 20 owned orders; links visible to support team.
- Dashboard shows tinted services; Main website and Sign out are larger touch targets.
- Homepage removes awkward keyword-link row but keeps natural SEO copy; Insights navigation and four published-post carousel, orange CTA, sign-in icon, and Track Order advert are included. Fewer than four published posts means fewer display; do not fabricate articles.

## Launch blockers / checklist
- [ ] GitHub Actions lint, unit and regression contract tests green.
- [ ] Run combined release installer --check then --apply using verified cPanel paths, confirm private backups and schemas.
- [ ] End-to-end test *fresh* Business Name sole & partnership (1 additional partner = NGN 41,000), company director/shareholder pricing and USD FX quotation.
- [ ] Real mobile Safari/Chrome upload test for ID, photo, draw signature and existing draft re-open; verify **server-confirmed** green status and no stale file ownership after changing/removing partner.
- [ ] Test invoice creation, cancellation, pay/confirmation and no duplicated order in My Services.
- [ ] Confirm staff can access submitted CAC application and private document downloads, but other customers cannot.
- [ ] Create/support ticket tied to 1 and multiple orders; check admin/finance list; verify unauthorized job IDs rejected.
- [ ] Test desktop/mobile at 320, 375, 390, 768, 1024 px; keyboard focus, carousel reduced-motion, accessible icon sign-in.
- [ ] Rotate expired Stripe live API secret using authorized gateway settings (never post keys in chat), verify sandbox and webhook first.
- [ ] Review CAC statutory requirements/consent, company share capital/beneficial owner details, minors/foreign owners before claiming fully automated CAC filing.
- [ ] Publish four real Insights in admin Blog and confirm indexing/analytics and title metadata.
- [ ] Confirm data retention/privacy notice, backups, file permissions, host PHP upload_max_filesize/post_max_size (at least 5MB + overhead) and server error logs.
- [ ] Execute production test order to valid payment gateway with explicit authorization; do not mark launch-ready without this.

Source baseline: CAC official business-name guidance https://cac.gov.ng/services/business-name ; iCRP FAQ https://icrp.cac.gov.ng/support/faq/ ; company guide https://cac.gov.ng/services/company-registration .
