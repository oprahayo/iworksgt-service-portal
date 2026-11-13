# iWorks live chat and editorial rollout — 2026-11-13

## Status and boundaries

PR #34 implements a near-realtime, two-way public/staff conversation inbox, five-emoji completed-service reviews and the warmer Insights layout. It passed source regression checks and was merged.

A GitHub merge alone does not install source files or create tables on cPanel. No real visitor/staff message exchange has been confirmed on production yet. The live widget uses 5-second polling, not WebSockets. Audible alerts work only after a staff member enables sound in an open browser tab.

## Architecture

- Public widget: `views/partials/public-assistance.php` + `assets/js/public-assistance.js` posting with CSRF to `https://iworksgt.com/?page=live_chat` on the same origin.
- Backend: `app/live_chat.php`; tables `iw_live_threads`, `iw_live_messages`, `iw_live_staff_presence`. Visitor token is random and stored only as a SHA-256 hash in MySQL.
- Private staff portal: `https://portal.iworksgt.com/index.php?page=live_inbox` for admin/agent, and protected `?page=live_inbox_api`.
- Staff inbox shows masked network prefix (/24 IPv4 or /48 IPv6) and approximate country only if an upstream CDN provides its country hint. These are not GPS or verified city data; the network prefix can belong to a reverse proxy.
- Customer consultation bookings remain unconfirmed requests submitted to the existing lead handler. Existing support tickets and internal messages are not deleted or migrated.

## Safe cPanel source refresh

From cPanel Terminal:

```bash
curl -fsSL https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/main/scripts/deploy_admin_mobile_refresh.sh -o "$HOME/iworks-admin-release.sh"

bash "$HOME/iworks-admin-release.sh" --check "$HOME/public_html/portal.iworksgt.com" "$HOME/public_html"
```

Only if the preview reports the correct document roots and clean checks, back up files and run:

```bash
bash "$HOME/iworks-admin-release.sh" --apply "$HOME/public_html/portal.iworksgt.com" "$HOME/public_html"
```

The installer includes backups, protects application credentials and does **not** execute SQL. Check that the pinned SHA has been updated to include the current live-chat feature merge before running `--apply`.

## Explicit MySQL migration

First export a complete MySQL backup using cPanel/phpMyAdmin or MySQL CLI. Do not skip this step.

```bash
cd "$HOME/public_html/portal.iworksgt.com"
php scripts/migrate_live_chat.php --check
```

When the output shows missing live-chat tables and the backup is verified:

```bash
php scripts/migrate_live_chat.php --apply
php scripts/migrate_live_chat.php --check
```

The migration creates only the new live-chat tables using `CREATE TABLE IF NOT EXISTS`, not dropping data. If the CLI prints `Could not ensure ea-php-cli cache directory`, verify the installed EasyApache PHP CLI binary before any migration.

## End-to-end acceptance

1. Browse the public site in a private browser window. Open the support widget, provide a test name/email and start a conversation.
2. In a different browser signed in as admin, open `?page=live_inbox`, enable sound with a click, and confirm the new conversation appears.
3. Send a visitor message. Confirm the admin sees it (normally within five seconds), receives a sound if enabled, and can reply.
4. Confirm the public widget displays the real staff reply and online/offline status. Close the visitor tab; after about 70 seconds the inbox should show them as offline.
5. End chat on the visitor side, choose a rating or Skip, and confirm the conversation closes.
6. Test agent access and verify an unsigned visitor cannot access inbox routes.
7. Confirm masked network metadata and an approximate country only when available; do not describe it as exact IP/GPS detection.
8. Check consultation request remains functional separately.
9. Complete a test service, choose one of five emoji ratings, verify confirmation and public consent rules; historic 1–10 values should render as five-point ratings without database rewriting.
10. Open the Insights archive and an article on desktop and mobile; verify image crops, serif article headings and compact typography. Confirm "How it works" uses the new subtitle.
11. Confirm admin dashboard, invoices, checkout, payments and other existing workflows continue to function.

## Privacy operations

The chat records name, email, messages, approximate country when available, masked network prefix and timestamps. The public widget explains this before a visitor starts. Define a retention period, limit staff access and provide deletion/export procedures under the privacy policy before claiming complete GDPR/privacy readiness. Do not expose raw IPs or visitor tokens to staff.

## Outstanding integration

Calendar availability/meeting confirmation remains separate from chat. No staff response can appear when no operator has opened the inbox, but incoming messages remain available for later replies. Browser sound cannot play without interaction and is unavailable when the tab/device is not running.
