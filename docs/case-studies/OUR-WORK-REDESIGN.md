# iWORKS GLOBAL — Our Work gallery redesign (14 November 2026)

## Design reference and intended result

Owner requested the image-led layout of CKDigital's current [Our Work](https://ckdigital.com/work) page as a visual reference, while preserving iWORKS identity. The source was opened and captured in Chromium for reference: clear "Our Work" heading, industry filter controls, two-column cards dominated by actual website screenshots, small client descriptions, visible project navigation and a direct-client-site pattern. CKDigital text, logos, images and HTML have **not** been copied into iWORKS.

The iWORKS implementation is an original dark charcoal portfolio section with warm orange brand highlights and a restrained image-first hierarchy. The global website remains in its approved light theme; only the portfolio gallery section is dark. The homepage Selected Work section remains light and displays three matching selected projects. Individual case details use a wide actual screenshot, readable editorial body, an outcome and a direct client website link.

## Current project data

**All five approved client stories and WebP homepage screenshots already exist** in the editorial database/source release; no duplicate cards or new CMS table should be created. The public template fetches only `iworks_editorial_cases(true,80)` and renders the records found. It does not invent project outcomes or statistics.

| Client | Reviewed external website | Core project category |
|---|---|---|
| Amandla Institute | https://amandlainstitute.org/ | Institutional incorporation / hosting |
| Triply Travels Nigeria Limited | https://triplytravels.com/index.html | Company formation / web platform |
| Restoration LLC | https://www.restorationllc.net/ | Healthcare website / hosting / management |
| Karis Health Services, LLC | https://karishealthservices.org/ | Healthcare website project |
| Cornerstonetl, LLC | https://cornerstonetlllc.com/ | Senior-care website / domain / hosting |

Each direct "Visit website" action is populated only from a reviewed `https` URL mapped by the five launch-catalog slugs; unrecognised future projects have a case-study link but no invented customer URL. External links open in a new tab with `rel="noopener noreferrer"`. The homepage and full portfolio list use distinct anchors for reading the case study and visiting the client domain.

## Technical changes

- `views/public-case-studies-refined.php`: visual first, category filters with dynamic availability, real screenshot preview, short card summaries and dual links
- `views/public-case-study-detail.php`: wide screenshot with browser chrome, factual client context, tidy results/story layout and verified outbound link
- `views/public-home-clean.php`: homepage Selected Work three-card screenshot presentation with dual actions
- `app/editorial.php`: slug-scoped website mapping and accurate filter tags; no DDL or account changes
- `assets/css/portfolio-work.css`: own-brand responsive desktop/mobile visuals and keyboard/reduced-motion support; loaded safely from the PHP source root on split-root cPanel
- `assets/js/portfolio-work.js`: progressive in-place filtering, `aria-pressed` and live count updates; no network requests
- Tests: PHP source/content/screenshots and Node JS behavior, plus static desktop/mobile/case detail visual fixture and GitHub Actions capture

Screenshot sources are genuine first-viewport browser captures under `assets/branding/case-studies/*.webp`, not AI-generated mockups. The preview's browser toolbar is a visual presentation frame, not a claim of a browser being embedded. Site screenshots are subject to change; recapture only from the real client URLs and review before replacing published assets. Logos and client names remain subject to consent.

## Visual acceptance and safe release

1. Review the visual-audit artifact (desktop 1440px, mobile 390px, and case detail) generated on GitHub Actions. Confirm actual screenshot images load in all cards and the page retains no horizontal overflow.
2. Pass PHP 8.2/8.3 CI and the JavaScript filter behavior tests.
3. Merge source PR; **do not assume source merge means cPanel production publication**.
4. Pin a coherent, immutable source-only release. On the signed-in cPanel Terminal run `--check` with portal and public roots. Verify private backup/preflight. Use `--apply` only when safe. This change has **no database migration and needs no new case-study import** when the five records are already published.
5. Verify live `/case-studies` including categories, first and last client cards, client site links, case detail pages, screenshots at desktop/mobile widths, homepage Selected Work, and 36 public/22 SEO/24 private release audit checks.
6. If the live DB contains additional published projects, retain them. Do not delete any existing editorial content, client record or media upload in a style release.

**State:** Source implementation prepared for code review; production deployment requires actual operator execution and independent HTTP/browser verification. Do not report it as live until those checks pass.
