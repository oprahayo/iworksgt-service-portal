# Backend-only iWORKS pulse transition — 14 November 2026

## Scope and brand choice

Approved short animated product brand: **iWORKS** with the original orange brand mark. The corporate/legal name remains **iWORKS GLOBAL LTD**, unchanged in site content and footer.

The transition is intentionally limited to the authenticated operational backend (admin, author, finance, agent, other staff roles using `views/partials/header.php`). No animation is injected into public marketing pages, login/OTP pages, or the client's dedicated workspace. On a direct frontend-to-backend navigation, it appears only as the backend loads. On backend-to-frontend navigation, it pulses on the departing backend page, not the incoming public site.

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
Presentation in authenticated staff-only `views/partials/header.php`; script bootstrapped conditionally in `views/partials/footer.php`, excludes client role.
Source: `assets/branding/iworks-compact.svg`.
Tests: `tests/admin_transition_contract_test.php` and `tests/admin_transition_behavior_test.js` added to CI.

Only publish with the repository's immutable, preflight-gated, source-only cPanel release after PHP 8.2/8.3 and Node checks pass. Do **not** rerun the first-time database-migration/incident publisher. cPanel operator controls actual live deployment.

Acceptance checks: admin dashboard → jobs → services → public website and browser Back, on desktop and mobile; confirm a quick translucent pulse; logo/text visible; no overlay on the public site or logged-out login pages; test keyboard and reduced-motion preference; verify navigation remains responsive even if JavaScript or assets are blocked. No database or private configuration changes expected.
