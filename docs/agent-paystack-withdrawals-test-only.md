# iWORKS agent commission withdrawals — live rollout runbook

**Status: Draft pull request #160, not merged or deployed. The requested LIVE webhook is registered; live transfers remain OFF pending Paystack sandbox and account readiness.**

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

- GitHub CI run 43935055378 passed PHP 8.2/8.3 and MySQL 8 database/concurrency tests at code commit 47120a8e24ae0a045bdf811fa9b34c7933cd0f99. Local MariaDB and signature tests also passed. **Actual Paystack sandbox, provider webhook delivery and real-bank settlement remain unverified.**
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


## Latest operator handoff — 17 November 2026 (Europe/Madrid)

- Code saved on the existing PR branch at `47120a8e24ae0a045bdf811fa9b34c7933cd0f99`; all three GitHub CI jobs passed: https://github.com/oprahayo/iworksgt-service-portal/actions/runs/43935055378.
- The production cPanel session is available. Actual portal path: `$HOME/public_html/portal.iworksgt.com`. Private source checkout: `$HOME/iworks-paystack-stage`, fast-forwarded to that commit.
- `backup_agent_payout_release.sh --prepare` completed on the production host. Encrypted SQL and full portal-file archive are in the private `$HOME/iworks-release-operations/agent-payout-backup-20261116T234844Z-*` directory. `RESTORE_VERIFIED` and `SHA256SUMS` record successful completion. SQL was restored to an isolated cPanel database and the users, jobs, invoices, job_payments, commission_payouts and withdrawal_requests table counts matched. The rehearsal database was removed. The encryption key remains separately protected under `$HOME/iworks-release-keys`; no key was exposed or committed. Off-server replication has not been performed.
- No production source was installed, no migration was applied, no cron was installed, and no payout mode was enabled. PR remains draft/unmerged because the requested provider integration gates are not yet satisfied.
- Blocking next action: owner signs into the open Paystack dashboard. Then verify Registered Business/Transfers readiness, obtain test credentials through the authorized private server workflow, exercise sandbox transfers and provider-signed webhook delivery, configure the exact LIVE webhook URL, and confirm live funding covers withdrawals plus fees. The last successful live `/balance` query showed funding below the minimum withdrawal.
- After those gates, merge only the verified PR revision, deploy only its portal changes with a rollback copy and maintenance window, apply the additive migration, verify private HTTP denial and valid/invalid signed callbacks, install the serialized recovery worker, and arm live payouts using the existing server-held live credential. Never claim live activation from successful code tests alone.

- Deployment compatibility: all nine existing portal files changed by this PR match the repository main baseline. Direct HTTP access to `/app/config.php`, `/app/agent_payout_runtime.php` and `/database.sql` returned 403. The Paystack login page was confirmed again and left open for the owner.

## Authenticated continuation — 17 November 2026

This entry supersedes the sign-in blocker and webhook-registration status above.

### Completed and verified

- Signed into the existing **iWorks GT** Paystack business (integration 111423). Dashboard shows **Approved**, but also an explicit **dormant account** notice. Its compliance screen lists Business Name registration documents; the current dormant notice still requires account readiness resolution.
- Changed the LIVE webhook from `https://iworksgt.com/veri/portal/paystack_webhook.php` to **`https://portal.iworksgt.com/index.php?page=payment_webhook&provider=paystack`**. Paystack displayed “Update successful!”; navigating away and back confirmed the saved value. The old endpoint returned HTTP 403 on a GET probe. The callback URL was not changed.
- Exercised the existing production webhook with a harmless, unrecognized event, signing its exact JSON bytes using the existing server-held live key. Valid HMAC-SHA512 returned **HTTP 200 / Event ignored**. Missing and invalid signatures returned **HTTP 403 / Invalid Paystack signature**. These were operator-generated HTTP probes, **not provider-delivery evidence**, and did not alter payment records.
- Added `scripts/paystack_sandbox_acceptance.php` at code commit `9e6154ed09552d5cbd427e1cd3bda465c0ed0220`. It refuses live credentials and non-isolated database names, seeds only synthetic commissions/users, exercises the actual application functions with real test APIs, and records signed receiver results. CI run **43984455281** passed all three jobs: https://github.com/oprahayo/iworksgt-service-portal/actions/runs/43984455281.
- Created a private source snapshot at `$HOME/iworks-paystack-sandbox` with an isolated cPanel database matching `*_iwqa_[8 hex digits]`. It has no production records. Its test secret was obtained from the authorized dashboard and passed through hidden terminal input into a 0600 runtime file; no value was printed or committed. Synthetic email notifications were disabled.
- Exercised its temporary public receiver with the candidate handler: unsigned and invalid signatures returned **403**, a valid signed unknown-reference event returned **200**, and the private event log records those three outcomes. Corrected the temporary directory's creation permissions before acceptance. This did not change production application source/schema.
- Real Paystack **test-mode bank resolution** succeeded for the currently documented Zenith fixture (`057`, `0000000000`, account name `Test`). The older First Bank fixture (`011`) did not resolve. No substitute real beneficiary was used.

