# iWORKS GLOBAL — Support Chat, Notifications and Homepage Follow-up

Date: 15 November 2026. Release is source-only until verified on cPanel.

## Verified application behavior before changes

- The live chat API (`app/live_chat.php`) stored visitor/staff messages and supported staff inbox polling.
- Start triggered admin-only `notify_users(admin_user_ids())`. Agent roles could access the inbox but did not get a new-chat alert.
- Offline reply email outbox existed but was disabled unless visitors checked two options. No closure-receipt email was queued.
- Closed cases remained in the DB without a 30-day automatic cleanup, and no one-click historical-topic restart existed.
- The widget displayed a country dropdown and three opt-in checkboxes.
- Home already had “View all services” and an identical “Explore the full service catalogue” destination.
- Genuine published five-case portfolio screenshots already existed in `assets/branding/case-studies/`; they must be mirrored to the public web root and corresponding CMS cases must be published.

## Implementation

- Chat opens to **Name, Email, approximate country flag (read-only), Start**. No unrelated consent-checkbox wall. We retain the collapsed "How we use your details" explanation.
- Server sets the necessary 30-day return cookie after the visitor submits the form; current public page paths are shared only while the requested support chat is active. Only whitelisted paths, without query strings, are accepted. No GPS or street address tracking or silent tracking of every non-chat visitor.
- Transactional offline email notification is automatic for chats initiated by the visitor; closure queues a receipt with reference and timestamp, but no transcript/private message body. All outbound messages remain queued in the existing MySQL outbox with retry handling; **transport acceptance is not a guarantee of recipient inbox delivery**.
- `notify_roles(['admin','agent'])` on new chat and a new inbound message at most once per five minutes per thread, to avoid mail floods. Existing admin/agent access controls are unchanged.
- Visitors can close, review and start another topic within the same secure 30-day history. `system` messages separate started/reopened/closed events and first-message topics. Any authorized support agent may see prior history and click Reopen case.
- `scripts/cron_support_chat.php --run` is the dedicated five-minute **CLI-only** dispatcher for customer reply/receipt outbox, staff email notification outbox and automatic server-side deletion of records more than 30 days after the latest stored update. Cascading foreign keys remove child messages and mail records with the thread.
- The duplicate services CTA is removed; the remaining “View all services” link remains.
- All five original published-case screenshot WebPs and the existing portfolio JS/CSS are included in the production release. CMS publication is a separate backup-gated procedure; static assets alone do not fabricate or publish case studies.
- The Privacy and Cookie pages describe the chat default, retention, country and current-public-page context.

## Operational release acceptance

1. Verify the required private database backup **and** that `scripts/migrate_live_chat_engagement.php --check` passes; existing security, schema and email mandatory release checks must pass.
2. Deploy only `scripts/deploy_chat_case_history_refresh.sh` with `--check` then `--apply` against the immutable, merged SHA. It backs up existing files and rolls back on failures. It does not write SQL rows.
3. Run `php scripts/cron_support_chat.php --check` in the installed portal. Verify SMTP active and outbox tables available.
4. Configure exactly one cPanel cron job at five-minute intervals: PHP CLI executable + absolute path to `scripts/cron_support_chat.php --run`; write logs outside the public web root, and verify the log, timestamps and cron success. A code merge is not a scheduled job.
5. Independently send real test chats: Nigeria and overseas, fallback unknown country, agent/admin app notification and *actual recipient email*, offline reply, visitor close, agent close, customer receipt, return in the same browser, start a new topic, and agent reopen with readable history. Test email transport retries and suppression on active visitor.
6. After a verified DB backup, run `php scripts/publish_all_launch_case_studies.php --check PORTAL_ROOT`; if it reports unpublished cases and a valid author account, run `--publish PORTAL_ROOT --backup-confirmed`. Verify all five public case detail URLs and mobile screenshot loads. Do not duplicate published rows.
7. Verify the one remaining services CTA, home case-study screenshots and mobile chat with 320/375/430px responsive QA; no duplicate menu/link regressions.
8. Review the actual legal basis and notice for 30-day persistent first-party cookies and support-related page-path processing in Nigeria and any other relevant jurisdiction. In particular, some cookie rules distinguish strictly necessary session state from persistent features; do not assume consent is never required simply because UI checkboxes were removed.
9. Review retention exemptions/legal holds and the private-worker deletion policy with counsel; the requested 30-day lifetime is implemented by automated cleanup, not by ordinary page loading.
10. Confirm real SMTP/inbox delivery and permissions before announcing unrestricted production readiness. Record test evidence and finalize privacy/cookie notice content.

### Why we keep a privacy explanation

A support request needs contact information, but there is no need to force a visitor to select three routine service-follow-up options. Data-protection obligations to inform customers still apply. This implementation does not enable unrelated advertising cookies or broad undisclosed surveillance.

### Authoritative legal references

- Nigeria Data Protection Act 2023, particularly section 27 (notice before collection): https://ndpc.gov.ng/wp-content/uploads/2024/03/Nigeria_Data_Protection_Act_2023.pdf
- UK ICO cookies guidance (if its scope applies), strictly necessary exception limited to a service requested by the user: https://ico.org.uk/for-organisations/direct-marketing-and-privacy-and-electronic-communications/guide-to-pecr/cookies-and-similar-technologies/
