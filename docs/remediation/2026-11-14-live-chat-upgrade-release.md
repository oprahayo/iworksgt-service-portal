# Production publication — persistent iWORKS visitor conversations and live dashboard

Date: 2026-11-14

## Pinned source

- Implemented feature is merged as PR #102, immutable source \`b96d90794d8373aab64377b82c33631908bc3305\`.
- This operator branch pins existing release components to that source and adds scoped \`--chat-upgrade\` to \`scripts/cpanel_finalize_release.sh\`.
- The old \`--publish\` first-time deployment path is **not** suitable for this upgrade: it would repeat legacy migration/incident token rotation. The new mode prevents that.

## Operator command

Use a pinned immutable operator commit, not a floating \`main\` URL. After the ops PR merges, substitute its merged commit SHA in both URL and fourth argument:

\`\`\`bash
curl -fsSL https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/OPERATOR_MERGE_SHA/scripts/cpanel_finalize_release.sh -o "$HOME/iworks-visitor-chat-upgrade.sh" &&
bash "$HOME/iworks-visitor-chat-upgrade.sh" --chat-upgrade \
  "$HOME/public_html/portal.iworksgt.com" "$HOME/public_html" OPERATOR_MERGE_SHA
\`\`\`

The operator will verify security denials, stage immutable release privately, scan for duplicate payment references, create an encrypted complete SQL backup outside both document roots, prove a real isolated SQL restore using cPanel UAPI and core row counts, then apply **only** the additive \`migrate_live_chat_engagement.php\` schema. It must confirm the new columns/table, run mandatory full release preflight, back up replaced source files and copy the release to portal + mirrored apex static assets. It performs post-deployment HTTP/private-file and schema checks.

The encrypted backup path is printed; never copy actual SQL dumps or encryption keys into ChatGPT.

## Before acceptance

- Migration must pass \`--check\` with all four chat thread fields and the outbox table present.
- On the public site, test chat without optional preferences; chat still works.
- Open chat again in the same browser after leaving. With **Remember this conversation** selected, restore conversation. Verify staff replies appear on returning.
- When visitor minimized the chat but stays on site, verify unread badge and active status; staff should see page only if **Share current page** selected. Verify masked network prefix and optional country hint; exact street address, raw IP and GPS are not available.
- With both **Remember conversation** and **Offline email** selected, send staff reply after the visitor leaves, process queue through existing cron, verify actual email inbox receipt and returning chat. Avoid treating PHP mail() acceptance as inbox delivery.
- Confirm new graphical live visitor overview is directly beneath the staff dashboard hero, plus 7-day bar visualization in live inbox; verify mobile.
- Page-aware nudge uses a local non-surveillance rule, disclosed as automated, dismissible, and retains previously approved 2-minute delay.
- Verify the **actual published Privacy Policy page** (which may be customized in CMS and not overwritten by code defaults) discloses optional 30-day cookie, offline email and page sharing. Review any required consent/privacy/legal notices before enabling.
- Confirm cPanel scheduler \`scripts/run_scheduled_operations.php queue\` runs (do not duplicate cron installation); chat email retries depend on it.
- Operational acceptance requires actual live tests; neither GitHub CI nor successful file copy proves email inbox delivery or current presence accuracy.

## Status

Source code CI validated with PHP 8.2 and 8.3 in PR #102. The separate ops-release PR must pass CI and merge. Production is not confirmed until cPanel Terminal reports success.

## Integrated site snapshot and no-regression rule

The operator release is pinned to `b96d90794d8373aab64377b82c33631908bc3305` (merged PR #105), which incorporates the chat features (PR #102), homepage legal/SEO changes (PR #103), and latest two-column mobile footer/compact mobile navigation (PR #105). Using the prior chat-only pin would regress newer public UI. Preserve existing private configuration, storage, uploaded media, published CMS content and customer records.

Use the **merge SHA of this repin PR** as `OPERATOR_MERGE_SHA` for both the script URL and command argument. Never use floating `main` and do not use the first-time `--publish` mode. Merging code does not deploy production; retain the backup-gated verification and manual live email acceptance steps above.

## 2026-11-14 editable visitor preferences release

Application snapshot `b96d90794d8373aab64377b82c33631908bc3305` includes merged PR #108: existing visitors can update opt-in remembered-chat, offline email, public-page sharing, and optional country in the same thread. Preserves current site UX from #105 and previous fixes. Do not interpret country as exact geolocation or enable offline mail without the visitor's explicit opt-in.

Use the **merge commit of this operator repin PR** as the immutable operator SHA in the installer URL and fourth command argument (not `main`). The `--chat-upgrade` path still requires private HTTP checks, encrypted SQL backup, isolated restore rehearsal, additive migration, release preflight, source backup and HTTP checks. No first-time `--publish` migrations or token invalidation. Source merge does not activate production or prove actual inbox delivery.

## Shared all-account loader deployment pin

Source snapshot `b96d90794d8373aab64377b82c33631908bc3305` includes the merged all-authenticated-account flash screen PR #112, along with the previously merged visitor-consent fixes and launch pricing work. The source-only installer now deploys the same lightweight pulsing iWORKS screen on admin, finance, agent, author and customer portal pages. Public and logged-out authentication pages are excluded. Use this complete integrated snapshot, not the earlier partial chat release, to avoid reverting newer site features. The source-only `deploy_admin_mobile_refresh.sh --check` then `--apply` path does not apply migrations; it must still pass all mandatory production gates. Production verification remains required.

## 14 November — Launch trust/source snapshot `b96d90794d8373aab64377b82c33631908bc3305`

This source includes merged PR #114 and prior #112: explicit registration country, a truthful Google listing link without a hard-coded rating, exclusion of CAC/GoDaddy from the client-logo ribbon, registration Terms/Privacy notice, extended read-only launch smoke tests and the all-account iWORKS loading flash. Production HTTP audit found the previous registration screen and unverified logo/rating unchanged, although legal routes, chat consent and 12/12 private SQL/mail/lock denial checks passed. **Run the source-only installer, not first-time migration/OTP invalidation.** A successful website copy does **not** approve taking payment; complete live mailbox, verified billing, gateway/refund, consent and legal checks.

## Public installer lockdown release

The verified prelaunch source `b96d90794d8373aab64377b82c33631908bc3305` includes production security PR #117. The previously exposed `/setup.php` route must now fail closed with HTTP 404 on GET and HEAD; normal production must not define `IWORKS_ENABLE_WEB_SETUP=1`. Safe release is a migration-free source update with backup and mandatory preflight. The independent 24-endpoint live audit must turn from 22/24 to 24/24 before full launch; root private-file checks, the 36 public and 22 SEO checks must remain green. Do not enable first-time setup to complete a normal update.

## Five-client editorial release (14 November 2026)

Immutable application source `b96d90794d8373aab64377b82c33631908bc3305` includes all five professionally authored client case-study stories, a safe CLI publisher and **five genuine Chromium website screenshots**. The source deployer remains migration-free and preserving existing config, uploads and customer records. **It does not publish case studies into the database.** Following backup verification, perform CLI-only `scripts/publish_all_launch_case_studies.php --check PORTAL_ROOT`, then `--publish PORTAL_ROOT --backup-confirmed`, and verify the public case-study page and five detail routes. Project scope: Amandla Institute, Triply Travels, Restoration LLC, Karis Health Services and Cornerstonetl LLC. Client logo/media rights must be respected; avoid invented testimonials and metrics.
