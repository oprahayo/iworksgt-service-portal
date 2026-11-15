# iWORKS GLOBAL — Case Studies design correction (15 November 2026)

## Owner feedback / approved direction

The previous "Our Work" gallery was rejected as visually derivative, dark, overly styled, inconsistent with the existing iWORKS site and lacking motion. The owner explicitly requested deleting the small **SELECTED WORK** label and **Real projects. Real progress.** heading, using **Case studies**, restoring the site's own background and typography, removing upward arrows and generic AI-like design effects, and adding meaningful interactions. The other website was a conceptual reference only, **not a design template**.

## Implemented

- Homepage: replaced the label and tagline with a single **Case studies** heading and one-line contextual introduction; retained three genuine projects and links; images and typography reflect iWORKS, not a generic agency template
- Case study listing: renamed to **Case studies**, removed faux browser/toolbars, dark charcoal gallery, large staged metrics/count badge, heavily styled CTA and redundant labels; changed to original editorial rows that alternate clean project screenshots with client details on the site's own warm-white canvas
- Individual case studies: aligned with iWORKS' existing Inter font, sizes, background, width and natural reading hierarchy; uses a large real screenshot without imitation browser chrome, no decorative upward arrows or gradient result cards
- Links: retained per-project **Read case study** and **Visit website** destinations, scoped to approved client URLs, with `rel="noopener noreferrer"`
- Effects: subtle scroll-entry fade/slide that activates only with IntersectionObserver, works on all three surfaces (homepage, listing, case detail), short image hover scale and native underlines. Reduced-motion devices and JS-disabled clients see everything with no animation.
- Filters: category selection remains functional, accessible, minimal text-underlined navigation instead of decorative badges
- Preservation: existing five published editorial records and real WebP client screenshots; no CMS DB updates, migrations, new customer/account data or permission changes
- Tests: verify absence of rejected headings and faux browser markup, presence of exact client URLs, 5 real screenshot assets, keyboard-friendly filters, image/mobile typography and reduced-motion support

## Verification and release

Use the existing static iWORKS visual QA workflow to compare current production, new 1440px desktop, 390px mobile and detail preview. Run CI on PHP 8.2/8.3 and JS filter behavior before merging.

**Do not publish source changes into cPanel until preflight and recoverable backups are confirmed.** Source-only production publication through the existing safe pinned deployer, followed by the independent public portfolio audit and existing security/SEO checks. This is a UI/UX-only improvement; do not republish or re-import live case-study DB records.
