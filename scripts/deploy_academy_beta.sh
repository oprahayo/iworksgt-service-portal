#!/usr/bin/env bash
# Controlled Academy beta release. No live payments, no customer mail.
set -Eeuo pipefail
MODE="${1:---check}"
[[ "$MODE" == --check || "$MODE" == --apply ]] || { echo 'Use --check or --apply'; exit 2; }
BASE=c20d4df85e48ee4e7ce91a9411d7ae16460a923d
RELEASE=f9bba44224b29f41e6215e828d5b3d6f2f810e41
PORTAL="$HOME/public_html/portal.iworksgt.com"
ASSETS="$HOME/academy-host-check"
PRIVATE="$HOME/.iworks-academy-templates"
OPS="$HOME/iworks-release-operations"
umask 077
for cmd in curl tar php mysql mysqldump gzip openssl sha256sum cmp readlink mktemp; do command -v "$cmd" >/dev/null || { echo "BLOCK missing $cmd"; exit 2; }; done
PORTAL="$(readlink -f "$PORTAL")"
HTML="$(readlink -f "$HOME/public_html")"
[[ "$PORTAL" == "$HTML/"* && -f "$PORTAL/app/config.php" ]] || { echo 'BLOCK unexpected portal root'; exit 2; }
[[ -d "$ASSETS" && ! -L "$ASSETS" && ! -L "$PRIVATE" ]] || { echo 'BLOCK missing private assets or unexpected symlink'; exit 2; }
mkdir -p "$OPS"; chmod 0700 "$OPS"
LOCK="$OPS/.academy-release-lock"
mkdir "$LOCK" 2>/dev/null || { echo 'BLOCK another Academy release is running'; exit 3; }
WORK="$(mktemp -d "$OPS/.academy.XXXXXXXX")"
STARTED=0
BACKUP=""
cleanup(){
  local rc=$?
  if [[ "$STARTED" == 1 ]]; then
    echo 'Release failed; restoring source files. Additive Academy database changes, if applied, are retained for safe inspection.'
    if [[ -s "$WORK/existing" ]]; then tar -xzf "$BACKUP/source.tar.gz" -C "$PORTAL" || echo 'WARNING source restore failed; inspect private backup'; fi
    while IFS= read -r f; do rm -f -- "$PORTAL/$f"; done < "$WORK/added"
  fi
  rm -rf "$WORK"; rmdir "$LOCK" || true
  return "$rc"
}
trap cleanup EXIT
echo '=== Check immutable release CI ==='
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$RELEASE&per_page=20" -o "$WORK/ci.json"
php -r '$j=json_decode(file_get_contents($argv[1]),true);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success"){echo "PASS release CI\n";exit(0);}}fwrite(STDERR,"BLOCK no successful CI for pinned release\n");exit(3);' "$WORK/ci.json" "$RELEASE"
for ref in "$BASE" "$RELEASE"; do
  mkdir "$WORK/$ref"
  curl -fsSL --retry 2 --max-time 180 "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$ref" -o "$WORK/$ref.tar.gz"
  tar -xzf "$WORK/$ref.tar.gz" -C "$WORK/$ref" --strip-components=1
done
SRC="$WORK/$RELEASE"; OLD="$WORK/$BASE"
# Preserve the operator-reviewed live Apache configuration byte-for-byte.
# Accept only the exact diagnostic hash, the repository base, or their exact
# previously merged variants. Never ignore arbitrary .htaccess drift.
[[ -f "$PORTAL/.htaccess" && ! -L "$PORTAL/.htaccess" ]] || { echo 'BLOCK invalid live .htaccess'; exit 3; }
PY="$HOME/.iworks-academy-renderer/bin/python"
[[ -x "$PY" ]] || { echo 'BLOCK private Python renderer unavailable'; exit 3; }
"$PY" -I - "$PORTAL/.htaccess" "$OLD/.htaccess" "$SRC/.htaccess" <<'PYTHON'
import hashlib
import pathlib
import sys

live_path, base_path, target_path = map(pathlib.Path, sys.argv[1:])
live = live_path.read_bytes()
base = base_path.read_bytes()
target = target_path.read_bytes()
approved = '1f22ab06817161da1b098a691e34b3cad1b1f14960aad115e97de37bd8748059'
anchor = b'RewriteRule ^sitemap\\.xml$ index.php?page=seo_sitemap [L,QSA]'
rules = [
    b'RewriteRule ^verify/?$ academy.php?action=verify [L,QSA]',
    b'RewriteRule ^student/register/?$ academy.php?action=register [L,QSA]',
    b'RewriteRule ^student/?$ academy.php [L,QSA]',
]
def recognized(data):
    return data == base or hashlib.sha256(data).hexdigest() == approved
