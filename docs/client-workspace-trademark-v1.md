# iWorks Customer Workspace — Trademark Sprint (2026-11-12)

## Architecture
Public marketing remains https://iworksgt.com, with authentication and customer applications at https://portal.iworksgt.com. Existing staff/admin screens and the operational job/invoice/payment pipeline are retained. Customers now see a simplified New Application, My Applications, Billing, Profile, Support navigation rather than the staff dashboard.

A direct login opens New Application. A login initiated from a public Trademark service link goes directly into the trademark application after Google OAuth, email login, or OTP.

## Trademark requirements
- Nigeria is the filing jurisdiction in this release, including for diaspora customers. Do not imply that multi-country filings are included.
- Nice Classification: 45 classes (goods 1–34, services 35–45), see official WIPO https://www.wipo.int/en/web/classification-nice/
- Default USD 185.00 **per distinct class**. Three classes = USD 555.00. Admin edits under Settings > Business Rules > Trademark fee in USD/class. Changes affect new invoices, not existing invoices.
- Customer chooses USD or NGN. NGN total = class count multiplied by USD class fee multiplied by existing iWorks-configured NGN/USD FX rate; this rate is configured by finance, not automatically live interbank FX. The rate and unit price are locked into the invoice at checkout.
- For this first release, the product-specific amount is invoiced with zero additional tax. Any statutory costs or add-ons require explicit pricing policy and customer disclosure before adding them.
- Mark name, type, Nigeria, Nice classes, owner name/email/phone/address, description, and logo for logo/combined marks. Artwork JPG, PNG, or WebP <=4MB is saved outside public web roots and delivered only after verifying account ownership.
- Draft: user can edit or delete, no invoice. Review: user confirms class and buyer details. Payment: one existing job/invoice is generated transactionally; duplicate submissions cannot create a second charge. Invoiced applications are locked against repricing or deleting, and invoice shows existing payment status. A draft may be kept indefinitely; login does not create orders.
- Existing Stripe, Flutterwave and Paystack **hosted checkout** is reused for card payment, when enabled in settings. iWorks does not store card numbers or CVV.

## Saved cards — boundary
The new Billing screen shows invoices and links to provider payment pages. It **does not** claim to store a card. A reusable saved-card vault needs provider tokenization, explicit opt-in consent, customer self-service removal and tested API setup (e.g., Stripe Customer + SetupIntent or saved PaymentMethod). Never store raw card credentials.

## Deployment and acceptance
1. First confirm correct cPanel public and portal document roots. Run the combined installer with --check and review redirects before --apply.
2. The reviewed installer backs up modified files, lints PHP, copies the required application files and runs the idempotent dedicated trademark table migration. It preserves existing users, jobs, invoices, payment gateways and client uploads.
3. Verify the public domain stays at iworksgt.com and the portal domain remains separate. Check public trademark price shows USD 185/class in detail, catalog and structured data.
4. Test user flow: new customer, returning customer and Google sign-in; save/edit/delete pending draft; class combinations of 1, 3, 5; private logo upload; currency display; final quote; one invoice; gateway sandbox payment; status after confirmed payment.
5. Test one customer cannot view another customer's draft or logo, and generic cart cannot bypass the trademark class price.
6. Test mobile 320, 375, 390, 430, 768 and 1024px and Safari/Chrome. Confirm Search Console and historical URL redirects separately.
7. Do not claim a production launch until cPanel deployment, schema migration and payment end-to-end checks actually succeed.

## Follow-on milestones
Business registration journey is next **after trademark validation**. Additional countries, regulated classifications, automatic save, provider-managed reusable cards and more service-specific workflows are future scoped work, not shipped features.
