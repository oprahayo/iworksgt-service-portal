# iWorks Client Workspace — Unified Services and Checkout Refinement
Date: 12 November 2026

## Product decision
Use one customer-history destination: **My Services**. It covers trademark applications, CAC registration, website development, hosting and every other service. The old "My Applications" and "My Orders" navigation labels are combined for clients; internal jobs, invoices and trademark drafts remain separate records for staff processing.

Client navigation: New application; My services (All, Draft, Awaiting payment, In progress, Completed, Cancelled); Billing; Saved cards; Profile; Help.

## Unified model
app/client_workspace.php merges trademark applications with existing jobs; a job linked by trademark_applications.job_id is excluded from the duplicate jobs loop. Website orders and generic orders still appear. Pending trademark drafts remain editable/deletable. Client list requests to the older jobs URL redirect to My Services, except the existing action=new legacy catalog order flow, which remains available.

The overview currently loads the newest 100 jobs with a notice when older jobs exist. Expanding pagination is a later improvement. Staff/finance/agent jobs and admin dashboards remain on original views.

## New application catalogue
Dashboard uses the existing published service records, descriptions, prices, tailored Bootstrap icons and category colors. Search and category filters work client-side. Trademark opens its guided filing wizard. Other products open their existing checkout pending future product-specific mapping. Banking products remain excluded.

## Intake duplication
New trademark wizard checkout calls create_job with an internal trusted option to skip a generic duplicate intake form. The wizard's own mandatory fields and uploaded logo remain the source of the trademark application; customer data must not be retyped into a redundant generic intake.

Pre-existing generated trademark intake forms are retained for administrator review, and are accessible under a collapsed "Older trademark intake record" section of a customer job. They are not deleted or falsely marked submitted.

## Customer job detail
The separate client view preserves payment, comments, file uploads/downloads, optional additional intake forms, status timeline, delivery acceptance/revision, and completed-order feedback via existing secured handlers. Staff job details remain unchanged.

## Billing, profile and invoice
Billing now has a concise lock indicator and Saved Cards entry.
Profile is reorganized into personal details, address, payment preference and password/security sections; existing update action names and all account fields are preserved.
The client invoice is restyled as responsive service summary plus payment selection, with amount due clearly displayed and invoice metadata expandable/printable. Payment logic, provider choices, invoice records and staff invoice behavior are preserved. Provider configuration instructions are hidden from customer views; checkout errors no longer echo masked or full API-key fragments.

## Payment configuration and cross-gateway limitations
The live test screenshot showed an expired Stripe API key. This is a genuine gateway credential/configuration issue requiring an authorized administrator to replace/rotate the secret in iWorks/Stripe; UI code cannot fix expired credentials. Do not paste keys in chat.

Saved cards remain provider-scoped: Stripe tokens enable Stripe USD checkout when supported. A naira-denominated card saved in Stripe cannot automatically be reused inside Flutterwave/Paystack's NGN gateway. NGN saved-card reuse requires separate provider-supported tokenization and explicit consent; raw PAN/CVV must never be stored.

## Verification and production acceptance
- CI lint and customer workspace contract; existing trademark price and OAuth tests.
- Customer sees one My Services navigation entry, no duplicate linked job.
- New application displays actual published products, prices, category filter, search and correct CTA routes.
- New trademark wizard produces USD 185 per class, USD 555 for 3 classes, correct NGN conversion; no second generic intake on new orders.
- Existing old intake record remains accessible for review.
- Website design order appears in My Services with payment, progress, comments and files.
- Client profile saves without clearing fields; admin/finance/agent views unchanged.
- Invoice renders correct totals, print details, methods, and a safe message for invalid API credentials.
- Test responsive widths 320/375/390/430/768/1024 and authenticated checkout with test gateways.
- Run the pinned cPanel installer in --check mode before --apply, confirm roots and backups.
No changes to live configuration, Stripe API keys or production data have been performed in GitHub-only development.
