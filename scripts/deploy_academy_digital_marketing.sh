#!/usr/bin/env bash
# Course-only publication; never overwrites portal PHP, CSS, JS, or payment configuration.
set -Eeuo pipefail
umask 077
SHA="${1:-}"
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]] || { echo 'BLOCK immutable release SHA required'; exit 3; }
REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
PUBLIC="$(readlink -f "$HOME/public_html")"
ROOT="$(readlink -f "$PUBLIC/portal.iworksgt.com")"
[[ "$ROOT" == "$PUBLIC/"* && -f "$ROOT/app/config.php" ]] || { echo 'BLOCK unexpected portal root'; exit 3; }
OPS="$HOME/iworks-release-operations"
mkdir -p "$OPS"; chmod 0700 "$OPS"
LOCK="$OPS/.academy-release-lock"
mkdir "$LOCK" 2>/dev/null || { echo 'BLOCK another Academy release running'; exit 3; }
WORK=""
finish(){ local rc=$?; [[ -z "$WORK" ]] || rm -rf -- "$WORK"; rmdir "$LOCK" || true; return "$rc"; }
trap finish EXIT
WORK="$(mktemp -d "$OPS/.academy-course-stage.XXXXXXXX")"
PHP=php
for candidate in /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php; do
 if [[ -x "$candidate" ]] && "$candidate" -r 'exit(extension_loaded("pdo_mysql")&&extension_loaded("mbstring")?0:1);'; then PHP="$candidate"; break; fi
done
echo "Using PHP CLI: $PHP"
echo '=== Read-only Academy source inventory ==='
cat > "$WORK/known" <<'KNOWN'
academy.php|b94bbf63aab824d1847be09e59423a3bff8e1780|Sprint 20
academy.php|537f974774c3409f009ed847c1554625665809c9|Sprint 19
academy.php|12a8ad0a4325bf9825a3ce7ba666423036fd1ed0|Sprint 18
academy.php|6a69e57bed1623b42829f2786d254bd7cfa895e5|Sprint 16
academy.php|f2edf7d0369d93699458c41dfe293d24bb67746e|Sprint 11
academy.php|81fd6aa01792d96188b63553c5cbda23d2c4f046|Sprint 10
academy.php|e9497eb175221e56bd64e18443437562d0aad510|Sprint 9B
academy.php|1738a18bd8a71cd9c65d98b4c69c8d73d3666af2|Sprint 8C, Sprint 7C
academy.php|774ab8fcade8e17f4c6c29f897856a316b96f88f|Sprint 7B
app/academy_assessments.php|743df6d9d215c5b9a0c96619ef4d3dfe48e027a9|Sprint 20, Sprint 19, Sprint 18, Sprint 16, Sprint 11, Sprint 10
app/academy_assessments.php|310ad05712c32ec25e73e441b288074bfd662e3a|Sprint 9B
app/academy_assessments.php|0e912f976589e68792fa7a713545be37b5d1030d|Sprint 8C, Sprint 7C, Sprint 7B
app/academy_admin.php|c93b6526b304f91694647afc66c58f3152bc67d9|Sprint 20, Sprint 19, Sprint 18
app/academy_admin.php|ec077b79ee0cbebf4946b9fb116403516f911b69|Sprint 16
app/academy_admin.php|32ed898956babcdf66a9d097dfbbba43e405369c|Sprint 11, Sprint 10, Sprint 9B
views/academy-admin.php|34b88782b65eed1886856281b7097ca8cf2f9614|Sprint 20
views/academy-admin.php|07c519f9a7e78a0bfa4bd2940f57c39f76f72e0b|Sprint 19, Sprint 18
views/academy-admin.php|9a8bc7cd9c3affdef2c5167afe7266638cf4b22e|Sprint 16
views/academy-admin.php|2b17290f50e1073b56a474314d403090f3e98c98|Sprint 11
views/academy-admin.php|1b13181b85d3db30da7712e9eaa2113686e20b8b|Sprint 10, Sprint 9B
views/academy-admin.php|b8516580b9593c5ab4ce8c754393b7d126f2219d|Sprint 8C, Sprint 7C, Sprint 7B
views/academy-learning.php|d58df0ecbf23ff908cd158e255a315ffcab7cff5|Sprint 20
views/academy-learning.php|d28826aa51cbd9e496381528a9049aab5acb9df6|Sprint 19
views/academy-learning.php|a505e6de3f95d980815a53e827b81cb24d94368f|Sprint 18
views/academy-learning.php|21d01217449986533e3db7ef1f84eb3875059342|Sprint 16
views/academy-learning.php|f6f7d7d81600cb151139a21b82db3f264c99adca|Sprint 11
views/academy-learning.php|6050cf8712f8fc897cb0fa12b42c16e9158a6449|Sprint 10, Sprint 9B, Sprint 8C, Sprint 7C, Sprint 7B
assets/css/academy-compact.css|c46ab7de01e2a6faa68fb1a45ff3c59bc06ccc81|Sprint 20
assets/css/academy-compact.css|5dbf9d725f60604f1c0cf1b7d45a172d0fecdf85|Sprint 19
assets/css/academy-compact.css|ff87275a9fb028330e934f44f60da1b669e94758|Sprint 18
assets/css/academy-compact.css|b1690206b7d69f597c5cd5620eefbb18ddd2f315|Sprint 16
assets/js/academy-actions.js|19f12e32db94b2fa87f8d1bf6e6fa22a89248e89|Sprint 20
assets/js/academy-actions.js|e685e3d5f74c8f2265ba953b5b98d555f15949b0|Sprint 19, Sprint 18, Sprint 16, Sprint 11, Sprint 10, Sprint 9B, Sprint 8C, Sprint 7C, Sprint 7B
KNOWN
for f in academy.php app/academy_assessments.php app/academy_admin.php views/academy-admin.php views/academy-learning.php assets/css/academy-compact.css assets/js/academy-actions.js; do
 if [[ ! -f "$ROOT/$f" ]]; then echo "MISSING $f"; continue; fi
 hash="$("$PHP" -r '$s=file_get_contents($argv[1]);echo sha1("blob ".strlen($s)."\0".$s);' "$ROOT/$f")"
 match="$(awk -F'|' -v p="$f" -v h="$hash" '$1==p&&$2==h{print $3}' "$WORK/known")"
 echo "$f : ${match:-unrecognized} [$hash]"
