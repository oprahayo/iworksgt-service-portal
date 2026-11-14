# iWORKS GLOBAL — five case-study publication release

**Owner request:** publish all five submitted client projects in the real public Case Studies and homepage Selected Work sections, with actual website screenshots where sites are accessible.

## Release scope

This is a real CMS database publication, not a collection of Markdown-only drafts.

The canonical publishable narratives are in `app/launch_case_study_catalog.php`. The CLI importer `scripts/publish_all_launch_case_studies.php` loads the catalog into the existing `iworks_case_studies` table, with `status='published'`, through `iworks_editorial_save_case`. The public site and homepage already use `iworks_editorial_cases(true,...)`; **no duplicate hard-coded cards** are created.

| Client | Case study slug | Evidence |
|---|---|---|
| Amandla Institute | `amandla-institute-formation-hosting` | Owner confirmed institutional incorporation and website/platform hosting; public site reviewed |
| Triply Travels | `triply-travels-company-formation-travel-website-portals` | Owner confirmed incorporation, licences, bank support, public website and separate client/agent portals; live website credits iWORKS |
| Restoration LLC | `restoration-llc-healthcare-website-design-hosting-management` | Owner confirmed site design, hosting and management since 2023; live site reviewed |
| Karis Health Services | `karis-health-services-digital-healthcare-presence` | Owner confirmed work on the website in earlier project history; exact hosting/maintenance scope not asserted; live site reviewed |
| Cornerstonetl, LLC | `cornerstonetl-senior-care-website-design-hosting` | Prior iWORKS portfolio records establish site design and a 2025 domain/hosting/SSL/maintenance renewal; no current-uptime claim |

## Genuine site screenshots

GitHub Actions workflow `capture-case-study-screenshots.yml` opens each HTTPS site in isolated Chromium, takes a first-viewport 1440×900 real screenshot and saves WebP under `assets/branding/case-studies/<case-slug>.webp`. The source/UTC timestamp is saved in the capture manifest. Site screenshots are not generated or manipulated into a fake website. For unavailable sites, the capture is skipped and CMS uses its existing media image or no image.

`app/editorial.php` serves the actual local screenshot only when the CMS has no uploaded cover image. An uploaded, approved CMS cover always takes priority. Screenshots are available to public assets after a normal source-only deploy; no external screenshot API is needed at page load.

All five browser captures completed successfully with HTTP 200 on 14 November 2026. In particular, the Cornerstonetl screenshot was captured from the exact supplied domain, after dismissing a site cookie overlay through the site's own UI in an isolated capture session. Screenshot assets are genuine site renderings, not AI-generated substitutes.

## Production publication checklist

1. Ensure a **current private database backup and isolated restore rehearsal**. The source-only deployer's backup is not a SQL backup.
2. Use the immutable **source-only** publisher `scripts/deploy_admin_mobile_refresh.sh` pinned to this merged application's release commit, first with `--check`, then `--apply`, both with portal and public root. It preserves configs, customer uploads and records; no database migration.
3. Run `php PORTAL/scripts/publish_all_launch_case_studies.php --check PORTAL`. Review any existing published/staged cases and the eligible author ID(s).
4. After the backup is verified, run `php PORTAL/scripts/publish_all_launch_case_studies.php --publish PORTAL --backup-confirmed [--author-id=N]`. This applies only the five case-study rows, never jobs, invoices or financial settings. Existing published stories are not overwritten. Matching drafts are completed; collisions with another slug for the same client are blocked.
5. Independently check the main Case Studies page, all five `/case-studies/<slug>` URLs, homepage Selected Work, genuine screenshot rendering, mobile layouts, and links to published external client sites. Do not send unsupported external "Visit site" links for inaccessible websites.
6. Verify the public 36/36 trust audit, SEO matrix and 24/24 protected endpoint audit still pass.

**Current state is source preparation only until actual cPanel publication and live HTTP verification are observed.** Do not claim GitHub merge equals a published case study or assume screenshots have reached the server simply because they are captured in CI.
