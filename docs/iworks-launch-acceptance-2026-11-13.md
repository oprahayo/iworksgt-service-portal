# iWorks website launch acceptance — 2026-11-13

## Latest source changes
PR #40 fixes a PHP fatal in client Support (`role_has_admin_access` undefined), restores legacy role helper for finance/admin, replaces native consultation date/time with candidate weekday and 30-minute interval start buttons in Lagos WAT, displays five emojis after chat close, moves back-to-top to the left of the page, brings orange accent to the Ready to Launch CTA, prevents overlapping Add to cart/Preview buttons, excludes unpaid admin-only Custom Service from client cart, and corrects a public homepage CTA that linked to portal-host Services.

Automated GitHub Actions checks passed for PHP lint, old/new regression suite, cPanel deployment syntax and JS syntax on the merged feature SHA 2ad845a00ef9b74d6e4b92f956c7ef313bc51aab.

## Booking model
The 15-minute consultation popup presents the next 25 working days (five per view) and WAT start-time request options from 09:00–16:30, every 30 minutes. These are suggested **requests**, not verified staff availability and not completed appointments. Guest emails are included in the lead request. A live scheduling/availability provider with staff busy slots, notifications, timezone rules and acceptance workflow is required before labelling times Available or Confirmed.

## Deployment boundaries
GitHub merges do not change the cPanel server. The safe deployment installer `scripts/deploy_admin_mobile_refresh.sh` must be pinned to the merged feature+audit commit. The installer checks file roots, runs PHP lint, backs up existing source/assets, preserves `app/config.php` and private storage, and does not run SQL.

From cPanel terminal (after DB/file backup and review):
```bash
curl -fsSL https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/main/scripts/deploy_admin_mobile_refresh.sh -o "$HOME/iworks-admin-release.sh"
bash "$HOME/iworks-admin-release.sh" --check "$HOME/public_html/portal.iworksgt.com" "$HOME/public_html"
bash "$HOME/iworks-admin-release.sh" --apply "$HOME/public_html/portal.iworksgt.com" "$HOME/public_html"
```
Then fetch/run the read-only auditor:
```bash
curl -fsSL https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/main/scripts/audit_launch_public.sh -o "$HOME/iworks-launch-audit.sh"
bash "$HOME/iworks-launch-audit.sh" https://iworksgt.com https://portal.iworksgt.com
```

## Acceptance matrix
1. Public home HTTPS 200, no PHP errors, main CTA points to apex Services, orange CTA accents, accessible featured insights and correctly cropped images.
2. `/services`, `/services/company-registration`, legacy business-name redirect, `/blog`, `/robots.txt`, `/sitemap.xml`, `portal/?page=login` all resolve; no false route into portal management.
3. Desktop/mobile width 320/375/768/1024/1440: no horizontal overflow; mobile menu opens/closes; public cards and portal service cards have no overlapping controls.
4. Consultation: usable digital date and time buttons, WAT label, date/time requests saved, guests captured, lead delivered to admin inbox, no confirmation until actual calendar acceptance.
5. Back-to-top remains hidden before scrolling, floats on left after scroll and never blocks live chat or appointment drawer.
6. Client Support: create/view/reply ticket, staff labels render, files remain private and download permissions validated.
7. Live chat: anonymous visitor starts chat, admin/agent receives message and replies, online/offline state, alert opt-in and end-chat five-emoji rating; if chat tables missing, follow previous migration handoff (backup MySQL first).
8. Client portal: account login, Google OAuth callback where configured, order flow, job/invoice, service payments and admin/agent/finance access checks.
9. Favicon appears consistently for home, inner pages, login, portal and client on hard refresh.

## Blockers
- Production deployment has not been performed/verified in this chat. The user has a cPanel Terminal tab open, but browser tooling here is read-only. Do not claim launch before terminal deployment and acceptance.
- Only source-level regression testing is verified; production booking email, MySQL storage, payments and protected portals need hands-on checks.
- Live real-time calendar availability is not integrated. Treat every date/time as a consultation preference until operator confirmation.
- Stale HTML or cached PHP/assets can show previous UI after code release; purge relevant CDN/opcache caches when safe and confirm deployment SHA.

## Operational next action
Merge the audit script, pin safe installer to that exact tested merged SHA, then run `--check`, `--apply` and read-only audit on cPanel. Capture status outputs and verify one real authenticated Support reply and visitor-to-agent chat before announcing production launch.