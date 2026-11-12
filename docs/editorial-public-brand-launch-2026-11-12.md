# iWorks Editorial & Public Brand Release — Handoff
Date: 12 November 2026

## Code state (verified)
- GitHub PR #27 merged, squash commit `91c489724dee9e2c686418e0b42b439d84384c80`.
- GitHub Actions PR run passed (PHP 8.2 syntax, existing smoke tests, editorial contract).
- Pinned cPanel deploy script update: `9c43e96ca3053cfa361d91adc77ebc985847cad8`.
- Deployment installer packages 64 portal files plus root files and provisions editorial schema.
- **NOT deployed from this chat. No authenticated browser smoke test, file-permissions penetration test or real Stripe test performed.**

## Changes
1. Public Insights landing: actual published posts, featured article, image-led responsive cards, pagination and hover/focus Read controls.
2. Article detail: typography, breadcrumb, real media, rich content from safe HTML, topics, sharing, session-scoped likes, related posts and moderated comments.
3. Public case-studies archive/detail and homepage spotlight carousel, backed by admin CRUD; shows only published records.
4. Homepage consent-gated customer feedback with company logo, circular portrait, title and quote; no placeholder testimonials seeded.
5. Original CSS illustrated service benefits, orange/dark/system theme selector, back-to-top, reduced apparent logo size. Guest header says Sign In; authenticated portal session changes link to account icon/dashboard using credentials-enabled CORS status endpoint.
6. New Author user role with strict allowlist (dashboard, own Insights, profile, notification support); admin User creation supports Author; richer Insights writing toolbar and sandboxed preview.
7. Admin Stories & Feedback panel for real cases, customer feedback (publication consent required), and blog comment moderation; improved dashboard shortcuts and grouped sidebar.
8. Fix frontend editor crash by removing dependency on undefined `list_travel_package_options()`; legacy travel package configuration hidden.

## Infrastructure
- New SQL migration: `scripts/migrate_editorial_content.php`, including users.role ENUM author, case studies, testimonials, moderated comments and blog likes.
- New `app/editorial.php` integrates through bootstrap. Public case-study URLs appear in sitemap; case article structured data is derived from the published record.
- Existing CAC/trademark, payment/Stripe and operational back-office business logic not deliberately changed.
- Deploy uses confirmed cPanel paths: public `$HOME/public_html`; portal `$HOME/public_html/portal.iworksgt.com`.
- Download pinned installer from raw GitHub at commit `9c43e96...`, save e.g. `$HOME/iworks-editorial-launch.sh`; run `bash -n`, then `--check`, then after **separate database backup** `--apply`.
- Installer backs up web files but **does not create or roll back a SQL database backup**. Database ALTER/CREATE may auto-commit; export MySQL before applying.

## Launch acceptance tests / blockers
- Verify no frontend editor fatal and that existing CMS pages still edit.
- Verify guest header Sign In text, logged-in account icon, all navigation links and logo proportions; theme system/light/dark persistence on iOS Safari and Android Chrome; responsive 320/375/390/768/1024px.
- Create an Author user (after migration); verify own blog draft/publish/edit, and verify denial to customers, orders, invoices, settings, admin, staff editor and Media Library.
- Create real article with image, H2/P/list/link, publish; check full URL, canonical, sitemap, tags, related posts, like button, share, moderation. Never permit unreviewed public comments.
- Create authentic approved case study with cover/logo; publish and check homepage card and detail.
- Create feedback with customer consent, brand logo, portrait, name/role; verify unpublished or unconsented entries never show.
- Verify homepage carousel keyboard/pause and reduced-motion handling, admin dashboard view, public Site editor and dark mode contrast.
- Complete CAC identity/document security acceptance and Stripe key rotation/test from prior release (separate existing blockers). Do not claim production launch-ready until these pass.

## Continuity
Next milestone after user runs deployment: collect cPanel `--check` and `--apply` output; compare live page code/UI against release, then execute controlled authenticated acceptance test. Keep iWorks separate from Ekiti Civic.
