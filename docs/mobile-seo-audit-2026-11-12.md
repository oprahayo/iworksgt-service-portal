# iWorks public-site mobile and technical SEO audit
Date: 2026-11-12
Project: iWORKS GLOBAL LTD — `https://portal.iworksgt.com/`

## Evidence and limits
- Source audit: PHP public routing, public header/footer, services directory, CSS, routing and portal guest authentication.
- Public-page checks: Homepage and Services were accessible; Services listed 14 published public offerings at the time checked.
- This is **not** a full mobile Lighthouse run or Google Search Console coverage audit. No device-specific performance measurements, field Core Web Vitals, Search Console coverage, Googlebot crawl logs or Google Ads analytics were available.
- Google indexing cannot be guaranteed by code changes. Genuine ranking/indexing requires publication, crawling, quality content and Search Console checks.

## Defects and outcomes
| Severity | Finding | Change | Follow-up |
| --- | --- | --- | --- |
| Critical | No tracked XML sitemap or robots discovery route for services and insights | Dynamic published-only `/sitemap.xml` and `/robots.txt` rewrites | Verify HTTP 200 XML after deployment, submit sitemap to Search Console |
| High | Public header had no canonical tags | Canonical for all public pages, service detail, articles and category views | Confirm public/portal domain selection in live config |
| High | Individual services had no Service structured data | Valid JSON-LD Service from actual name, description, backend price/currency, provider | Validate representative service URLs with Google's Rich Results Test/Schema Validator |
| High | Service description was unbounded/long, social share was incomplete | Truncated clean page-specific descriptions, og:url/type/image, Twitter summary | Edit unique SEO titles/descriptions for each product in CMS |
| High | Order tracking could be indexed as a public utility page | noindex for tracking page; exclude tracking and auth routes from sitemap | Review indexing reports and request removal of old indexed private utility pages if necessary |
| High | Modal sign-in and register each output H1 in public homepage markup | Convert embedded headings to H2, preserve standalone auth H1 | Spot check DOM/heading hierarchy on deployed homepage |
| Medium | Mobile collapsed nav link targets and action buttons may be too narrow or crowded | Minimum 44px controls, mobile stack and focus indicators | Test iPhone Safari and Android Chrome at 320, 375, 390, 768px |
| Medium | Many blocking CSS files, Google Fonts, Bootstrap/Icons and externally sourced images | Scope mobile CSS, reuse current assets; no new JS library added | Next performance pass: consolidate styles, optimize images, locally host fonts if appropriate |
| Medium | Stale browser caching hid new design changes | Versioned assets, repaired earlier broken release installer | Check actual css URL and server/CDN cache after installing |
| Medium | Marketing contact/footer data sometimes differed across older pages | Existing CMS supports unified footer; no speculative overwrites | Confirm official contact, phone and address in Website Editor |
| Medium | Service titles/categories may be repetitive and thin | Individual service detail SEO metadata from published CMS text | Improve specific service descriptions; avoid templated/duplicate copy |
| Low | Unknown Google Search Console/robots crawl behaviour | Sitemap and canonical endpoints now available in code | Verify domain ownership, submit sitemap, monitor actual indexed URLs and Coverage |

## Mobile acceptance checklist (post-deploy)
- At 320–430px: no horizontal scrolling, tap nav/close menu, press search, toggle Grid and List, open a service, order, login/signup and submit forms.
- At 768px: menu remains usable; grid shows appropriate number of columns and card text does not overlap.
- At 1024–1440px: card prices, CTA, breadcrumbs and keyboard focus order remain intact.
- On iOS Safari and Android Chrome: no fixed popup clipped, text inputs zoom appropriately, keyboard doesn't hide submit, reduced-motion preference is respected.
- Network/SEO: `/robots.txt`, `/sitemap.xml`, service canonical and JSON-LD return real public-domain URLs; no 404/5xx.
- Search Console: submit sitemap and inspect Coverage / Page Indexing, then verify important service URLs individually.

## Operational safety
- No billing, order, customer, notification, permissions, or DB schema modifications are required for these SEO/mobile corrections.
- Only published public content appears in sitemap; login, dashboards and order tracking do not.
- Deployment script creates an archive backup and validates PHP before installation.
