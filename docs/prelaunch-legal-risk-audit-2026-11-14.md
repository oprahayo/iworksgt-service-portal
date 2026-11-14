# iWORKS GLOBAL LTD — Pre-Launch Legal & Regulatory Risk Audit

**Audit date:** 14 November 2026  
**Business:** iWORKS GLOBAL LTD, iworksgt.com and portal.iworksgt.com  
**Scope:** Public homepage/About/Contact/Privacy content accessible during this review, the current GitHub application source, registration and standard-checkout views, the public legal drafts, and primary regulator guidance.  
**Classification:** Preliminary legal-risk and launch-readiness review. **Not a Nigerian solicitor's formal opinion or an independent audit of live databases, credentials, contracts, payment transactions, browser storage, or hosting.** Unverified facts are not presented as compliant or noncompliant.

## Executive result: conditional NO-GO for an unrestricted paid public launch

The marketing website is usable and has good legal foundations in code, including separate private document access controls, CSRF tokens in reviewed forms, properly configured session-cookie parameters, clear separation of public and authenticated domains, draft legal notices, and conditional CAC/US LLC disclaimers. **However, the items below require documented closure before marketing at scale or taking sensitive identification documents through a general public funnel.** An evidence-backed limited pilot with controlled onboarding may be assessed separately by management and counsel.

## P0 / mandatory decision gates

| ID | Evidence / issue | Legal or commercial risk | Concrete remediation | Verification / owner |
| --- | --- | --- | --- | --- |
| L01 | Live homepage labels CAC and GoDaddy logos under “Selected brands we've worked with”; neither the review nor repository establishes client/endorsement consent. | Could imply a government endorsement, formal business relationship, or trademark authorization not proven. FCCPA requires truthful marketing. | Remove or withhold CAC and any other unapproved logo from Trusted Logos **unless** there is documentary permission and the relationship can be described accurately. A GoDaddy reseller relationship is not the same as GoDaddy being an iWORKS customer. Separate clients, technology providers and registries; show only actual approved clients in a client-logo strip. | Marketing + counsel: permission evidence, CMS screenshot, revised copy. |
| L02 | Public /privacy still rendered the older 10-section policy during audit; source branch PR #101 added expanded /privacy, /terms, /cookies and two overseas pages, but those changes were not verified on the live domain. Some new paths were unavailable to the website retrieval tool. | Customer notices can be out of sync with actual data processing; broken/missing policies undermine informed collection and commerce. | Deploy approved source release only after safety preflight, then verify all legal pages return HTTP 200, correct content, policy date, canonical and nav links on real desktop/mobile. Verify it is the canonical apex, not duplicated on portal. | Release engineer + counsel: live HTTP/network capture and legal sign-off. |
| L03 | Standard checkout `views/client-service-checkout.php` shows estimated price, tax and final-invoice note, but no visible Terms/Privacy link or acknowledgement next to “Continue to invoice”. `views/register.php` similarly has no visible legal links near account creation. This inspection did not cover every checkout route or saved records. | Notice/terms incorporation and proof that users saw cancellation and material conditions can be disputed. | Put legible Terms and Privacy links and a short cancellation/price disclosure near account creation and final payment; ensure an appropriate recorded acceptance where relied upon as contractual agreement (version, timestamp, order ID, actor); do not treat an obligatory privacy notice as consent for unrelated marketing. Keep existing checkout calculations. | Product + counsel: UI screenshots, acceptance log and signed-off versioning. |
| L04 | Existing source Terms explains refunds in broad conditional language; checkout uses “estimated total” and offers cross-currency recalculation. Final pricing, government fees and the actual refund lifecycle were not live-tested. | FCCPA rules emphasize plain terms, complete pricing and cancellation remedies. Blanket no-refund clauses can be unlawful. | Map each service: iWORKS fee; official CAC/registry/stamp duty; tax, payment/FX fee; non-refundable third-party payment; point work begins; conditions for full/partial refund; exception for unperformed service; response and escalation route. Show exact scope and final payable total *before payment*. Reconcile written policy, invoice and gateway refund workflow. | Finance + product + counsel: test orders NGN/USD and refund receipts. |
| L05 | The service requests may collect identification, director, shareholder and beneficial-owner information. The source Privacy draft names categories, purposes and rights but does not prove actual retention periods, data processor agreements, security practices or cross-border transfer bases. | NDPA 2023 s.27 and s.29, GAID 2025; heightened confidentiality and DPIA questions. | Data map every collection point and vendor, classify records, determine a lawful basis for each purpose, prescribe specific justified retention periods, rights requests, breach response and DPA/processor contracts. Conduct a risk-based DPIA for high-risk ID-processing activities; confirm NDPC registration and audit-return obligations in light of actual processing class. | Privacy lead + independent NDPC-qualified adviser: records, policy matrix, vendor agreements, applicable registration. |
| L06 | Public source says “Selected brands we've worked with” and the About page links a “4.8 / 5 On Google” rating. Review could not independently authenticate the current Google rating or every displayed review consent. | Potentially misleading testimonials/reviews or infringement of client logo rights. | Verify the live Google Business Profile rating against current source and update/remove if not exact; preserve review order-verification evidence and customer permission for public quote/name/logo, with withdrawal/escalation procedure. | Marketing + client success: dated source, consent logs. |
| L07 | Prior release preflight explicitly says controlled mailbox, gateway sandbox and backup verification are separate acceptance requirements. Source file-access controls exist, but live production endpoint protection wasn't independently tested here. | Payment loss, public identity-document exposure or unfulfilled orders are serious legal and security risks. | Do not bypass existing release gates. Confirm verified private backup, denial of direct HTTP access to storage, logged-in role authorization, payment webhooks and idempotency, real transactional emails, OTP/reset, incident response and provider reconciliation. | Engineering + operations: test artifacts and redacted run logs. |

