# iWORKS GLOBAL — approved favicon update (14 November 2026)

## Source and approved design
Original owner-supplied orange rounded iWORKS mark (small ring, two diagonal bars, rounded check-like shape). Use exact four approved vector paths already in `assets/branding/iworks-compact.svg`, not an AI-generated substitute.

## Merged implementation
- PR #91, immutable application SHA: `53820f8b01a2d211f36ba3a905307f2ded4db610`.
- `assets/branding/iworks-favicon.svg`: true scalable orange vector, transparent background.
- `assets/branding/iworks-favicon-32.png`: transparent browser fallback.
- `assets/branding/iworks-favicon-180.png`: transparent Apple touch icon.
- `views/partials/favicon-head.php`: one consistent, cache-busted icon source included by public, admin and client headers.
- `app/helpers.php`: official favicon active by default on apex and portal domains, even if an older uploaded icon exists; existing uploaded file and its secure endpoint are preserved. Future intentional admin upload selects custom override.
- `index.php`, `views/settings.php`: admin can upload a valid raster replacement or restore original SVG.
- `tests/iworks_original_favicon_contract_test.php`, `tests/cac_unified_product_contract_test.php`: tests verify exact approved shapes, 32/180 PNG dimensions, HTML, override mechanism and split-domain deployer asset copy.
- CI PHP 8.2 and 8.3 passed on PR #91.

## Publishing
The source deployer `scripts/deploy_admin_mobile_refresh.sh` must be pinned to `53820f8b01a2d211f36ba3a905307f2ded4db610` by follow-on deployment PR. Production publishing is **not** performed by merely merging GitHub code.

Run `--check` before `--apply` using an immutable copy of the deployer. Both modes run the same mandatory read-only DB/mail/private-file preflight. The script backs up replaced source files and mirrors `assets/branding/*` to both document roots, excluding private `app/config.php`, uploads, storage, SQL and .htaccess. No new database migration required.

Acceptance: live GET with HTTP 200 and `image/svg+xml` for `assets/branding/iworks-favicon.svg` and 200 `image/png` for both fallbacks on `https://iworksgt.com` and `https://portal.iworksgt.com`. Public homepage, portal login and client workspace should render favicon links; browser may require hard reload due previously cached favicons. The favicon change does **not** alter the website navigation logo or existing customer data.

## Live status
GitHub code reviewed and tested; cPanel source release to be published by authorized terminal operator. Do not claim the favicon is already live without deployment output and HTTP checks.

## Approved tighter crop — follow-up

The owner approved a larger, closer crop to improve favicon legibility. PR #94, immutable source `df2423e279077a4eea3f06cd5c8a9af02594c3c4`, updates the SVG square viewport from `19 -74 310 310` to `40 -54 270 270`, retaining all four original vector path definitions and orange `#FF6600`. The mark now occupies approximately 92% of the favicon canvas width versus approximately 80% previously, with a small safety margin and the original proportions preserved. Both 32px browser PNG and 180px Apple PNG were regenerated with matching transparency. HTML uses a fresh `?v=20261114-iworks-icon2` cache key. GitHub CI on PHP 8.2 and 8.3 passed.

Source-only cPanel deployment should be performed using the *new* pinned installer from the following ops release PR. It must run `--check` then `--apply`, preserving private configuration, uploads, storage and the production database. Afterwards, verify the updated SVG and PNG on **both** apex and portal hostnames and check the browser tab after reopening it. A GitHub merge is not proof that production assets have been updated.
