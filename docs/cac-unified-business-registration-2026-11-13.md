# CAC Nigeria — Unified Business Registration product (2026-11-13)

## Government references
- CAC business names: https://cac.gov.ng/services/business-name
- CAC private companies: https://cac.gov.ng/services/company-registration
- CAC iCRP FAQ and name availability: https://icrp.cac.gov.ng/support/faq/
- Official public search: https://icrp.cac.gov.ng/public-search/
- CAC beneficial ownership: https://bor.cac.gov.ng/home
- NIMC NIN (11 digits): https://nimc.gov.ng/NIN

## One product, different legal pathways
Show one Business Registration product in public/client catalogues. The pathway selector separates business names (sole proprietor/partnership) from Nigerian private companies limited by shares (one/multiple owners, directors/shareholders). Nigerian private companies use Ltd/Limited, not a US LLC suffix. Limited by guarantee requires specialist review.

All new registration orders attach to the existing company-registration service ID. The CAC filing data retains its legal kind and pricing breakdown. Historical Business Name service IDs, completed jobs and invoices remain unchanged and accessible. Legacy duplicate rows are hidden from the normal catalogue and editing panels; old public detail slugs redirect to canonical.

## Single pricing authority
Admin Services > Business Registration holds sole-proprietor base, additional partner, private Ltd base (stored on existing company-registration record), additional director, optional managed address, and optional lawful director privacy advisory. Settings links to this editor instead of offering competing controls. New totals are calculated server-side; invoices freeze on creation. These are iWorks fees, not guaranteed CAC statutory charge totals.

## Name guidance
The client gets first and alternative proposed names, deterministic local name suggestions, and warnings for incorrect suffixes/restricted terms. They must still check the actual CAC iCRP and reserve an available name; no API reservation/approval is implemented, and no near-certain approval is promised. Name screening is preliminary, with staff review for regulated wording.

## Nature of business
Intake categories cover 35+ typical professions/sectors with a separate specific-activity field. They are indicative, not a certified current copy of all iCRP taxonomies; staff matches the final specific classification in the official portal before filing.

## Registered office and privacy
Managed office is unavailable until admin confirms a real traceable Nigerian physical address, permission to use it, and its price. The server replaces any client-supplied virtual address with the configured address; if office availability is disabled, checkout rejects virtual selection. Privacy advisory (default 50,000 NGN per director) is an optional lawful service for address/privacy planning, never an offer to conceal mandatory public director or PSC/beneficial ownership data. The CAC beneficial ownership register and mandatory disclosures remain applicable.

## Government ID
Government ID selector offers NIN, National ID card, passport, FRSC licence and voter card. NIN is strictly 11 digits; other types have broad alphanumeric format screening but no government authenticity/identity check. Staff must verify original documents. Sensitive documents remain in restricted private CAC storage.

## Favicon
Portal, public and independent client headers share the favicon helper. Uploaded setting takes precedence; where absent, a data-URI first-party iWorks SVG fallback means there is always a tab icon even without a public static asset. The link MIME follows the actual icon type.

## Release checklist
1. Merge only after PHP and JS CI passes.
2. Check and apply the cPanel installer only after it is repinned to this reviewed feature SHA. Back up live files; no database migration is required for this feature.
3. Confirm only one public/client product, correct pathway selection, and old historical orders still accessible.
4. Test quotes: sole, two partners, one/two directors, address advisory, privacy advisory. Verify invoice currency and frozen total.
5. Test bad NIN, nonmatching company/business-name suffix, existing name warnings and profession categories. Actual CAC approval must be obtained externally.
6. Test managed address disabled until verified, identity document upload and admin access.
7. Check favicon on website, inner pages, authentication, admin, client and mobile.

NOT DONE: real-time CAC name availability or API reservation, official ID authenticity verification, automatic legal vetting of an address, live site deployment, or production browser acceptance.