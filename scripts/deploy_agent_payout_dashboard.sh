#!/usr/bin/env bash
# Reversible portal code release while Paystack initiation stays OFF.
# No schema migration, payment API call, queue execution or ledger write.
set -Eeuo pipefail
[[ "${1:-}" == --deploy-off ]] || { echo 'Use --deploy-off PORTAL_PATH REVIEWED_SHA' >&2; exit 2; }
PORTAL=$(readlink -f "${2:?Portal path required}")
SHA=${3:?Reviewed source revision required}
ROOT=$(cd "$(dirname "$0")/.." && pwd)
[[ "$PORTAL" == "$HOME/public_html/portal.iworksgt.com" && -f "$PORTAL/app/config.php" ]] || exit 2
[[ "$SHA" =~ ^[a-f0-9]{40}$ && $(git -C "$ROOT" rev-parse HEAD) == "$SHA" ]] || exit 2
git -C "$ROOT" diff --quiet
git -C "$ROOT" diff --cached --quiet
BASE=c20d4df85e48ee4e7ce91a9411d7ae16460a923d
FILES=(app/agent_paystack_client.php app/agent_withdrawals.php app/auth_rate_limits.php
  app/bootstrap.php app/domain.php app/gateway_verification.php index.php
  views/payouts.php views/profile.php views/settings.php views/withdrawals.php)
umask 077
mkdir -p "$HOME/iworks-release-operations"
RUN=$(mktemp -d "$HOME/iworks-release-operations/dashboard-off-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX")
mkdir "$RUN/candidate" "$RUN/rollback"
git -C "$ROOT" archive "$SHA" | tar -x -C "$RUN/candidate"
cp "$PORTAL/app/config.php" "$RUN/candidate/app/config.php"
chmod 0600 "$RUN/candidate/app/config.php"
printf '%s\n' '<?php return ["mode" => "off"];' > "$RUN/candidate/app/agent_payout_runtime.php"
php -r '$p=$argv[1]."/app/agent_payout_runtime.php"; if(is_link($p)){exit(2);} $c=is_file($p)?require $p:[]; if(($c["mode"]??"off")!=="off"){fwrite(STDERR,"Active payout runtime: stop and review.\n");exit(2);}' "$PORTAL"
for f in "${FILES[@]}"; do
  [[ ! -L "$PORTAL/$f" ]] || exit 3
  if git -C "$ROOT" cat-file -e "$BASE:$f" 2>/dev/null; then
    git -C "$ROOT" show "$BASE:$f" | cmp -s - "$PORTAL/$f" || {
      echo "Production source differs from reviewed baseline: $f" >&2; exit 3;
    }
  elif [[ -e "$PORTAL/$f" ]]; then
    echo "Unexpected existing new file: $f" >&2; exit 3
  fi
  php -l "$RUN/candidate/$f" >/dev/null
  if [[ -f "$PORTAL/$f" ]]; then
    mkdir -p "$RUN/rollback/$(dirname "$f")"
    cp -p "$PORTAL/$f" "$RUN/rollback/$f"
  fi
done
cp -p "$PORTAL/.htaccess" "$RUN/rollback/.htaccess"
if [[ -e "$PORTAL/app/agent_payout_runtime.php" ]]; then
  cp -p "$PORTAL/app/agent_payout_runtime.php" "$RUN/rollback/runtime.php"
