# iWORKS agent commission withdrawals — live rollout runbook

**Status: Draft pull request #160, not merged or deployed. Live transfers are not currently activated.**

## Approved business policy

- NGN payouts from verified and cleared earned agent commissions only.
- Minimum withdrawal: NGN 5,000 inclusive.
- Automatic processing upon request for NGN 5,000–100,000, after validation.
- Requests above NGN 100,000 and split requests above the rolling 24-hour NGN 100,000 threshold require Finance approval.
- New or changed LIVE payout banks require the agent's current password, successful Paystack bank resolution, and a 24-hour hold.
- Agent ledger reservations are serialized per account; each transfer has a permanent unique reference.
- Transfer-success webhooks/verification, not a POST response, mark a request as paid.

## Integration and security

The system defaults to OFF. Sandbox keys cannot send live funds. Live mode is allowed only via a protected, untracked application file:

    app/agent_payout_runtime.php

That file is created by the private CLI and is excluded from Git. It must have 0600 permissions and must never be served by HTTP. The app/.htaccess protection must be verified on the target server.

**Paystack LIVE webhook (not a public key):**

    https://portal.iworksgt.com/index.php?page=payment_webhook&provider=paystack

Use the exact configured public portal URL if different. Paystack sends transfer.success, transfer.failed and transfer.reversed events. Existing charge.success events continue through the existing checkout handler. The webhook signature is verified with the active payout secret, and amount/currency/domain/reference are cross-checked.

Before live activation, confirm inside Paystack: Registered Business approval, live Transfers access, NGN balance sufficient for payouts **and fees**, live webhook signing/delivery, and approval settings/OTP appropriately configured. Live transfer requires an sk_live_ secret; never paste it in ChatGPT or GitHub.

## Deployment procedure

1. Deploy the reviewed PR through the established cPanel release process. Avoid overwriting unrelated public or portal changes. A draft branch should not be copied into a production directory blindly.
2. Make, restore-test and retain a secure off-server backup of the live MySQL database. Confirm all jobs/commissions/withdrawals are included.
3. Run the backup-gated migration from the **actual portal installation**:
   
       php scripts/migrate_agent_paystack_withdrawals.php --check
       php scripts/migrate_agent_paystack_withdrawals.php --apply --backup-confirmed

4. Run syntax and contract tests and the read-only preflight:
   
       php tests/agent_paystack_withdrawals_contract_test.php
       php scripts/configure_agent_paystack.php --check

5. Reconcile all existing sandbox history or legacy pending requests in this database. The preflight blocks activation while those exist.
6. After confirming Paystack readiness, invoke the CLI with a live secret provided through a protected environment variable (not a command argument). This executes GET /balance only, writes private runtime config and **does not initiate a transfer**:

       IWORKS_PAYSTACK_LIVE_KEY=... IWORKS_PAYSTACK_LIVE_APPROVAL=I_HAVE_VERIFIED_REGISTERED_BUSINESS_WEBHOOK_AND_TRANSFER_APPROVALS php scripts/configure_agent_paystack.php --activate-live

   For shell history safety, use `read -rs` to input the key; do not type the value literally into the command.
7. Check LIVE display in the Withdrawals dashboard. Each agent must verify their own LIVE bank account; previous TEST recipient codes cannot be reused.
8. Validate a tightly supervised first real withdrawal, webhook delivery, fee debit, bank receipt, and finance reconciliation. Stop immediately if a transfer is ambiguous; verify by reference, never repeat the POST.
9. Disable further new live transfer initiation if anomalies appear:
   
       php scripts/configure_agent_paystack.php --disable

   Disabling prevents new transfer submissions but retains the private live signing key exclusively to verify final Paystack webhooks for already submitted transfers. Disabling never reverses a submitted transfer.

## Validation and outstanding production work

- CI PHP lint and contract checks passed; **no connected test database, real-bank integration, or live transfer has been verified through ChatGPT**.
- Production readiness also requires independent fraud and access review, monitoring and alerting, secure backup restore rehearsal, transfer ledger audit, and refund/chargeback handling.
- Do not set production mode simply by shell `export`; Apache/PHP-FPM does not automatically inherit an interactive terminal session environment. The protected CLI-generated runtime file is used specifically to avoid that problem.

## Release references

- PR #160: https://github.com/oprahayo/iworksgt-service-portal/pull/160
- Paystack transfers: https://paystack.com/docs/transfers/single-transfers/
- Paystack webhook requirements: https://paystack.com/docs/payments/webhooks/

## Continuation audit — 16 November 2026

The existing implementation was continued on PR #160; no replacement architecture was introduced.

Implemented in the continuation:

- Request tokens are persisted under a unique index, scoped to the agent. Retrying the same form returns the original withdrawal; changing its amount is rejected.
- Actual database tests exercise concurrent reservations, concurrent dispatch, duplicate callbacks, reversals, split withdrawals, timeouts, manual approval and bank verification. Network behavior is simulated at a dedicated Paystack client boundary; these are **not Paystack sandbox results**.
- Verification matches reference, integer kobo amount, currency, environment, recipient, bank/account suffix and provider transfer ID. `abandoned`, `blocked` and `rejected` terminal statuses release the reservation after verified reconciliation.
- New/changed banks require rate-limited password verification; inactive/suspended/deleted agents cannot receive queued payouts. Profile editing can no longer bypass bank verification.
- Finance identity is checked in the service layer. Legacy NGN commission controls cannot mark payments paid or reopen paid commissions after migration; settlement uses the withdrawal ledger.
- Reconciliation continues while new payouts are OFF. `scripts/process_agent_payouts.php --run` recovers only never-submitted queued transfers and verifies ambiguous transfers without repeating POST requests. Install with a private cron log and `flock` after deployment; `--check` is read only.
- The existing webhook route requires POST and limits payloads to 1 MiB. Legacy HMAC-SHA512 remains supported; configured `whsec_` signing secrets require timestamped signatures with a 300-second replay window and support rotation overlap. Checkout uses the Paystack method's Private/Extra Key for a `whsec_` secret; payout runtime uses `live_webhook_secret`/`test_webhook_secret`. Keep them consistent for the shared endpoint.
- Migration checks now fail when columns, unique indexes or transactional engines are missing. Fresh installation, upgrade and repeated application are tested.
- `scripts/backup_agent_payout_release.sh --prepare PORTAL_PATH` creates encrypted SQL and full portal-file backups outside document roots, restores the SQL in an isolated cPanel database, verifies key table counts and removes the rehearsal database. It never changes production source, schema or payout mode. Keep the backup key separately; copy encrypted backups to the operator's approved off-server storage before rollout.
- Live activation can reuse the existing server-side checkout key with `--use-existing-checkout-key`; secrets need not be copied through chat. The live balance gate now rejects funding below the minimum withdrawal. Fees and account approval readiness still need independent verification.

Verified production observations (read only): PHP 8.3.36; Paystack checkout enabled with a live credential; live `GET /balance` successful; **NGN transfer funding below the minimum withdrawal before fees**; no unsettled legacy withdrawals; new payout source/tables absent. Paystack dashboard presents a sign-in screen.

Outstanding release gates: authenticated Paystack dashboard; Registered Business/Transfers and approval configuration; test-mode transfer + actual provider webhook delivery; sufficient live NGN funding plus fees; reviewed deployment from the merged commit; production migration, HTTP signature probes, Finance/agent dashboard and scheduler acceptance. No live transfer has been initiated. Do not merge or arm live payouts until these gates pass.
