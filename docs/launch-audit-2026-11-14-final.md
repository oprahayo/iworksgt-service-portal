# iWORKS GLOBAL — evidence-based launch audit and release handoff

**Audit date:** 14 November 2026. **Product:** https://iworksgt.com and https://portal.iworksgt.com. **Decision:** **NO-GO for unrestricted paid launch** until the remaining live checks and operator sign-offs pass.

## What was actually checked

The independently executed GitHub Actions read-only job [#43622175443](https://github.com/oprahayo/iworksgt-service-portal/actions/runs/43622175443) ran from an internet-connected CI runner against actual production HTTPS endpoints on 14 Nov 2026 (not cached snippets).

- **32 PASS / 4 FAIL.** Public homepage, services, CAC detail and legacy routes, blog, contact, FAQ, About, tracking, portal login, robots.txt and sitemap.xml returned HTTP 200.
- Expanded Privacy, Terms, Cookies, overseas CAC and US LLC public pages returned HTTP 200 **with new expected content markers**, as did visitor chat consent controls on the homepage.
- Desktop/compact branded SVG assets fetched and parsed as SVG.
- All **12 safe GET/HEAD probes** across apex and portal for `database.sql`, `storage/mail.log`, `storage/setup.lock` returned **403**; response bodies were discarded. This supports those particular HTTP access controls but is not an authenticated file-permission or full penetration test.
- Four production failures: the registration form lacked explicit residence-country prompt; the homepage still showed a hard-coded 4.8 Google score, CAC in a client-logo ribbon and GoDaddy in the same client-logo ribbon.
- Source PR **#114** (immutable app commit `748bb5986317179319d7c96f00341200c4633a11`) corrects those four failures. The changes passed CI PHP 8.2/8.3, including isolated registration/country and review tests. **GitHub merge has not been proven published**.

The search-indexed public HTML snapshot in a separate retrieval service showed older legal and visitor chat UI, but the more recent CI live HTTP probe proved the newer legal/privacy controls were present. Do not infer a failed route from a web-crawler fetch error when direct safe GET gives HTTP 200.

## What source code was fixed

1. Registration requires customers to actively choose the country of residence; no fallback automatically makes them Nigerian when CDN location is missing. The server validates the submitted country against allowed values. Terms and Privacy are visible at sign-up.
2. The regulator CAC and technology vendor GoDaddy are not presented as iWORKS client projects; the CMS records are preserved, and other displayed client marks still require substantiated permission.
3. Removed the hard-coded unverified Google rating while preserving genuine opted-in completed-order reviews and a neutral listing link.
4. A repeatable, read-only live audit checks current legal routes, chat preference UI, account country prompt, branding, marketing trust content, and private HTTP denial. It can be launched from GitHub Actions **Live launch acceptance (read-only)** via workflow_dispatch after deployment.
5. The source-only release deployer must be pinned to app commit `748bb5986317179319d7c96f00341200c4633a11` in both the deployment and operator scripts. Avoid publishing the earlier all-roles pulse snapshot which would revert these fixes.

## Mandatory production acceptance — no synthetic success claims

**Release engineering**
- [ ] Operator executes the immutable **source-only** cPanel deployer `--check` against portal and public roots and saves redacted output. This preflight must be green; no bypass.
- [ ] Confirm current private encrypted SQL + file backup and a recent isolated restore/rollback rehearsal. Prior documented backup exists, but its existence does not validate a new post-change backup.
- [ ] Operator executes `--apply` only after the green gate, verifies source/version on both domains, and checks file rollback support. Do not use first-time migration/OTP invalidation for these UI fixes.
- [ ] Rerun live production launch acceptance after publication: target **36/36 PASS** (32 original passes + four corrections). Run separate SEO/canonical audit and test representative links on 320/375/768/1440 widths with actual browsers.

**Identity and customer lifecycle**
- [ ] Create one disposable Nigerian and one non-Nigerian test customer, verify email OTP actually arrives, login/resend/trusted-device/logout/relogin and password reset. Confirm new country cannot be spoofed by POST and unknown country never silently gets NGN.
- [ ] Confirm client/admin/agent/finance/author navigation, the shared branded flash, no overlay on sign-in or public pages, support ticket and consented visitor live chat/offline email.

**Commerce and delivery**
- [ ] Obtain explicit commercial sign-off on the discrepancy between the **live CAC prices** (Business Name ₦26,000; Ltd from ₦120,000 when tested) and the **proposed unapproved** Nov 14 price sheet (₦35,000; ₦75,000+). Preserve old invoices and refrain from arbitrary SQL price changes.
- [ ] Verify separately quoted government CAC filing fees, stamp duty, taxes, bank fees, refund/cancellation handling and accurate final invoice totals.
- [ ] Test real sandbox NGN/USD Paystack and Stripe paths (and any enabled other payment methods), signed callbacks, replay/duplicate idempotency, cancelled/rejected payment, status transition, and a controlled refund. Do not create live charges during an audit.
- [ ] Test a Business Name, Ltd at multiple share-capital amounts, partnership, NGO quote, trademark, website written-quote request and normal paid service. Confirm no premature automatic invoice on quote-only services.
- [ ] Send controlled app/transaction email to customer and all active staff roles, verify recipient **inbox receipt**, retry handling and the scheduled OTP/notification/visitor outbox worker.
- [ ] Confirm file ownership/role restrictions with synthetic data and verify customer downloads cannot be accessed from another account.

**Legal and operational approval**
- [ ] Keep legal notices accurate to actual vendors, retention periods, international transfers, consumer rights and statutory services. Obtain human counsel/privacy approval where required.
- [ ] Verify case/review publication permission, every remaining client-logo licence and all regulatory/third-party relationship language; do not invent endorsements.
- [ ] Verify registered legal entity details, business address, complaint channels, support staffing, payment merchant account names, required filings/accreditation and incident-response escalation.
- [ ] Agree on monitored cutover, responsible operator, rollback criteria and follow-up support coverage.

## Current decision

Public browsing and private-file **smoke tests** look substantially better than earlier snapshots, and the launch-trust fixes are merged and tested in source. **Do not advertise a fully verified commercial launch** while deployment, identity/email, payment/refund, statutory fee and privacy approvals remain unverified. A carefully scoped no-payment preview is a separate risk decision; do not upload real ID documents or collect money until the relevant acceptance gates pass.
