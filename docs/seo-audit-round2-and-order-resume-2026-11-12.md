# iWorks second SEO audit and order-checkout continuation
Audit date: 12 November 2026
Primary marketing domain: https://iworksgt.com
Portal: https://portal.iworksgt.com

## Summary and evidence

The previously requested domain separation exists in merged source but is not fully verified on production. Live HTTP retrieval currently shows the apex homepage redirecting to the portal, while some portal service URLs redirect towards an apex URL that the web fetcher cannot retrieve. This is a **critical potential routing/indexing blocker**, not a successful completed migration. Verify with `scripts/audit_public_seo_live.sh` and cPanel redirect diagnostics. The web fetcher's inability to retrieve some URLs does not by itself establish a universal 500 error for every visitor or Googlebot.

Public Services currently exposes 14 catalog items. Sampled business-name registration, company, trademark, website design, domain hosting, and incorporated trustees pages on the portal. Several pages had one-sentence descriptions and repeated generic bullets. The launch-company page showed a legacy template mentioning banking plus a placeholder support phone and Lagos address — this does not match the intended exclusion of banking or the modern site templates, and is evidence of inconsistent deployment versions. The code has a newer version, but live consistency is unverified.

Google Search Console links, clicks, ranking reports, crawler logs, real-device Lighthouse results, and backlink-index datasets have not been connected; do **not** claim exhaustive backlink coverage, site-wide Core Web Vitals measurements or first-page Google rankings.

## Order journey — root cause and implementation

Previously public service-detail CTA linked to login without a service slug; successful Google/password login, OTP, or registration always redirected to dashboard. User's original service selection was discarded.

New flow:
1. Public service-detail `Continue to order` passes the published service slug as `service` parameter to the **portal login origin**.
2. Portal validates the slug against **active, public services**, then stores a single pending order intent in its session (60-minute expiration). No arbitrary return URL is accepted.
3. Google OAuth, normal password login, email OTP and new-account registration redirect a customer to `jobs?action=new&service=<slug>`. Normal login without a pending selection still goes to dashboard.
4. Portal validates the slug again against the active checkout catalog and preselects the matching service in the cart. The buyer may add/remove other services, edit requirements or choose payment method.
5. Only when buyer confirms checkout does the existing order handler create a job/invoice. It clears the pending intent after a successful order creation, then sends the customer to invoice/payment. **Authentication never creates an order or charges a card.**
6. A failed checkout retains the pending selection and shows an error so the buyer can retry. Logout clears pending intent.

No payment-gateway, job, invoice, customer, or database schema code changed for this feature. Real end-to-end integration needs a controlled portal test account and a configured payment method, and **must not automatically charge a live customer**.

## SEO round two — findings, changes, remaining tasks

| Severity | Problem observed | Implementation/next step |
| --- | --- | --- |
| Critical | Apex website still redirects to portal in sampled live checks; multiple service endpoints not fetchable through web inspection | Finish cPanel domain-root split and remove conflicting external redirects; run read-only 22-URL audit |
| Critical | Public sitemap and robots inaccessible on apex through fetcher | Verify 200, valid XML/text and apex URLs after domain fix before Search Console submission |
| High | Individual product pages have extremely short/generic descriptions | Add unique, useful three-step buyer guides and FAQ explanations for all 14 published service slugs; CMS detail copy still needs editorial review |
| High | Website homepage generic messaging | Improve default H1/intro to Nigerian business registration, remote design and directly linked services; retain actual custom CMS text |
| High | Interlinking weak from service product pages | Add related published services within the same category using real catalog data |
| High | Multiple indexed portal login/auth URLs possible | Send `X-Robots-Tag: noindex, nofollow` on portal account and OAuth routes; canonical public URLs point to apex |
| Medium | Service and blog pages without structured breadcrumbs | Add valid JSON-LD BreadcrumbList matching user-facing navigation |
| High | Trust issues: legacy launch-package copy mentions banking, placeholder contact/address on one page, outdated templates | Verify deployment, fix CMS marketing text, compare actual business details and eliminate stale template/forwarding caches |
| High | Thin/placeholder leadership and case-study pages | Replace with confirmed real management details and consented case studies, no invented project outcomes |
| Medium | Lack of backlink visibility | Connect Search Console, export Links, categorise referring domains and anchors, seek legitimate relevant citations |
| Medium | Performance/interaction only source-audited | Test Core Web Vitals (LCP, INP, CLS), Safari/Chrome mobile sizes, checkout and OAuth deep-link preservation |

## SEO content map

| Search topic | Landing URL |
| --- | --- |
| how to register a business in Nigeria; register business name CAC | /services/business-name-registration-cac-nigeria |
| how to register a company in Nigeria; company incorporation | /services/company-registration |
| website design Nigeria; website design remotely | /services/website-design-development |
| register trademark in Nigeria | /services/trademark |
| NGO / incorporated trustees registration | /services/incorporated-trustees |
| register social enterprise / limited by guarantee | /services/social-enterprise-ltd-by-guarantee |
| managed web hosting, domains and business email | /services/domain-web-hosting, /services/email-list-smtp |
| SEO agency and social media marketing Nigeria | /services/seo-smm-digital-marketing |
| logo and graphic design in Nigeria | /services/graphics-design |
| remote US LLC formation support | /services/form-your-u-s-llc-in-minutes |
| business launch package Nigeria | /services/launch-a-company-package |
| business and digital skills training Nigeria | /services/pro-training |
| vehicle and asset tracking Nigeria | /services/vehicles-assets-tracking |

The editorial page copy avoids unsupported guarantees, fabricated client results and false government affiliation. CAC guidance links to the official CAC iCRP FAQ: https://icrp.cac.gov.ng/support/faq/.

## Redirect migration and Google ranking implications

Google recommends that site moves be prepared and tested, legacy URLs redirect directly to matching new pages using permanent redirects, internal links point to new canonicals, and the new sitemap is submitted while rankings and crawl status are monitored. See https://developers.google.com/search/docs/crawling-indexing/site-move-with-url-changes. Do not redirect all former service URLs to a generic homepage and do not leave portal/app and apex/public pages competing for canonical indexing. Rankings can fluctuate during the move.

## Live verification gate

After deploying the reviewed merged source and confirming cPanel apex/portal document roots, run:

```bash
bash scripts/audit_public_seo_live.sh
```

This read-only crawler tests the homepage, services index, **all 14** published service URLs, About, Contact, Insights, FAQ, sitemap and robots. It reports status, redirect count, final host, title, canonical and mobile-viewport hints. A failed audit must block claiming a completed SEO launch.

Manually test:
- Google login/signup from `/services/company-registration`, from `/services/business-name-registration-cac-nigeria` and from `/services/website-design-development`.
- Check the selected item is preselected in client checkout after OAuth/password/OTP, and can be removed/edited before purchase.
- Complete a low-risk checkout in a permitted test/sandbox payment setup and verify invoice. Avoid live charges unless the test is intentionally authorised.
- Check mobile Safari, Android Chrome, focus navigation, cart totals and input accessibility.
- Validate canonical and breadcrumb JSON-LD on production HTML.
- Submit apex sitemap via Google Search Console. Monitor Page Indexing, Performance and Links. Verify official Google Business Profile information.

Never claim that technical metadata changes guarantee search ranking; market competition, search quality, authority and indexing remain external factors.
