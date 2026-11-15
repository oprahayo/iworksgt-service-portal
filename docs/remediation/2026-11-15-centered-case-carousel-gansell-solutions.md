# Gansell marketing hub and centered case-study showcase — 15 November 2026

## Approved presentation

- Homepage case studies use a center-focused coverflow: one large active browser-window preview with smaller peeks on both sides, starting on case study 2 where three or more case studies exist.
- Drag, swipe, wheel, keyboard, previous/next and accessibility continue to work. Every screenshot still links to its case-study detail. No overlay action buttons.
- **One** iWORKS /solutions public page. Gansell is the featured section at /solutions#gansell. Existing old /solutions/gansell URLs redirect permanently to that anchor. Do **not** restore a separate Gansell landing.
- Our Solutions dropdown links to the page and to its Gansell anchor; other digital solutions, including Kobiza, are future additions and should not be presented as live products yet.
- Gansell is marketed as a free vendor storefront, multivendor discovery platform with public vendor URL, shareable product/store links, catalogue, cart, available checkout/payment methods, and address/live-location delivery choices. Do not promise payment options or provider fees that are not offered for a particular seller.

## Real images and source

GitHub Actions, `.github/workflows/capture-gansell-public.yml`, used headless Chromium against unauthenticated public pages:

- `https://gansell.com/` at desktop and mobile sizes
- `https://gansell.com/@ab-african` at desktop and mobile sizes

Source-controlled captures:
`assets/branding/solutions/gansell-home-desktop.png`,
`assets/branding/solutions/gansell-home-mobile.png`,
`assets/branding/solutions/ab-african-store-desktop.png`,
`assets/branding/solutions/ab-african-store-mobile.png`.

Capture validation checks the real website page title and AB ÁFRICAN FOOD AND MALL name, with Unicode accent normalization. These are public storefront images only. No user cart, checkout data or orders were captured. If files are absent, the site uses the already verified real marketplace image and a **labelled illustration**, never pretending that the illustration is a screenshot.

## Release

Continue using the existing, scoped, immutable-SHA GitHub→cPanel Bash publisher at `scripts/deploy_solutions_ui_refresh.sh`: `--check` first, then `--apply` with a verified private backup. It now includes the carousel JavaScript, footer CSS/JS cache version, and the optional authentic screenshot assets. Backup/rollback and mandatory security/schema/mail preflight remain in place.

Only mark production verified after checking:

- Case study 2 opens centered, neighboring screenshots are visible and smaller, mobile swipe still functions
- Clicking screenshot opens case study, no action buttons cover it
- Desktop/mobile menu includes Our Solutions → Gansell and only one landing at /solutions
- Screenshot gallery displays genuine desktop and mobile captures of the live marketplace and vendor
- Separate account, admin, customer payments, upload and database data remain unchanged
- /solutions and homepage serve normally on the public apex site

GitHub merge and PHP/JS CI success alone do not prove production deployment or live visual acceptance.