def merge(data):
    lines = data.splitlines(keepends=True)
    indices = [i for i, line in enumerate(lines) if line.rstrip(b'\r\n') == anchor]
    if len(indices) != 1:
        raise ValueError('Expected exactly one existing sitemap rule')
    i = indices[0]
    if any(rule in data for rule in rules):
        raise ValueError('Unexpected existing Academy routing')
    eol = b'\r\n' if lines[i].endswith(b'\r\n') else b'\n'
    return b''.join(lines[:i] + [r + eol for r in rules] + lines[i:])
try:
    if recognized(live):
        merged = merge(live)
    elif live == target:
        merged = live
    else:
        stripped = live
        for rule in rules:
            matches = [line for line in stripped.splitlines(keepends=True) if line.rstrip(b'\r\n') == rule]
            if len(matches) != 1:
                raise ValueError('Unrecognized live Apache configuration')
            stripped = stripped.replace(matches[0], b'', 1)
        if not recognized(stripped) or merge(stripped) != live:
            raise ValueError('Live Apache configuration changed since diagnostics')
        merged = live
    # The old comparison baseline is deliberately the verified live file;
    # the target is a local surgical merge rather than an upstream replacement.
    base_path.write_bytes(live)
    target_path.write_bytes(merged)
except ValueError as exc:
    print('BLOCK .htaccess: ' + str(exc), file=sys.stderr)
    sys.exit(3)
print('PASS .htaccess: existing configuration preserved; three Academy routes staged')
PYTHON
cp "$PORTAL/app/config.php" "$SRC/app/config.php"; chmod 0600 "$SRC/app/config.php"
mkdir -p "$SRC/storage/academy-templates"
for colour in orange purple green blue; do
  [[ -f "$ASSETS/iworks-$colour.svg" && ! -L "$ASSETS/iworks-$colour.svg" ]] || exit 3
  cp "$ASSETS/iworks-$colour.svg" "$SRC/storage/academy-templates/"
done
# Explicit allowlist: never deploy old unrelated application files.
cat > "$WORK/files" <<'FILES'
.htaccess
academy.php
app/academy.php
app/auth_rate_limits.php
app/domain.php
assets/css/academy.css
index.php
scripts/migrate_academy.php
scripts/render_academy_pdf.py
scripts/run_scheduled_operations.php
views/login.php
views/partials/header.php
FILES
: > "$WORK/existing"; : > "$WORK/added"; : > "$WORK/changes"
echo '=== Verify source synchronization ==='
while IFS= read -r f; do
  [[ -f "$SRC/$f" && ! -L "$PORTAL/$f" ]] || { echo "BLOCK unexpected source path: $f"; exit 3; }
  case "$f" in *.php) php -l "$SRC/$f" >/dev/null ;; esac
  if [[ -f "$PORTAL/$f" ]]; then
    if cmp -s "$SRC/$f" "$PORTAL/$f"; then continue; fi
    if [[ ! -f "$OLD/$f" ]] || ! cmp -s "$OLD/$f" "$PORTAL/$f"; then
      echo "BLOCK live source differs from expected base: $f"
      echo 'No files or database changed. Reconcile the actual live version before deploying.'
      exit 3
    fi
    echo "$f" >> "$WORK/existing"
  else
    [[ ! -f "$OLD/$f" ]] || { echo "BLOCK expected existing file missing: $f"; exit 3; }
    echo "$f" >> "$WORK/added"
  fi
  echo "$f" >> "$WORK/changes"