fi
cat > "$RUN/snapshot.php" <<'PHP'
<?php
require $argv[1].'/app/bootstrap.php';
$out=['agents'=>[], 'tables'=>[]];
foreach(db_all("SELECT id FROM users WHERE role='agent' ORDER BY id") as $row){
    $s=agent_withdrawal_summary((int)$row['id']);
    $out['agents'][(string)$row['id']]=array_intersect_key($s,array_flip([
        'total_earned','total_withdrawn','pending_withdrawal','available_balance']));
}
foreach(['users','jobs','commission_payouts','withdrawal_requests'] as $t){
    $out['tables'][$t]=hash('sha256',json_encode(db_all('SELECT * FROM '.$t.' ORDER BY id')));
}
file_put_contents($argv[2],json_encode($out,JSON_THROW_ON_ERROR));
PHP
php "$RUN/snapshot.php" "$PORTAL" "$RUN/before.json"
php "$RUN/snapshot.php" "$RUN/candidate" "$RUN/candidate.json"
cmp -s "$RUN/before.json" "$RUN/candidate.json" || {
  echo 'Candidate changes existing financial balances or records; review required.' >&2; exit 4;
}
php "$ROOT/tests/agent_paystack_security_test.php"
php "$ROOT/tests/agent_paystack_withdrawals_contract_test.php"
CHANGING=0
SUCCESS=0
restore() {
  result=$?
  if [[ "$CHANGING" == 1 && "$SUCCESS" != 1 ]]; then
    for f in "${FILES[@]}"; do
      if [[ -f "$RUN/rollback/$f" ]]; then cp -p "$RUN/rollback/$f" "$PORTAL/$f";
      else rm -f "$PORTAL/$f"; fi
    done
    if [[ -f "$RUN/rollback/runtime.php" ]]; then cp -p "$RUN/rollback/runtime.php" "$PORTAL/app/agent_payout_runtime.php";
    else rm -f "$PORTAL/app/agent_payout_runtime.php"; fi
    cp -p "$RUN/rollback/.htaccess" "$PORTAL/.htaccess"
    echo 'Dashboard release rolled back.' >&2
  fi
  exit "$result"
}
trap restore EXIT
cat > "$RUN/maintenance.htaccess" <<'APACHE'
RewriteEngine On
RewriteRule ^ - [R=503,L]
ErrorDocument 503 "A brief portal update is in progress. Please refresh shortly."
APACHE
cat "$RUN/rollback/.htaccess" >> "$RUN/maintenance.htaccess"
CHANGING=1
cp "$RUN/maintenance.htaccess" "$PORTAL/.htaccess"
chmod 0644 "$PORTAL/.htaccess"
for f in "${FILES[@]}"; do
  temp=$(mktemp "$PORTAL/$(dirname "$f")/.payout-release-XXXXXXXX")
  cp "$RUN/candidate/$f" "$temp"
  chmod 0644 "$temp"
  mv -f "$temp" "$PORTAL/$f"
done
if [[ ! -e "$PORTAL/app/agent_payout_runtime.php" ]]; then
  cp "$RUN/candidate/app/agent_payout_runtime.php" "$PORTAL/app/agent_payout_runtime.php"
  chmod 0600 "$PORTAL/app/agent_payout_runtime.php"
fi
php "$RUN/snapshot.php" "$PORTAL" "$RUN/after.json"
cmp -s "$RUN/before.json" "$RUN/after.json"
php -r 'require $argv[1]."/app/bootstrap.php"; if(iworks_agent_payout_mode()!=="off" || withdrawal_minimum()!==5000.0 || withdrawal_cooldown_days()!==0){exit(5);} echo "PASS minimum NGN 5,000; no withdrawal interval; payout initiation OFF\n";' "$PORTAL"
for f in "${FILES[@]}"; do cmp -s "$RUN/candidate/$f" "$PORTAL/$f"; done
cp -p "$RUN/rollback/.htaccess" "$PORTAL/.htaccess"
STATUS=$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' 'https://portal.iworksgt.com/index.php?page=login')
[[ "$STATUS" == 200 ]]
printf '%s\n' "$SHA" > "$RUN/DEPLOYED_SHA"
date -u +%FT%TZ > "$RUN/CODE_DEPLOYED"
SUCCESS=1
echo 'PASS existing agent balances and user/job/commission/withdrawal records unchanged'
echo "Dashboard code deployed with payouts OFF: $SHA"
echo "Rollback and verification evidence: $RUN"
echo 'No schema migration or money movement was performed.'
