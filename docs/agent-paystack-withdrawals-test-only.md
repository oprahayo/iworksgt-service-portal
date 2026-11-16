# iWORKS agent withdrawals — TEST ONLY

Status: Draft branch. NOT merged, deployed or enabled for real money.

## Owner-approved rules

| Rule | Implementation |
| --- | --- |
| Currency | NGN, in integer kobo for transfers |
| Minimum | ₦5,000 inclusive |
| Automatically eligible | ₦5,000–₦100,000 inclusive |
| Manual finance review | Individual request above ₦100,000 |
| Anti-splitting | Cumulative withdrawals exceeding ₦100,000 in a rolling 24h window require review |
| Timing | Validate, reserve and initiate eligible TEST transfer on request |
| Old 14-day cooldown | Removed |
| Live transfer | **No live mode in this branch** |

## Code changes

The original PHP/MySQL agent, commission and withdrawal functionality is preserved.

- Separate verified Paystack TEST beneficiary table; account ownership resolved by Paystack.
- Existing profile-bank edits invalidate beneficiary verification.
- Agent-row SQL lock and reserved withdrawal balance prevent concurrent overspending.
- Every withdrawal has one immutable, unique transfer reference and ledger record.
- Finance approves/rejects high-value or split requests; reviewer is recorded.
- Test-only POST transfer initiation. Never automatically repeat ambiguous POST requests.
- Verified signed transfer webhooks set final state. Read-only finance reconciliation uses Verify Transfer.
- Transfer success marks the request paid; failure/reversal releases reserved balance.
- Existing charge.success payment webhooks are unaffected by transfer.* delegation.
- Only earned commission from delivered, paid NGN jobs is currently withdrawable.

## Protecting money movement

Server defaults to OFF. Only one nonproduction setting is accepted:

    IWORKS_PAYSTACK_PAYOUT_MODE=test
    IWORKS_PAYSTACK_PAYOUT_TEST_SECRET=sk_test_...

Place the value in a protected environment, not the repository. The code rejects sk_live_ keys and does not expose a live mode. A future live launch requires separate explicit authorization, further security work, and a new code review.

## Schema migration

Database changes are CLI-only and require a verified private backup:

    php scripts/migrate_agent_paystack_withdrawals.php --check
    php scripts/migrate_agent_paystack_withdrawals.php --apply --backup-confirmed

The migration creates agent_payout_beneficiaries and agent_withdrawal_transfers and writes the approved business-rule defaults. It does not enable transfers.

## Existing webhook destination

Paystack webhook endpoint is routed through:

    index.php?page=payment_webhook&provider=paystack

Transfer webhooks are signed separately using the test payout key, with reference, amount, currency and test environment verification. Existing checkout events retain normal logic.

## Minimum test checklist

1. Test ₦4,999, ₦5,000, ₦100,000 and ₦100,000.01.
2. Test two overlapping withdrawals against the same agent balance.
3. Test ₦60,000 followed by ₦50,000 within 24 hours.
4. Reject missing/changed beneficiary and non-NGN earnings.
5. Test invalid bank code, bank account, suspended agent and missing migration.
6. Replay success webhook twice, then simulate failed/reversed and out-of-order events.
7. Simulate API timeouts and ensure only the original reference is reconciled.
8. Confirm sk_live_ never initiates transfers and OFF never calls Paystack payouts.
9. Check existing customer charge.success behavior and legacy manual records.

Smoke commands:

    php tests/agent_paystack_withdrawals_contract_test.php
    php -l app/agent_withdrawals.php
    php -l app/domain.php
    php -l index.php
    php -l scripts/migrate_agent_paystack_withdrawals.php
    php -l views/withdrawals.php

## Outstanding before any live rollout

- Test database and Paystack test-account integration verification, including production-like concurrency.
- Secure reauthentication/hold after payout-bank changes, risk controls, fraud checks and payouts rate limits.
- Encryption and retention policy for bank account numbers, audit trail and finance alerting.
- Automated queue and reconciliation jobs for interrupted requests and settlement delays.
- Commission reversal/refund handling, immutable accounting ledger and funding-source safeguards.
- Registered Paystack business account, transfer approval settings, sufficient balance and production webhook review.
- Independent security review and explicit separate authorization before enabling any real-money transfers.

This draft must not be described as live, deployed or production-ready.
