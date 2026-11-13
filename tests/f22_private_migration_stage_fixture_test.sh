#!/usr/bin/env bash
# F22: source-only dry-run migration stage, with synthetic private config.
set -Eeuo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
export HOME="$TMP/home"
PORTAL="$HOME/public_html/portal.iworksgt.com"
mkdir -p "$PORTAL/app" "$TMP/fixture/app" "$TMP/fixture/scripts"
printf '%s\n' '<?php return ["db"=>["pass"=>"synthetic-secret"]];' > "$PORTAL/app/config.php"
for p in migrate_legacy_schema.php migrate_auth_attempt_limits.php migrate_gateway_uniqueness.php migrate_notification_email_outbox.php; do
  printf '%s\n' '<?php // offline synthetic migration' > "$TMP/fixture/scripts/$p"
done
printf '%s\n' '<?php // synthetic PHP application' > "$TMP/fixture/app/bootstrap.php"
tar -C "$TMP" -czf "$TMP/fixture.tar.gz" --transform='s#^fixture/#release-root/#' fixture
export IWORKS_RELEASE_ARCHIVE="$TMP/fixture.tar.gz"
SHA="0123456789abcdef0123456789abcdef01234567"
bash "$ROOT/scripts/prepare_private_migration_stage.sh" --check "$PORTAL" "$SHA" > "$TMP/check.log"
grep -Fq 'not prepared' "$TMP/check.log"
bash "$ROOT/scripts/prepare_private_migration_stage.sh" --prepare "$PORTAL" "$SHA" > "$TMP/prepare.log"
STAGE="$HOME/iworks-private-migration-stages/$SHA"
[[ -f "$STAGE/.release-sha" && -f "$STAGE/app/config.php" ]]
cmp "$PORTAL/app/config.php" "$STAGE/app/config.php"
[[ "$(stat -c %a "$STAGE")" == "700" ]]
[[ "$(stat -c %a "$STAGE/app/config.php")" == "600" ]]
[[ "$(stat -c %a "$HOME/iworks-private-migration-stages")" == "700" ]]
[[ ! -e "$PORTAL/scripts/migrate_legacy_schema.php" ]]
if bash "$ROOT/scripts/prepare_private_migration_stage.sh" --prepare "$PORTAL" "$SHA" >/dev/null 2>&1; then
  echo 'Private stage was unexpectedly overwritten.' >&2
  exit 1
fi
bash "$ROOT/scripts/prepare_private_migration_stage.sh" --check "$PORTAL" "$SHA" > "$TMP/again.log"
grep -Fq 'Private migration stage ready' "$TMP/again.log"
echo 'f22_private_migration_stage_fixture_test passed'
