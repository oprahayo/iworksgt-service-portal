# iWORKS — returning visitor support, privacy-first presence, graphical dashboard

Date: 2026-11-14
Repository: `oprahayo/iworksgt-service-portal`
Feature PR: #102 (review and CI before production)

## Implemented source

- Existing MySQL `iw_live_threads` and `iw_live_messages` remain authoritative. Existing messages and staff assignments are preserved.
- New **opt-in** thirty-day browser remembering uses a host-only, HttpOnly, Secure, SameSite=Lax cookie containing a random conversation token. The database stores only a SHA-256 token hash. On returning to the same browser, `resume` returns the latest 120 messages. Guest chat can still be used without opting to remember it; existing per-tab support remains.
- A staff reply while the visitor is offline remains in that same conversation. The client widget checks periodically even when minimized and shows a small unread-reply badge; it makes a new presence heartbeat only while the tab is visible.
- Offline email is separate and **off by default**. It requires remembered-chat opt-in, queues a generic email (no confidential chat body), waits for the visitor to become truly offline, suppresses alerts for already-read replies, and limits reminder volume (recently emailed visitors are not spammed). Transport failures retry through the existing cPanel queue worker. Delivery is subject to mail server and recipient provider latency.
- Current public page sharing is a third, separate **unchecked** preference. Only whitelisted public paths without query strings are recorded while a consented chat is active. Support staff may see the masked IP prefix and approximate CDN country hint already present in existing chat records. The feature **does not** collect raw IP addresses, GPS or street addresses, and cannot locate a user’s residence from their IP.
- All visitors can see a local **AUTOMATED SUGGESTION** after the established two-minute delay. Context changes with Services, Pricing, Track, Contact, Blog and other safe page types. This is a rule-based invitation, not a chatbot pretending a human is watching individual movements. It does not send unconsented browsing history to the server. The nudge can be dismissed for the session.
- Operational staff dashboard places visitor conversation cards and a seven-day message activity visualization **directly below the hero**, before quick shortcuts and generic KPIs. Active chat, open conversations, awaiting staff reply, new today, recent interactions and page context are refreshed while the tab is visible. The standalone live inbox adds graphical activity, consented page details and offline email status. Access remains admin/agent role restricted.
- Public site, customer portal, login/OTP, staff payments and existing backend menus remain intact.

## Database migration and release safety

New additive fields in `iw_live_threads`: `current_page`, `track_page`, `notify_offline`, `visitor_last_read_message_id`. New unique-keyed `iw_live_email_outbox` table. All new preferences default off; no existing records are deleted or overwritten.

`scripts/migrate_live_chat_engagement.php --check` is read-only. `--apply --backup-confirmed` adds the missing schema only after the operator has created and independently verified a **fresh private SQL backup**. It never runs from the public HTTP request or application bootstrap. Without migration, the website still loads and new opt-in UI is hidden.

A cPanel operator must:
1. Verify the signed-in account’s current database and a **fresh encrypted SQL backup outside the web roots**, including a real isolated restore rehearsal. Keep the encryption key separate.
2. Run the pinned, private-staged migration and confirm all columns + outbox table; investigate any failures without deleting data.
3. Run existing immutable source-only cPanel `deploy_admin_mobile_refresh.sh --check` and then `--apply` only if all mandatory schema, mail and private-file checks pass. Do not rerun the *first-time* production publisher or OTP token invalidation.
4. Verify real guest chat start, reload/return, OTP-free public reopening, staff reply when offline, inbox reappearance and the actual opted-in email; check the existing `queue` scheduler.
5. Verify dashboard responsive layout, role isolation, privacy choices off by default and public nudge dismissal. Review the **published, possibly CMS-overridden Privacy Policy** to make sure the actual page reflects visitor email, cookie and page-sharing practices. Changing the code default does **not** overwrite published customized content.

## Acceptance boundary

CI PHP 8.2 / 8.3, JS syntax and contracts provide source validation, not proof of a live user-to-user exchange or mail delivery. Production is not marked deployed until the user’s cPanel Terminal shows a successful release and live chat/mail tests pass.

Do not log raw visitor IPs, full URLs, private message contents, database credentials, unmasked client addresses, payment details or OTPs in cPanel status output or support emails. No unconsented cross-website tracking.

## 2026-11-14 consent/country usability follow-up

The original visitor chat only offered privacy preferences when opening a *new* conversation. Existing support chats now provide a collapsible **Notifications and privacy settings** form. A visitor can opt into or revoke thirty-day browser remembering, offline reply email notices, and consented public-page sharing without restarting the conversation. Every change is CSRF-protected, scoped by the conversation bearer token, and saved server-side. Offline email requires a separately selected remembered-chat preference; a revocation skips queued unsent reminders. Only supported public page paths are shareable; disabling sharing immediately clears the currently recorded page.

The country badge accepts an optional explicit visitor selection from an accessible name-based country dropdown. When not provided, the server prefers locally configured GeoIP country headers or a locally installed GeoIP extension; no raw IP is forwarded to any third-party lookup service, no exact address is inferred and unknown remains honest. Existing saved conversations are not assigned a made-up country or email consent by this upgrade.

The same settings are returned when restoring an opted-in browser conversation. Active visitors can turn remembering off without losing the current tab conversation. The staff view distinguishes an unknown country and the customer's optional offline/page-sharing choices.

**Acceptance gates:** repeat tests with an existing active chat; change preferences while connected, minimize/reopen, leave and return in the same browser, revoke sharing/email, verify queued emails are suppressed after revocation, and confirm that a *newly consented* offline email can be received using configured production mail transport and queue cron. Source tests alone do not prove production delivery. Check the *published* privacy policy and live email transport configuration before claiming full production activation.
