# iWORKS GLOBAL — International SEO & Legal Website Release

Date: 14 November 2026

## Scope

- New, **direct-on-page**, unboxed legal documents at `/privacy`, `/terms`, and `/cookies`.
- Existing published CMS page drafts, revisions, uploads, services, prices, checkout, portal sessions and customer data remain unchanged. The three legal URLs are served by explicit public PHP templates; existing CMS legal content remains in the database but is no longer used for these three rendered pages.
- Two substantial, non-duplicative cross-border guides:
  - `/register-company-nigeria-from-abroad` (UK, Canada, US, Germany, Australia and other overseas residents)
  - `/us-llc-formation-from-nigeria` (US state registration, registered agent, EIN, foreign-owner tax reporting, Nigerian founders)
- Source-managed page title/description, canonical URL, WebPage + breadcrumb structured data on guides, sitemap integration and contextual homepage/footer links. No country doorway clones, keyword stuffing, fake ratings or unverified service guarantees.
- CSS is scoped to the legal/guide pages and mirrors to the canonical apex static-asset root on a split-root cPanel setup; an inline CSS fallback handles public PHP delegation.
- Minimal immutable-SHA deployment with `--check`, required release safety preflight, private file backup, rollback, PHP/route tests, and post-deployment URL smoke checks.

## Primary official sources consulted

- Nigerian Corporate Affairs Commission: https://cac.gov.ng/services/company-registration
- CAC company-registration portal: https://icrp.cac.gov.ng/
- Nigeria Data Protection Act 2023: https://ndpc.gov.ng/wp-content/uploads/2024/03/Nigeria_Data_Protection_Act_2023.pdf
- Nigeria Data Protection Commission GAID 2025: https://ndpc.gov.ng/wp-content/uploads/2025/07/NDP-ACT-GAID-2025-MARCH-20TH.pdf
- UK ICO cookie guidance: https://ico.org.uk/for-organisations/direct-marketing-and-privacy-and-electronic-communications/guide-to-pecr/cookies-and-similar-technologies/
- European Commission transparency requirements: https://commission.europa.eu/law/law-topic/data-protection/information-business-and-organisations/obligations_en
- IRS foreign-owned entity reporting: https://www.irs.gov/instructions/i5472
- IRS international EIN applications: https://www.irs.gov/businesses/employer-identification-number

## Operational/legal validation before claiming full compliance

1. A responsible company officer or lawyer must verify the exact registered legal identity/address and that support@iworksgt.com receives rights and dispute requests.
2. Inventory all current third-party processors: payment gateways, hosting/data locations, email/SMS, chat, document storage, fonts, analytics, consent tools and identity services.
3. Confirm actual retention schedules, customer contract terms, withdrawal/refund processing, cross-border transfer safeguards and regulatory complaint contacts against practice. The site intentionally makes no unsupported fixed retention or service timeline promise.
4. Check country-specific privacy applicability (NDPA/GAID, UK GDPR + PECR, EU GDPR/ePrivacy, and any applicable regional US, Canadian, Australian rules). These drafts are not a legal opinion or a certification of compliance.
5. Inspect the live site for non-essential cookies/scripts before representing the cookie implementation as consent-compliant. **A Cookie Policy by itself is not consent.** If optional analytics, remarketing or non-essential third-party tracking exists, deploy a real prior-consent preference manager with equal refusal and withdrawal, and prevent the scripts before consent.
6. Validate that the US LLC catalogue service is genuinely offered and can be fulfilled, and that the required partners/advisers are available. The new guide defaults to the Contact page if the corresponding published product is absent.
7. Reconfirm current CAC eligibility (including foreign-owner cases), US state requirements, EIN/Form 5472/1120 applicability and beneficial-ownership rules on each individual file before making client representations.
8. Confirm any legal copy or data processing disclosure needed at collection points (chat, checkout, forms, identity upload), not just the standalone policy page.
9. Confirm the 5 pages' public HTTP 200, unique H1, canonical tags, WebPage and BreadcrumbList metadata on guide pages, sitemap indexing, no duplicate CMS-rendered legal sections, and mobile readability.
10. Submit the sitemap in Search Console and monitor crawl/indexing after release. The deployment does not promise or verify Google rankings or indexing.

## Rollout

Use the immutable merged commit SHA with `scripts/deploy_international_legal_seo.sh --check`, and only when mandatory preflight passes use `--apply`.

Never deploy or publicly announce legal compliance if operational reality has not been validated. Mandatory safety gates are fail-closed; do not bypass them merely to publish visual or SEO adjustments.