## P1 / launch-follow-up work

| ID | Finding | Action |
| --- | --- | --- |
| L08 | The reviewed session-cookie code uses `Secure`, `HttpOnly`, and `SameSite=Lax` settings; `pay_currency` functional cookie and support-chat `sessionStorage` are present. A full live browser cookie/network inventory is not yet available. | Audit cookies, tags, external fonts/images, OAuth, video and payment scripts by purpose and country. Do **not** assume a consent banner is needed for necessary cookies alone; add granular prior consent, refusal and withdrawal only where optional technologies and applicable law require it. |
| L09 | Marketing mentions Nigerian CAC filings, trademarks, NAFDAC documentation, export licences, BPP-related services and US LLC formation. Claims of qualified professional handling, agency accreditation or geographic eligibility were not independently proven. | Verify iWORKS role and any partner credentials, CAC accredited-agent status when filing as such, correct agent-of-record, regulated advisers and service availability. Show "independent service provider; government approvals not guaranteed." Never imply government affiliation. |
| L10 | Overseas guides provide caveats about remote filings and US LLC tax consequences. | Keep them current. **As of 14 Nov 2026**, US-created companies are exempt from FinCEN's federal BOI reporting requirement under the Aug 2026 final rule; this does not remove possible IRS Form 5472/1120, state, bank or Nigerian tax obligations. Confirm per state and owner. |
| L11 | Contact emails, phone, location and company description vary between live historic material and newer source defaults; registered business evidence not supplied. | Confirm the exact CAC registered company name, RC number, business address, complaint contact, and whether the displayed premises/address is real and authorized. Align header/footer, invoices, policy controller name and payment receipts. |
| L12 | The Privacy page draft allows cross-border transfers in broad terms but actual processors/regions and transfers not verified. | Identify recipients and regions and use lawful contractual/transfer safeguards where applicable; document how overseas founders are informed. UK/EU rules must be considered only where their territorial scope actually applies. |
| L13 | A separate US LLC formation product in the catalogue cannot be assumed to be active from its slug or marketing copy alone. | Confirm scope and fulfilment partners before enabling paid US LLC checkout; if unavailable, use a contact-only enquiry (current guide code already falls back when product is unpublished). |
| L14 | Pexels photos and third-party logos are used across the marketing site. | Retain source/licence records and verify commercial use, model release/endorsement context, and client approval. Stock subjects must not be presented as iWORKS personnel or actual customers. |
| L15 | Marketing emails and promotional messaging not reviewed in connected provider records. | Separate transactional notices from opt-in marketing, collect and record consent/opt-out where applicable, maintain suppression and deletion handling. |
| L16 | Service change/failure complaints need an end-to-end human workflow. | Publish accessible complaint channel, acknowledge case IDs, document escalation/response times based on actual staffing, and never promise a regulatory or bank outcome. |

