# iWorks admin, mobile, CTA and content schema release — 2026-11-13

## Verified source changes
PR #28 added the support enquiry widget, rating/skip end-chat flow, and delayed consultation request drawer.
PR #29 corrected the portal Insights/Services routing collision, added missing editorial schema definitions, agent publishing access, a light-only website, grouped mobile navigation, a sitewide business CTA, and a redesigned shared operations shell.
GitHub CI passed for PR #29. The production website and database have not been updated or verified by these merges alone.

## Architecture
Public website: https://iworksgt.com with /blog, /services and /case-studies.
Secure portal: https://portal.iworksgt.com/index.php?page=blog, ?page=services, ?page=frontend and ?page=system_health (admin only).
Services and Insights edit in the backend. Published records link to their public pages.
The consultation form submits a request only. The support form delivers enquiries rather than simulated live replies.

## Deployment
From cPanel Terminal, download the repository's release script:
curl -fsSL https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/main/scripts/deploy_admin_mobile_refresh.sh -o "$HOME/iworks-admin-release.sh"

Run a dry check:
bash "$HOME/iworks-admin-release.sh" --check "$HOME/public_html/portal.iworksgt.com" "$HOME/public_html"

If document roots are correct and the database/files have been backed up:
bash "$HOME/iworks-admin-release.sh" --apply "$HOME/public_html/portal.iworksgt.com" "$HOME/public_html"

The script installs pinned, coherent application files and mirrors static CSS/JS where the public root does not share the portal assets. It preserves private configuration, uploads, storage, and server rewrite files. It creates rollback backups off the webroot. It does not run SQL.

## Database check and repair
Run from the deployed portal directory:
php scripts/repair_public_content_schema.php --check

Only after a separate MySQL backup and reviewing the report:
php scripts/repair_public_content_schema.php --apply

The editorial repair uses CREATE TABLE IF NOT EXISTS and an idempotent role check; it does not drop customer tables. Other core schema problems need independent investigation.

## Acceptance checks
Verify the public mobile menu, Get started button, booking drawer, light-only theme, CTA, public Insights and service detail pages.
Verify admin ?page=blog, ?page=services and ?page=system_health work without redirecting to the public site.
Verify agents can see their own Insights but cannot administer services.
Create a draft, publish it, then follow its public View link.
Run login, customer order, payment, notification and support smoke tests on production after deployment.

## Outstanding work
Deep per-page admin and agent workflow redesign remains a separate audit; this sprint focused on the shared layout and routing.
No real-time staff chat or calendar availability provider has been wired yet; never present unconfirmed appointments as booked.
