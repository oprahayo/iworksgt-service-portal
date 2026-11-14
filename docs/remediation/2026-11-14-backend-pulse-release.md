# iWORKS authenticated portal transition — production release handoff

- Approved brand displayed in loader: **iWORKS** (short product mark). Legal/company name remains **iWORKS GLOBAL LTD** everywhere else.
- Approved backend-only pulsing blurred-white loading overlay merged in PR #99 at immutable source SHA `d6285211fdb973a26fa6586edb0b4ce1a60e1419`.
- Code files: `assets/css/admin-transition.css`, `assets/js/admin-transition.js`, conditional `views/partials/header.php` and `views/partials/footer.php`. Uses first-party `assets/branding/iworks-compact.svg`.
- The overlay appears for authenticated operational staff backend routes. It is intentionally omitted from client workspace, login, OTP and the public marketing frontend. When a user navigates backend to public, animation occurs while leaving backend; when public to backend, it occurs upon backend arrival.
- No new database tables, migrations or production settings. Existing site/page content, uploads, private config, payment data and accounts must remain unchanged.
- PR #99 CI: PHP 8.2 and 8.3, Node JS checks and behavioral test passed. Release deployer is pinned to PR #99 merged application SHA and must be verified against this exact commit.
- Publication is through `scripts/deploy_admin_mobile_refresh.sh` read-only `--check`, followed by `--apply` only after mandatory gates pass. The release script keeps backups and rolls back files on failure. Do **not** invoke first-time publisher or incident token invalidation.
- After cPanel publication verify admin dashboard → jobs → settings → View website, browser Back, mobile navigation, and reduced-motion preference. The overlay must never intercept navigation or remain visible after load; check it stays absent from public and customer pages.
- If production is not yet published, do not claim otherwise.

## All-authenticated-accounts extension (14 November 2026)

The prior staff-only scope was expanded at the owner's request. The same official orange iWORKS loading transition is now shared by the client workspace and all staff roles through reusable `portal-transition-head.php` and `portal-transition-overlay.php` templates. `views/partials/footer.php` includes the existing navigation script for **every** logged-in user. Login, OTP, registration and public marketing pages remain without the loading overlay.

Preserve the existing first-paint safety timeout, brief pulse, reduced-motion handling, non-intercepting navigation, and back/forward-cache cleanup. Source regression validates admin, client, agent, finance, author and guest exclusions. Do not deploy from an outdated pinned snapshot or repeat first-time migrations: pin the source-only installer to the verified merge containing this change, then run the safe cPanel `--check` and `--apply` sequence. Merge and CI alone do not establish live deployment.
