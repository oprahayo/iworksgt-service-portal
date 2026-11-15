# iWORKS source-only follow-up notification release (15 November 2026)

Source commit: `c6a4e6e0da10b6f158832180880972111e1c687a` (merged PR #142). The existing backup/rollback-aware full-source publisher is pinned to that immutable release. It includes the client/staff chat notification code, improved worker coordination, OTP retry preservation and all currently merged source files. This is the established cPanel **direct GitHub Bash** workflow, not a manually uploaded shell script.

## cPanel terminal — first, read-only check

```bash
cd "$HOME" && curl -fsSL "https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/REPLACE_OPERATOR_SHA/scripts/deploy_admin_mobile_refresh.sh" -o "$HOME/iworks-release.sh" && bash "$HOME/iworks-release.sh" --check "$HOME/public_html/portal.iworksgt.com" "$HOME/public_html"
```

Replace `REPLACE_OPERATOR_SHA` with the merge commit of this release-pin PR, *not* the application source SHA. The script itself retrieves the immutable application release `c6a4e6e0da10b6f158832180880972111e1c687a`.

## Apply only after mandatory readiness and verified private backup

```bash
bash "$HOME/iworks-release.sh" --apply "$HOME/public_html/portal.iworksgt.com" "$HOME/public_html"
```

The publisher never runs SQL migrations, does not replace private app config, storage or uploads, and checks required database schema, mail flags and private HTTP denial before any write. It performs a private source backup and automatically rolls back file changes after failed deployment checks. A database backup and restore rehearsal, real mail transport / inbox verification and operational cron review remain mandatory before production acceptance. Do not run the full first-time installer or two duplicate queue crons.

After successful deployment, read-only diagnostics:

```bash
php "$HOME/public_html/portal.iworksgt.com/scripts/cron_support_chat.php" --check
crontab -l | grep -E 'run_scheduled_operations.php.*queue|cron_support_chat.php.*--run' || true
```

**Status:** This document describes the release. GitHub merge and CI must be verified independently; cPanel deployment must not be claimed without terminal and live evidence.
