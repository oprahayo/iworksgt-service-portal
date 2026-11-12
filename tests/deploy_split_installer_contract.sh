#!/usr/bin/env bash
# Offline contract test: cPanel release preflight must validate actual source
# markers before modifying production or creating DB tables.
set -euo pipefail
cd "$(dirname "$0")/.."
script="scripts/deploy_public_root_split.sh"
dashboard="views/client-dashboard.php"
bash -n "$script"
grep -Fq -- 'iw-client-services' "$dashboard"
grep -Fq -- "grep -Fq -- 'iw-client-services' \"\$WORK/portal/views/client-dashboard.php\"" "$script"
if grep -Fq -- "grep -Fq -- 'New application' \"\$WORK/portal/views/client-dashboard.php\"" "$script"; then
  echo "ERROR: Old false-negative dashboard check found" >&2
  exit 1
fi
# --check must validate release contents, not just the document-root paths.
node - "$script" <<'JS'
const fs=require('fs');
const s=fs.readFileSync(process.argv[2],'utf8');
const check=s.indexOf('RELEASE VALIDATION PASSED.');
const guard=s.indexOf("grep -Fq -- 'iw-client-services'");
const backup=s.indexOf('mkdir -p "$BACKUP_DIR/portal"');
const copy=s.indexOf('install -m 0644 "$WORK/portal/$file"');
const migration=s.indexOf('migrate_trademark_applications.php" || fail');
if ([check,guard,backup,copy,migration].some(n=>n<0) ||
    !(guard<check && check<backup && backup<copy && copy<migration)) {
  throw Error('Installer must preflight before backups, copied files and migrations');
}
if (!s.includes("trap 'restore $?' EXIT") ||
    !s.includes('if [[ "$STARTED" == 1 && "$status" -ne 0 ]]')) {
  throw Error('Installer should restore an in-progress update on all nonzero exits');
}
JS
echo "deploy_split_installer_contract passed"