done
echo '=== Course-only release: no portal source files will be replaced ==='
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$SHA&per_page=30" -o "$WORK/ci.json"
"$PHP" -r '$j=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success")exit(0);}fwrite(STDERR,"BLOCK pinned CI has not passed\n");exit(3);' "$WORK/ci.json" "$SHA"
echo 'PASS pinned CI'
for f in seed_academy_digital_marketing.php cpanel_finalize_db.php; do
 mkdir -p "$WORK/target/scripts"
 curl -fsSL --retry 2 "$REPO/$SHA/scripts/$f" -o "$WORK/target/scripts/$f"
 "$PHP" -l "$WORK/target/scripts/$f"
done
"$PHP" "$WORK/target/scripts/seed_academy_digital_marketing.php" --check
"$PHP" -r 'require $argv[1];db_one("SELECT id,duration_minutes FROM academy_assessments LIMIT 1");db_one("SELECT course_id FROM academy_learning_courses LIMIT 1");db_one("SELECT id,body FROM academy_lessons LIMIT 1");db_one("SELECT id,course_id FROM academy_modules LIMIT 1");echo "PASS existing Academy schema\n";' "$ROOT/app/bootstrap.php"
BACKUP="$(mktemp -d "$OPS/digital-marketing-backup-XXXXXXXX")"; chmod 0700 "$BACKUP"
for tool in mysqldump gzip openssl sha256sum; do command -v "$tool" >/dev/null; done
"$PHP" "$WORK/target/scripts/cpanel_finalize_db.php" client-options "$ROOT" "$WORK/mysql.cnf" "$WORK/db-name"
DB="$(cat "$WORK/db-name")"
mysqldump --defaults-extra-file="$WORK/mysql.cnf" --single-transaction --quick --skip-lock-tables --no-tablespaces --routines --events --triggers "$DB" | gzip -6 > "$WORK/database.sql.gz"
gzip -t "$WORK/database.sql.gz"
KEYDIR="$HOME/iworks-release-keys"; mkdir -p "$KEYDIR"; chmod 0700 "$KEYDIR"
KEY="$KEYDIR/mysql-backup.passphrase"
if [[ ! -e "$KEY" ]]; then openssl rand -hex 48 > "$KEY"; chmod 0600 "$KEY"; fi
[[ -f "$KEY" && -s "$KEY" && ! -L "$KEY" ]]
openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$KEY" -in "$WORK/database.sql.gz" -out "$BACKUP/database.sql.gz.enc"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$KEY" -in "$BACKUP/database.sql.gz.enc" | gzip -t
sha256sum "$BACKUP/database.sql.gz.enc" > "$BACKUP/database.sql.gz.enc.sha256"
rm -f "$WORK/database.sql.gz" "$WORK/mysql.cnf"
echo "PASS encrypted database backup: $BACKUP"

"$PHP" "$WORK/target/scripts/seed_academy_digital_marketing.php" "$ROOT" --backup-confirmed
echo 'PASS course-only release complete. No portal source, payment or student records replaced.'