### Exact external blockers

1. The real test-mode `POST /transferrecipient` for that successfully resolved Zenith fixture returned **HTTP 403**, `status:false`, `code:unknown`, message **“Sorry, we can't make the transfer to this recipient at the moment”**, with `meta.nextStep` **“Try again later”**. No matching existing recipient was found. A later diagnostic request returned the same rejection. This is a provider response, not an application signature error. Its underlying cause is not yet established; do not attribute it definitively to dormancy.
2. LIVE dashboard transfer balance is **NGN 0**. The TEST balance (NGN 68,747) is separate and cannot fund live withdrawals. Live funding must cover actual withdrawals plus fees.
3. Paystack's **“Confirm transfers before sending” OTP confirmation remains enabled**. Automatic payouts cannot be claimed operational until the approved transfer-approval arrangement is completed. Do not disable OTP or weaken another account safeguard without explicit action-time confirmation.
4. Actual provider webhook delivery remains unverified. The authenticated webhook-events API returned an empty list for `charge.success`; the historical successful-payment detail had no webhook-redelivery control. No payment or real-money transfer was created merely to generate an event.

### Safe stopping state and evidence

- **Zero sandbox transfer rows and zero registered sandbox beneficiaries**; no sandbox transfer was submitted because recipient registration failed. No live transfer was initiated.
- Sandbox mode was changed to **OFF**, and the temporary public receiver directory was moved into the private `$HOME/iworks-paystack-sandbox/disabled-receiver` directory. Its former public path was `/payout-sandbox-a1597f3a67227f0c/`. The existing TEST webhook setting was never changed (`https://iworksgt.com/wp-json/ticket/v1/verify-payment`).
- Private evidence: `$HOME/iworks-release-operations/paystack-acceptance-checkpoint.json`; original provider response: `$HOME/iworks-paystack-sandbox/storage/provider-recipient-result.json`; signature results: `$HOME/iworks-paystack-sandbox/storage/payout-sandbox-events.jsonl`. These contain no secret keys. Private configuration and fixture-password files must stay private.
- Production code and schema remain unchanged, payout mode is not activated, and no recovery cron was installed. PR #160 remains draft/unmerged because the user's required sandbox gate has not passed. The restore-tested encrypted backup described above remains available; off-server replication remains outstanding.

### Resume without repeating completed work

Resolve Paystack's test-recipient rejection, account dormancy and funding first. Preserve the saved LIVE webhook. Restore the isolated receiver and test runtime only for a new controlled sandbox attempt; use the same fixture/request token and inspect its ledger before any submission. If registration succeeds, temporarily register the isolated TEST webhook, exercise the test withdrawal, verify provider-signed delivery plus read-only reconciliation and duplicate-request reuse, then restore the original TEST webhook and retire the receiver again. Never route test events or fixture records into the production ledger.

After the required provider tests pass, review the final exact-head CI and merge. Deploy only reviewed portal files with the verified backup/rollback copy, apply the additive migration, install the serialized recovery worker, and complete agent/Finance acceptance. Keep new payouts OFF until live funding, account status, approval settings and live signed delivery are verified. The existing protected activation script can reuse the current checkout credential without exposing it.
