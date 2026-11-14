# All-account iWORKS portal pulse transition — 14 November 2026

## Scope and brand choice

Approved short animated product brand: **iWORKS** with the original orange brand mark. The corporate/legal name remains **iWORKS GLOBAL LTD**, unchanged in site content and footer.

The same approved transition is now displayed for **every authenticated portal role** (admin, author, agent, finance and customer/client). Staff use `views/partials/header.php`; clients use `views/partials/client-header.php`. Both include the same reusable branded head and overlay partials. The public marketing website and logged-out sign-in, registration, reset and OTP pages remain unaffected. On a public-to-portal visit, the animation appears only when the authenticated workspace loads. On portal-to-public navigation, it pulses on the departing portal page, not the arriving public site.

## UI behavior

- White 72%-opacity, 7px backdrop blur shows existing backend content faintly behind it.
- Centered official orange SVG mark with a dark **iWORKS** wordmark.
- Smooth 0.62-second breathing/zoom pulse; short entry flash on navigation, instant no-motion fallback if the visitor requests reduced motion.
- Shows on eligible normal same-tab backend menu links and deliberate View website links. Excludes downloads, other sites, targets opening new tabs, same-page anchors, modified clicks, and canceled actions.
- Overlay is non-blocking (pointer-events none), never prevents navigation, and clears on browser back/forward restore and after a 1050ms safety timeout. Head-level 900ms fallback prevents a stuck overlay if the external JS fails to load.
- No network APIs, data collection, new dependencies, database migrations, or alterations to existing menus, authentication, customer pages, and public website.

## Architecture and rollout

Styles: `assets/css/admin-transition.css`.
Behavior: `assets/js/admin-transition.js`.
Presentation in shared `views/partials/portal-transition-head.php` and `views/partials/portal-transition-overlay.php` included by both authenticated staff and client headers; navigation script is loaded by `views/partials/footer.php` for **any** signed-in account.
Source: `assets/branding/iworks-compact.svg`.
Tests: `tests/admin_transition_contract_test.php` and `tests/admin_transition_behavior_test.js` added to CI.

Only publish with the repository's immutable, preflight-gated, source-only cPanel release after PHP 8.2/8.3 and Node checks pass. Do **not** rerun the first-time database-migration/incident publisher. cPanel operator controls actual live deployment.

Acceptance checks: separately sign in as admin, agent, finance, author and client. For each role, navigate between two permitted portal pages and back; verify the same short translucent iWORKS pulse on desktop and mobile, with brand mark/text visible. Confirm client applications → billing, browser Back, client → main website, and staff → public website. Verify no overlay on marketing, guest login or OTP pages; keyboard navigation, reduced-motion setting, JavaScript-off fallback, and unchanged forms. No database or private configuration changes expected.
