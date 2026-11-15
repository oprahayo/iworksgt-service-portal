# iWORKS GLOBAL — OrangeServer, RillaHost and Gansell launch amendment
Date: 15 November 2026

## Owner requirements

- Use the two supplied transparent hosting-service logo artworks in the **existing homepage hero logo ribbon**, not an oversized separate advertising band.
- Keep the hero marquee and all existing client logos; correctly identify outside providers instead of presenting provider logos as customer testimonials or brand endorsements.
- Add https://gansell.com/ as an authentic sixth case study, with its own real website thumbnail, URL and case detail page.

## Asset provenance and visual treatment

- `assets/branding/orangeserver-logo.svg` is a transparent scalable vector traced from the owner's supplied 816×157 PNG. It retains the green `#00a651` and orange `#f7941e` mark/wordmark.
- `assets/branding/rilahost-logo.svg` is a transparent scalable vector traced from the owner's supplied 357×55 PNG. Its white RillaHost logo is shown on a compact charcoal backing, so it remains legible on the homepage's light hero background. The user originally said “Rila Host”; the provided image reads “RillaHost”, which is used as the displayed brand spelling.
- The third supplied image visually resembles GoDaddy artwork. It was not included as a new client or claimed partnership because the request specifically identified OrangeServer and RillaHost; the independently classified GoDaddy logo that may exist in CMS is left unchanged.
- CSS keeps both logos modestly sized and visible in the original horizontally scrolling hero ribbon on desktop and mobile.
- The labels for both are **provider** and use the existing disclosure that platform/provider marks do not imply client or endorsement relationships.

## Gansell website evidence and content limits

The site was checked in real Chromium on 15 November 2026, returned HTTP 200, and identified itself as `Gansell Nigeria · Vendors, products and services`. It is a Nigerian vendor marketplace with public search, vendor/business listings and product/service categories. The initial automatic screenshot caught a loading overlay; the capture script now rejects screenshots with insufficient visual variance and waits for the actual rendered marketplace. Source captures and evidence:

- `assets/branding/case-studies/gansell-website-project.webp`
- `assets/branding/case-studies/gansell-website-project-capture.json`
- `scripts/capture_gansell_website.py`

The publicly presented Gansell portfolio content reflects the live website features. **No unprovided claim** was made about whether iWORKS developed particular modules, owns the platform, managed regulatory filings or hosts Gansell. Those scope details can be added to the CMS when the owner supplies them.

## Public publishing without database changes

`app/gansell_case_study.php` supplies the owner-approved source case; `app/editorial.php` includes it exactly once in the published case list unless a published CMS case with the same slug exists. The source case is directly accessible via `/case-studies/gansell-website-project`; the website link is allowlisted as `https://gansell.com/`. The homepage show limit is six, preserving all original five clients plus Gansell. Existing five-case launch data, media, orders and accounts remain untouched; a future editorial CMS publication of the same slug supersedes the source entry without duplicate cards. The project is immediately visible after safe source-only deployment, without requiring a database write.

## Release safety and acceptance

1. Ensure PHP 8.2/8.3 CI and the new `gansell_hero_partner_contract_test.php` pass.
2. Use a pinned, checked, immutable source-only cPanel publisher (not a full first-time installer). Run `--check`, validate a private recoverable backup, then `--apply` only with all prerequisites satisfied.
3. Verify both SVGs return HTTP 200, appear in the hero ribbon at suitable sizes, have correct contrast, retain original clients, and do not appear as client claims.
4. Verify Gansell screenshot HTTP 200, homepage marquee/case-study carousel presence, `/case-studies/gansell-website-project` HTTP 200 and actual `https://gansell.com/` outbound link.
5. Re-run launch public, private-file, SEO and mobile browser acceptance checks.

Production should not be reported as updated until the cPanel publisher and independent live checks actually succeed.
