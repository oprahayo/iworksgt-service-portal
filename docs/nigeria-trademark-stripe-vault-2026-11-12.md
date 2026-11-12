# iWorks Nigeria Trademark + Saved Cards — Release Notes

Date: 12 November 2026.

## Trademark jurisdiction

**Nigeria only.** Every customer, including one living abroad, requests filing in Nigeria. The trademark wizard's filing country remains fixed to Nigeria. Do not label this a global or multi-jurisdiction trademark filing tool. The pricing is USD 185 per selected Nice class by default (admin editable), and NGN quotes use the configured finance exchange rate. It is not a live automatic FX rate unless the finance team changes its source. Government fees/add-ons are not silently added.

## Stripe card vault (client role)

The client dashboard now offers **Billing → Saved cards** and a dedicated client navigation item.

1. Customer explicitly ticks an opt-in checkbox and clicks **Add a card securely**.
2. Server creates or looks up the Stripe Customer linked exclusively to that authenticated iWorks account, storing only that opaque Stripe Customer ID and consent time in `stripe_customer_vault`.
3. The user is redirected to a Stripe-hosted Checkout Session in **setup mode**, not a payment transaction. iWorks never collects card PAN, CVV or card expiry in its own forms.
4. When the customer returns, the server independently fetches the Checkout Session and SetupIntent and verifies status, Stripe Customer ID, metadata and user identity. Only then is the payment method marked as displayable for future Stripe Checkout.
5. The user can see masked brand, last four digits, expiry, and remove a saved card; removal checks Stripe ownership on the server and detaches the Stripe PaymentMethod.
6. Existing USD Stripe invoice checkout uses that Stripe Customer and displays eligible saved cards. The customer must still approve each purchase. No off-session or automatic charging feature was introduced.

**Currency limitation:** Stripe-saved cards are for Stripe checkout, which the current iWorks code restricts to USD. NGN invoices currently use Paystack or Flutterwave. A saved Stripe card is **not** automatically available within those gateways; it is a different provider, even if the physical card is issued in naira. An interoperable vault requires a separate provider-supported consent/tokenization design.

**Prerequisite:** Stripe payment gateway must be configured and marked active in the current iWorks admin payment settings, with a valid server-side Stripe secret. When not enabled, Saved cards displays a safe unavailable state rather than collecting any card data. Sandbox and live Stripe customers are separate; do not mix keys/environments.

## Security and operational constraints

- Only role **client** can access the vault.
- Add/remove require CSRF-protected POST; Stripe return URLs alone never make a payment method trusted.
- SetupSession/SetupIntent/PaymentMethod/Customer identifiers are strictly validated and matched.
- No raw card numbers, full expiry input, CVV, or bearer secrets are written to iWorks storage.
- Setup mode creates no iWorks invoice and charges no card; users authorize each checkout through Stripe.
- User can remove their card; iWorks does not add recurring or off-session charges.
- If a Stripe API call fails, no local saved-card record is fabricated.
- Payment operations still require the existing webhook/provider verification path.

## Deployment and validation

Repo branch work is not a production deployment.

1. Verify main domain document root is marketing site and portal subdomain root is app.
2. From the newest merged release, run the read-only `scripts/deploy_public_root_split.sh --check PUBLIC_ROOT PORTAL_ROOT`.
3. After the root check, run `--apply`; the installer deploys all trademark and saved-card code and runs the dedicated idempotent `scripts/migrate_stripe_card_vault.php`. Existing jobs/invoices/clients are not replaced; backups are created first.
4. Verify `https://iworksgt.com/services/trademark` shows **USD 185 / class** (not previous NGN 50,000) and the filing country is **Nigeria**.
5. Use a test client account, Stripe **test mode** keys and a test card to exercise: Add card → Stripe setup → return → masked card list → USD invoice saved card reuse → remove card. Do not use a real card or trigger production payments for acceptance checks.
6. Confirm that NGN checkout still uses its configured gateway and doesn't pretend to support Stripe's vault.
7. Verify that another signed-in client cannot list, confirm setup for, or detach the first client's card.
8. Test mobile widths, keyboard/focus interactions, CSRF failures and Stripe setup-cancelled state.

**Not implemented:** card sharing across Stripe/Paystack/Flutterwave, international trademark filing, payment method vault that independently stores card numbers, automatic recurrent payments.