done < "$WORK/files"
echo 'PASS existing live files match the base or target release'
php "$SRC/tests/academy_smoke_test.php"
php "$SRC/tests/academy_payment_lifecycle_test.php"
php "$SRC/scripts/migrate_auth_attempt_limits.php" --check
php "$SRC/scripts/academy_preflight.php"
php -r 'require $argv[1];if(setting("academy_checkout_enabled","0")!=="0"){fwrite(STDERR,"BLOCK checkout is already enabled\n");exit(3);}echo "PASS checkout disabled\n";' "$SRC/app/bootstrap.php"
if [[ "$MODE" == --check ]]; then echo 'CHECK ONLY passed; no live files or database changed.'; exit 0; fi
echo 'This publishes the Academy beta, with payments disabled. A private encrypted database backup is taken first.'
echo 'Type DEPLOY to continue.'
read -r confirmation </dev/tty
[[ "$confirmation" == DEPLOY ]] || { echo 'Cancelled'; exit 0; }
BACKUP="$(mktemp -d "$OPS/academy-backup-XXXXXXXX")"
chmod 0700 "$BACKUP"
echo "Private backup: $BACKUP"
# Existing trusted cPanel helper writes credentials only to a private temporary file.
php "$SRC/scripts/cpanel_finalize_db.php" client-options "$SRC" "$WORK/mysql.cnf" "$WORK/db-name"
DB="$(cat "$WORK/db-name")"
mysqldump --defaults-extra-file="$WORK/mysql.cnf" --single-transaction --quick --skip-lock-tables --no-tablespaces --routines --events --triggers "$DB" | gzip -6 > "$WORK/database.sql.gz"
gzip -t "$WORK/database.sql.gz"
KEYDIR="$HOME/iworks-release-keys"; mkdir -p "$KEYDIR"; chmod 0700 "$KEYDIR"
KEY="$KEYDIR/mysql-backup.passphrase"
if [[ ! -e "$KEY" ]]; then openssl rand -hex 48 > "$KEY"; chmod 0600 "$KEY"; fi
[[ -f "$KEY" && -s "$KEY" && ! -L "$KEY" ]] || exit 3
openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$KEY" -in "$WORK/database.sql.gz" -out "$BACKUP/database.sql.gz.enc"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$KEY" -in "$BACKUP/database.sql.gz.enc" | gzip -t
sha256sum "$BACKUP/database.sql.gz.enc" > "$BACKUP/database.sql.gz.enc.sha256"
rm -f "$WORK/database.sql.gz" "$WORK/mysql.cnf"
if [[ -s "$WORK/existing" ]]; then tar -czf "$BACKUP/source.tar.gz" -C "$PORTAL" -T "$WORK/existing"; tar -tzf "$BACKUP/source.tar.gz" >/dev/null; fi
cp "$WORK/added" "$BACKUP/added-files.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/release.txt"
echo 'PASS encrypted database and source backup integrity (not a restore rehearsal)'
cmp -s "$OLD/.htaccess" "$PORTAL/.htaccess" || { echo 'BLOCK .htaccess changed during release preparation'; exit 3; }
# Keep signed assets outside the public root.
mkdir -p "$PRIVATE"; chmod 0700 "$PRIVATE"
for colour in orange purple green blue; do
  f="iworks-$colour.svg"
  if [[ -e "$PRIVATE/$f" ]] && ! cmp -s "$SRC/storage/academy-templates/$f" "$PRIVATE/$f"; then echo "BLOCK different private template $f"; exit 3; fi
  install -m 0600 "$SRC/storage/academy-templates/$f" "$PRIVATE/$f"
done
php "$SRC/scripts/academy_preflight.php"
php "$SRC/scripts/migrate_academy.php" --backup-confirmed
# No payment setting is enabled here.
cmp -s "$OLD/.htaccess" "$PORTAL/.htaccess" || { echo 'BLOCK .htaccess changed during migration'; exit 3; }
STARTED=1
while IFS= read -r f; do
  dest="$PORTAL/$f"
  mkdir -p "$(dirname "$dest")"
  stage="$(mktemp "$(dirname "$dest")/.academy-release.XXXXXXXX")"
  cat "$SRC/$f" > "$stage"; chmod 0644 "$stage"; mv -f "$stage" "$dest"
done < "$WORK/changes"
while IFS= read -r f; do cmp -s "$SRC/$f" "$PORTAL/$f" || exit 3; done < "$WORK/files"
# Check web requests without logging in or sending mail.
for route in student/register verify; do
  code="$(curl -sS --max-time 30 -o "$WORK/response" -w '%{http_code}' "https://portal.iworksgt.com/$route")"
  [[ "$code" == 200 ]] || { echo "BLOCK HTTP $route returned $code"; exit 3; }
  if [[ "$route" == verify ]]; then grep -qi 'certificate number' "$WORK/response"; else grep -qi 'student registration' "$WORK/response"; fi
  echo "PASS public route /$route"
done
code="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' 'https://portal.iworksgt.com/academy.php?action=admin')"
case "$code" in 301|302|303|403) echo 'PASS unauthenticated admin access denied';; *) echo "BLOCK admin route returned $code"; exit 3;; esac
for route in app/academy.php scripts/render_academy_pdf.py; do
  code="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' "https://portal.iworksgt.com/$route")"
  case "$code" in 401|403|404|410) echo "PASS private source denied: $route";; *) echo "BLOCK private route returned $code"; exit 3;; esac
done
STARTED=0
echo 'SUCCESS: Academy beta deployed; checkout remains disabled. No real payments or email tests performed.'
echo "Release backup: $BACKUP"
