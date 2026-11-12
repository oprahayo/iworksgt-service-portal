# iWorks Global: Google sign-in, organic search and mobile audit
Audit date: 12 November 2026
Live URL: https://portal.iworksgt.com/

## Evidence and limits
Reviewed public homepage, Services index (14 published services), business-name registration, company registration, trademark, incorporated trustees, hosting, the website-development URL (challenge page observed), About, Management, Case Studies, Insights, an article, FAQ, Contact, Terms and Privacy. Checked the PHP and CSS source, search results, Google OAuth and Google documentation. These findings are not a full Googlebot crawl, Lighthouse field audit, backlink-index export, keyword-volume study or ranking guarantee.

## Confirmed Google sign-in defect
An existing browser tab shows Google Error 400: redirect_uri_mismatch. The sign-in start endpoint sends:
https://portal.iworksgt.com/index.php?page=google_callback

Required Google Cloud Console fix:
1. Visit https://console.cloud.google.com/auth/clients and select the iWorks Web application OAuth client already in use.
2. Add the exact callback above to Authorized redirect URIs, including https, hostname, path and query.
3. Save; check Audience so intended accounts are allowed (add test user if Testing).
4. Run php scripts/check_google_oauth.php from the live portal directory; do not expose credentials.
5. Complete a real sign-in on desktop and mobile and verify the callback lands in dashboard. Only then mark as fixed.

No website code change by itself can authorize a missing URI in Google's Cloud Console. Never share the Client Secret, tokens, or an OAuth code.

## Public page audit and remediation
| Page / area | Observed finding | Action |
| --- | --- | --- |
| Home | Generic positioning and title | Use CAC Nigeria and remote website design search-intent descriptions; link to relevant services |
| Services | 14 published entries; several thin descriptions | Strengthen product detail descriptions, preserve current prices/CMS data; deploy existing grid installer |
| Business Name Registration | Solid requirements but little query-led structure | Add how-to guide, official CAC resource and focused title |
| Company Registration | One-sentence description with generic bullets | Add real CAC incorporation steps and focused FAQ |
| Website Design | Challenge page on one web fetch; Googlebot outcome unknown | Add original web design Nigeria and remotely relevant content, test crawlability |
| Trademark / Trustees / Hosting | Short or repeated generic text | Edit CMS descriptions for deliverables, process, exclusions and requirements |
| Management | Unrelated placeholder academy leadership copy | Replace with approved real profiles or unpublish until accurate |
| Case Studies | Public page says no projects published | Publish permissioned real work with specific outcomes, never invent clients |
| Insights | Few short posts | Publish original registration and digital business guides, cite official rules |
| Contact / Footer | Inconsistent visible email, phone and address across pages | Confirm one official NAP and synchronize CMS, remove placeholders |
| Auth dialog | Older cached extraction displayed hidden login H1 | Deploy auth heading fix and inspect rendered DOM |
| Sitemap / robots | Code exists, live XML not confirmed by fetch | Check 200, validity, canonical hostname; submit to Search Console |
| Terms / Privacy | Basic legal pages exist | Legal review and keep accessible, not a primary ranking target |

## Keyword to landing page map
| Priority | Target search terms | Existing landing page |
| --- | --- | --- |
| P1 | register business in Nigeria; how to register business in Nigeria; CAC business name registration online; register Nigerian business from abroad | /services/business-name-registration-cac-nigeria |
| P1 | register company in Nigeria; CAC company incorporation; limited company registration remotely | /services/company-registration |
| P1 | website design Nigeria; design a business website in Nigeria; website design remotely; remote WordPress website developer | /services/website-design-development |
| P2 | register NGO Nigeria; incorporated trustees Nigeria | /services/incorporated-trustees |
| P2 | trademark registration Nigeria; register a trademark | /services/trademark |
| P2 | domain registration Nigeria; web hosting Nigeria; business email hosting | /services/domain-web-hosting |
| P2 | SEO services Nigeria; digital marketing agency Nigeria | /services/seo-smm-digital-marketing |
| P3 | iWORKS GLOBAL LTD; iWorks Global Nigeria | Home, About, Google Business Profile |

Use keywords naturally in titles, H1s, contextual copy and anchor links. Avoid keyword stuffing, doorway pages, claims of CAC affiliation and promises of approval or Google rank one.

## Editorial publishing queue
1. How to register a business name in Nigeria: official CAC steps and document checklist.
2. Business name versus limited company in Nigeria: practical differences.
3. Registering a Nigerian company from abroad: scope and verification.
4. Business registration costs: distinguish statutory CAC fees from iWorks service fees, refresh when changed.
5. How to choose a website design company in Nigeria: scope, portfolio, accessibility, hosting and maintenance.
6. How to design a mobile-friendly Nigerian business website remotely.
7. Trademark filing in Nigeria: what a founder should know.
Cite official CAC registration references at https://cac.gov.ng/services/business-name and https://cac.gov.ng/services/company-registration.

## Backlinks and local SEO
Sampled public search results cannot establish an exhaustive backlink inventory or referring domain count. Use Google Search Console Links export and optionally a dedicated crawler to classify real referring domains, anchors, broken links and potentially harmful placements. Avoid buying or automating backlinks.

Pursue authentic editorial citations: approved client case studies, partner websites, Nigerian entrepreneurship publications, relevant business organisations, legitimate directories and verified Google Business Profile. Keep company name, mailing address, phone and support emails aligned across sites and profiles.

## Mobile and performance acceptance tests
- Test iPhone Safari/Android Chrome at 320, 375, 390, 430, 768 and 1024px.
- Ensure no horizontal scroll, menu clipping, overlapped CTA/pricing or inaccessible modal.
- Confirm 44px tap targets; 16px inputs to avoid Safari autozoom; search/filter/grid/list, ordering and enquiry interaction.
- Verify login completes after Cloud Console repair, including iPhone redirect and session.
- Run PageSpeed Insights on Home, Services, one service and one blog page; record LCP, INP, CLS, TTFB. Optimize based on data, not assumptions.
- Check production HTML: unique useful titles, correct canonical URLs, mobile viewport, actual service-specific descriptions, JSON-LD and XML sitemap.

## Deployment and verification
The GitHub SEO branch improves metadata, service guides and responsive guide CSS; this is not live until merged and installed. The previous combined release has sitemap/robots/mobile navigation code but may not be installed on the server. Do not deploy older pinned scripts after new changes; back up PHP/CSS, syntax-check, deploy, validate the real HTML and clear caches. Database records, client documents, orders, payment configuration and personal data stay untouched.

No legitimate agency can guarantee first-page Google results. Track actual clicks, impressions, queries and index coverage with Search Console after deployment.
