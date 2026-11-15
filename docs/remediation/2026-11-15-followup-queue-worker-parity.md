# Follow-up notifications — queue-worker parity and readiness (15 November 2026)

## Background

The merged 30-day support-chat release uses the existing locked `run_scheduled_operations.php queue` worker, which delivers OTP, in-app notification email and visitor chat email. The documented alternative `cron_support_chat.php --run` was not equivalent: it omitted OTP delivery and lacked the shared queue lock. The main worker also reported status=ok when the email transport was disabled, and disabled transport could consume OTP retry budgets.

## Change

- Preserve queued OTPs without claiming or exhausting attempts while email transport is disabled.
- Mark the main queue task `degraded` with a nonzero exit when transport is disabled; keep chat expiry processing independent of mail delivery.
- Make the alternative CLI worker handle OTP plus both notification email queues; share the main worker's `cron-queue.lock` to prevent two scheduled worker entrypoints processing concurrently.
- `cron_support_chat.php --check` is read only and exits nonzero when mail or the OTP table is unavailable. It prints no customer data or OTPs.
- No schema migration, mail-address changes or customer mail sends are performed by this source change.

## Operational handoff

1. **Preferred:** keep exactly one installed `run_scheduled_operations.php queue` cron. Do not configure `cron_support_chat.php --run` alongside it. The latter is a fallback for an installation missing the main scheduled queue.
2. Before deployment, confirm private F01 HTTP-denial protection, a verified private backup, schema migrations, legitimate mail sender configuration and cPanel scheduler ownership.
3. Run `php scripts/cron_support_chat.php --check` in the deployed portal; a nonzero result means the notification system is not ready. Check the private `cron-queue-status.json` for `status=ok` and `mail_transport_enabled=true` when the main worker runs.
4. Use synthetic accounts/mailboxes to verify one staff follow-up, one visitor offline reply and one closure receipt. Verify actual recipient mailbox delivery, retry behavior and OTP sign-in; queue acceptance is not inbox delivery.
5. Check only one active scheduler, run the approved backup-gated cPanel publisher after mandatory preflight, and verify 30-day retention with safe test records. Never read or log live customer messages in diagnostics.

**Status:** Source change proposed. GitHub CI and a live cPanel/SMTP acceptance test are required before production-ready claims.
