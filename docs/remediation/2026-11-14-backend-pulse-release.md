# iWORKS backend transition — production release handoff

- Approved brand displayed in loader: **iWORKS** (short product mark). Legal/company name remains **iWORKS GLOBAL LTD** everywhere else.
- Approved backend-only pulsing blurred-white loading overlay merged in PR #99 at immutable source SHA `d6285211fdb973a26fa6586edb0b4ce1a60e1419`.
- Code files: `assets/css/admin-transition.css`, `assets/js/admin-transition.js`, conditional `views/partials/header.php` and `views/partials/footer.php`. Uses first-party `assets/branding/iworks-compact.svg`.
- The overlay appears for authenticated operational staff backend routes. It is intentionally omitted from client workspace, login, OTP and the public marketing frontend. When a user navigates backend to public, animation occurs while leaving backend; when public to backend, it occurs upon backend arrival.
- No new database tables, migrations or production settings. Existing site/page content, uploads, private config, payment data and accounts must remain unchanged.
- PR #99 CI: PHP 8.2 and 8.3, Node JS checks and behavioral test passed. Release deployer is pinned to PR #99 merged application SHA and must be verified against this exact commit.
- Publication is through `scripts/deploy_admin_mobile_refresh.sh` read-only `--check`, followed by `--apply` only after mandatory gates pass. The release script keeps backups and rolls back files on failure. Do **not** invoke first-time publisher or incident token invalidation.
- After cPanel publication verify admin dashboard → jobs → settings → View website, browser Back, mobile navigation, and reduced-motion preference. The overlay must never intercept navigation or remain visible after load; check it stays absent from public and customer pages.
- If production is not yet published, do not claim otherwise.
