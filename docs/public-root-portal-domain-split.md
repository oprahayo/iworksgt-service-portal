# iWorks domain split: public website at apex, private portal on subdomain

## Intended architecture

- `https://iworksgt.com/` — SEO marketing home, public service details and catalog, About, Blog, Case Studies, Contact, tracking page, sitemap and robots.txt.
- `https://portal.iworksgt.com/` — authenticated login, Google OAuth, client dashboard, orders, payments, agents, administration and secure document management.
- Canonical blog/service URLs and page metadata refer to the apex site. Legacy public URLs on the portal redirect (301) to the corresponding apex URL. Portal's `/sitemap.xml` and `/robots.txt` redirect to the public domain.
- Marketing root has a **tiny PHP front controller** that includes the existing portal application by local filesystem path. It shares the existing CMS database and does **not** copy credentials or customer data. Public and portal have separate browser origins/sessions.
- Public root serves CSS/JS/public images from the existing portal's `assets/` folder by symlink. If Apache does not allow a symlink, replace it with a static **copy of assets only**; do not symlink private storage or the whole application.

## Why the earlier setup was incorrect

On 12 November 2026, `https://iworksgt.com/` redirected to `https://portal.iworksgt.com/`. DNS primarily maps a hostname to an address; **HTTP redirection is normally configured at the server, cPanel Redirects, hosting forwarder or proxy layer**, not by DNS A records. The actual cPanel main-domain document root and the origin of that redirect must be verified before deployment.

The old PHP `public_base_url()` defaulted to the incoming host (the portal), thereby generating portal-domain public links and sitemap entries whenever explicit public-base configuration was missing. The updated routing forces the public canonical domain for the recognized production hosts and keeps Google authentication tied to the portal host.

## Prerequisites and environment

- Existing same cPanel account with separate document roots. Expected, but not independently verified:
  - apex: `$HOME/public_html`
  - portal: `$HOME/public_html/portal.iworksgt.com`
- Portal must retain `app/config.php`, its database, assets, storage, users, orders and uploads.
- PHP CLI, curl, readlink and common POSIX tools available. Apache must honor .htaccess rules. Domain TLS/SSL for both origins must be valid.
- Ensure cPanel **Domains** lists `iworksgt.com` with the intended public root; the portal domain points at its existing application directory. No domain should be mistakenly assigned the same document root.
- Remove any old **cPanel Redirects** or external domain-forwarding rule that redirects `iworksgt.com` to the portal. Do this after backing up/inspecting the existing root .htaccess; don't remove mail, MX, TXT, SPF, DKIM or unrelated DNS settings.

## Safe installation

First, inspect the actual document roots in cPanel > Domains. Then run the read-only checker:

```bash
bash scripts/deploy_public_root_split.sh --check "$HOME/public_html" "$HOME/public_html/portal.iworksgt.com"
```

If paths are correct, run explicit apply:

```bash
bash scripts/deploy_public_root_split.sh --apply "$HOME/public_html" "$HOME/public_html/portal.iworksgt.com"
```

The installation script downloads a pinned reviewed release, syntax-checks PHP, privately backs up modified files, updates 19 portal PHP/CSS/router files and writes the two small public-root files. It moves any existing public `assets` directory into the private backup and makes a symlink to portal's public assets. It backs up and replaces the apex .htaccess. **It does not modify app/config.php, customer data, database tables, payments, mail DNS or Google Cloud credentials.** A failed installer stage triggers rollback. The script reports when an external cPanel redirect still prevents the apex site from staying at the apex URL.

The script is intentionally not an automated DNS manager. It cannot change the actual cPanel domain assignment, active forwarding outside .htaccess, Google Cloud OAuth allowlist or Google Search Console ownership.

## Verification checklist

1. `curl -IL https://iworksgt.com/` should finish at apex without portal-domain redirect and return HTTP 200.
2. `curl -IL https://iworksgt.com/services` and a public service detail page should return 200 under apex.
3. `curl -IL https://portal.iworksgt.com/services` should redirect 301 to apex (old indexed links preserved).
4. `curl -I https://portal.iworksgt.com/` should serve/redirect to portal sign-in for guests; logged-in users should open the dashboard. It should not display the marketing home.
5. `https://iworksgt.com/robots.txt` and `https://iworksgt.com/sitemap.xml` should return content from the apex, with canonical apex URLs only.
6. `https://iworksgt.com/assets/css/app.css` should be HTTP 200; verify portal files and private storage are not browsable under the public root.
7. Test registration, Google sign-in, regular sign-in, payment and order workflows on portal; test public enquiry and public job tracking on apex.
8. Check iPhone/Android navigation, service listing, icons, images, mobile login and deep links.
9. In Google Cloud OAuth Web client, authorize exactly `https://portal.iworksgt.com/index.php?page=google_callback`; previously observed Error 400 `redirect_uri_mismatch`. Do not change callback to apex. Confirm Audience test users/publishing and run the safe OAuth diagnostic. Never share Client Secret.
10. Update Search Console domain property for `iworksgt.com`, verify sitemap and inspect 301 redirects from legacy portal URLs, not temporary duplicate indexing.

## Rollback

Installer saves private backups in `$HOME/iworks-deployment-backups/public-portal-split-*`. If the installer fails mid-change, it attempts automatic restoration. If application-level issues emerge after an apparently successful deployment, stop and restore files from the printed backup directory and the old public assets directory/symlink. Keep the portal database and app/config.php intact. If a domain forwarding rule remains configured in cPanel, remove it independently.

## Notes and limitations

Do not claim production has been split until both hosts and their HTTP/HTML responses have been checked after cPanel changes. Do not assume the default `$HOME/public_html` is the apex root on every hosting account; verify cPanel Domains first.

We are keeping a **single PHP application codebase** for this release (public view and portal view at separate hostnames). A future completely independent deployable marketing app is possible, but unnecessary for this separation and would duplicate backend integrations.