## Source evidence and positives

- Actual secure-cookie code in `app/bootstrap.php`, a functional preference cookie in `app/helpers.php`, and support-chat session storage in `assets/js/public-assistance.js`.
- The published-portal service pricing policy in `app/customer_service_policy.php` avoids depicting unconfigured offers as free and uses server-side price calculation. `views/client-service-checkout.php` displays base/tax/estimated total and warns about FX recalculation.
- `app/file_access.php` contains explicit ownership/role checks; these are **source-review positives, not evidence of real production endpoint testing**.
- `views/public-legal-pages.php` contains expanded 2026 privacy/terms/cookie text, but release/live equivalence was not proven.
- User-reported visible home SEO text is a template paragraph, not a meta tag; removed in the associated code patch without noindexing or deleting overseas information.

## Source of law / authoritative information

1. Nigeria Data Protection Act 2023, especially sections 25–29 and 39–44: https://ndpc.gov.ng/wp-content/uploads/2024/03/Nigeria_Data_Protection_Act_2023.pdf
2. NDPC General Application and Implementation Directive (GAID) 2025: https://ndpc.gov.ng/wp-content/uploads/2025/07/NDP-ACT-GAID-2025-MARCH-20TH.pdf
3. NDPC Registration of Controllers and Processors of Major Importance Guidance Notice (scope/classification exemptions must be reviewed against actual business): https://ndpc.gov.ng/wp-content/uploads/2026/01/NDP-ACT-GAID-2025-MARCH-20-1.pdf
4. FCCPC Nigerian consumer rights, plain language, full pricing, cancellation and marketing accuracy: https://fccpc.gov.ng/consumers/consumer-rights-responsibilities/rights-responsibilities/
5. CAC online portal and accredited-agent distinction: https://icrp.cac.gov.ng/ and https://icrp.cac.gov.ng/support/faq/
6. UK ICO cookies and consent, if UK electronic-communications rules apply: https://ico.org.uk/for-organisations/direct-marketing-and-privacy-and-electronic-communications/guide-to-pecr/cookies-and-similar-technologies/
7. IRS Form 5472 requirements: https://www.irs.gov/instructions/i5472
8. FinCEN August 2026 final BOI rule: https://www.fincen.gov/boi

## Acceptance checklist — objective go/no-go

- [ ] Remove the cross-border SEO paragraph from the live home section without disabling SEO metadata/guide URLs.
- [ ] Confirm true client/partner/regulator affiliations and permissions for CAC/GoDaddy/other logos; remove unsubstantiated claims.
- [ ] Publish the legally reviewed, **accurate to actual operations** privacy, terms and cookie notices, confirm all links work.
- [ ] Add material Terms/Privacy/price/refund disclosures at collection and purchase points; demonstrate stored agreement evidence as appropriate.
- [ ] Prove order pricing, all taxes, government fees and refunds across each route/currency.
- [ ] Complete NDPA/GAID screening, NDPC registration analysis and records of processing / retention / cross-border vendors; create privacy incident and rights-handling playbooks.
- [ ] Verify service/agent credentials, delivery scope, US LLC cross-border guidance and compliance limits.
- [ ] Verify marketing reviews, testimonials and stock/photo licences.
- [ ] Complete private-file penetration checks, backup restore dry run, payment callback idempotency, gateway sandbox tests, live mailbox and OTP login regression checks.
- [ ] Obtain written legal/management go-live sign-off with date, responsible reviewers and supporting evidence.

## What this audit does **not** establish

No direct access to live cPanel storage, private customer data, merchant admin dashboards, provider contracts or Google Business Profile was used. The web retrieval tool could read some public pages but did not confirm the new Terms, Cookie, or international guide pages; inaccessible results are **not proof** that the URLs are down. No manual browser cookie interception or security penetration test was performed. No claim of legal compliance certification or attorney-client representation is made.
