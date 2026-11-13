# iWorks customer products and guided CAC registration — launch handoff (2026-11-13)

## Decision: do not duplicate historical product DB records

- **One service-of-record:** The existing `company-registration` ID is retained for CAC registrations. Historical BN, package and Ltd service IDs/jobs/invoices remain untouched. No data migration or deletion.
- **Two customer offers:** The public/client catalogue shows `cac-business-name` ("Business Name (Enterprise)") and `company-registration` ("Private Limited Company (Ltd)"). The former is a virtual slug, not a new DB record; both have clear descriptions and independently computed prices. Both lead to structure-specific pre-payment CAC intake. Public `/services/cac-business-name` resolves through the existing canonical record.
- **One admin price editor:** Admin → Services retains Business Name base, partner increment, existing Ltd base, additional director, managed address and advisory settings. Price is calculated again on the server and tied to the actual structure and add-ons, never trusted from a browser.
- **Specialist exception:** Incorporated Trustees and companies limited by guarantee use reviewed enquiries, not generic self-service invoice checkout. Existing older Ltd-by-Guarantee drafts retain their specialist type rather than silently switching structures.
- **All other products:** Public and portal New Application share the published catalogue policy. Internal, private, inactive, banking-only and retired "Launch a Company Package" offers are not new customer checkouts; historical work remains accessible.

## CAC customer flow

1. **Choose:** Enterprise/Business Name versus Private Ltd, explained in compact original orange/charcoal panels. For Business Name, select sole proprietor or partners. Private Ltd collects shareholders, directors and share details.
2. **Business basics:** Name suggestions (preliminary only), nature and activity; no unsupported automatic CAC name-approval claim.
3. **Contact/office:** Business contact details; own traceable address or verified admin-enabled managed office, price included. Optional director privacy service is advisory only and does not conceal statutorily public information.
4. **Owners:** Exact legal names, DOB, nationality, residence and ID type/number. Optional owner contact fields are collapsed. Add partners or directors as needed.
5. **Secure docs and review:** Save draft before uploading IDs/photos/signatures. Redirect straight to document stage after saving, review missing items and calculated total, then complete invoice/payment. Existing restricted document storage and staff review remain.

The interface has five stages after the choice screen (Business → Contact → Owners → Documents → Review). The stage currently displayed is validated client-side; final completeness remains enforced by PHP. Some required questions/documents cannot be removed because of CAC filing requirements.

## Checkout CSS root cause

The authenticated customer layout short-circuits through `views/partials/client-header.php`, which did not load `assets/css/customer-focused-checkout.css`; the administrative header did, which never applied to the client. PR #47 links the stylesheet in the real client shell, resolving the unstyled product/summary screenshot in source. Verify on live desktop and mobile after deploy.

## Production acceptance checklist

- In cPanel terminal, run the pinned `scripts/deploy_admin_mobile_refresh.sh --check PORTAL_ROOT PUBLIC_ROOT`; after backup and a clean check, run `--apply`. No SQL is executed by this installer.
- As a signed-in client, open New Application. See separate Enterprise and Ltd cards; click each to the correct guided application. Confirm the public Services cards and both detail URLs render, and the old BN URLs redirect to Enterprise.
- Open ordinary Website Design checkout at a wide desktop viewport and at 375px mobile: the designed two-column (desktop)/single-column (mobile) layout and orange CTA must load, with no inline default browser textarea/buttons or horizontal overflow.
- Complete Business Name draft with one proprietor. See mandatory name, contact, owner steps; save draft; land at document upload. Repeat with partnership and check add/remove partner; repeat with Ltd and ensure shareholders/share capital, not Enterprise fields.
- Confirm saved owner files stay attached after editing and cannot be accessed by other clients. Validate empty/invalid ID numbers, different name alternatives and underage/regulated cases with staff. Test upload errors without disclosing internals.
- Verify Business Name base/extra partner and Ltd base/extra director are displayed separately, server-calculated and included correctly on the invoice. Confirm no duplicate historical service/invoice rows were created.
- Test website registration/login, Google consent if configured, and mobile interactions. Verify live chat and booking separately before a broad launch claim.
- Verify static cache/CDN/opcache invalidation where appropriate and compare deployed release SHA to installer pin.

## Verification status

PR #47 merged at `ccd187b0e75b4b2437ce81f5f8b57b8b7fd8d474`, with GitHub Actions PHP lint and complete test suite successful, including new CAC render/choice and catalogue price tests. Production cPanel rollout, real-time CAC approval checks, end-to-end payments, uploaded document permissions and signed-in mobile testing remain **not verified** in this chat.

## Official compliance references

- https://cac.gov.ng/services/business-name
- https://cac.gov.ng/services/company-registration
- https://icrp.cac.gov.ng/support/faq/
- https://icrp.cac.gov.ng/assets/docs/crp-user-guide.pdf
