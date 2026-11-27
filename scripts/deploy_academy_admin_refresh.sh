#!/usr/bin/env bash
# Deploy only the student-first Academy interface, without database or payment changes.
set -Eeuo pipefail
umask 077
BASE=f9bba44224b29f41e6215e828d5b3d6f2f810e41
TARGET=86be6193e4d0b04fa43452a2e9a73edf9bed4d36
ROOT="$HOME/public_html/portal.iworksgt.com"
ROOT="$(readlink -f "$ROOT")"
HTML="$(readlink -f "$HOME/public_html")"
[[ "$ROOT" == "$HTML/"* && -f "$ROOT/app/config.php" && -f "$ROOT/academy.php" ]] || { echo 'BLOCK unexpected portal location'; exit 3; }
OPS="$HOME/iworks-release-operations"; mkdir -p "$OPS"; chmod 0700 "$OPS"
LOCK="$OPS/.academy-release-lock"
mkdir "$LOCK" 2>/dev/null || { echo 'BLOCK another Academy release running'; exit 3; }
WORK="$(mktemp -d "$OPS/.academy-ui.XXXXXXXX")"
BACKUP=""
STARTED=0
finish(){
  local rc=$?
  if [[ "$STARTED" == 1 ]]; then
    echo 'Restoring previous Academy interface.'
    if [[ -s "$WORK/existing" ]]; then tar -xzf "$BACKUP/source.tar.gz" -C "$ROOT" || echo 'WARNING manual source restore required'; fi
    while IFS= read -r f; do rm -f -- "$ROOT/$f"; done < "$WORK/added"
  fi
  rm -rf "$WORK"; rmdir "$LOCK" || true
  return "$rc"
}
trap finish EXIT
echo '=== Student-first Academy interface ==='
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$TARGET&per_page=20" -o "$WORK/ci.json"
php -r '$j=json_decode(file_get_contents($argv[1]),true);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success"){echo "PASS pinned CI, including all admin MySQL rendering tests\n";exit(0);}}fwrite(STDERR,"BLOCK pinned CI not successful yet\n");exit(3);' "$WORK/ci.json" "$TARGET"
: > "$WORK/existing"; : > "$WORK/added"; : > "$WORK/changes"
REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
# Install the view and stylesheet before changing the entrypoint.
for f in views/academy-admin.php assets/css/academy.css academy.php; do
  [[ ! -L "$ROOT/$f" ]] || { echo "BLOCK symlink $f"; exit 3; }
  mkdir -p "$WORK/source/$(dirname "$f")" "$WORK/base/$(dirname "$f")"
  curl -fsSL --retry 2 "$REPO/$TARGET/$f" -o "$WORK/source/$f"
  case "$f" in *.php) php -l "$WORK/source/$f";; esac
  if [[ -f "$ROOT/$f" ]] && cmp -s "$ROOT/$f" "$WORK/source/$f"; then continue; fi
  if [[ "$f" == views/academy-admin.php ]]; then
    [[ ! -e "$ROOT/$f" ]] || { echo "BLOCK unexpected existing $f"; exit 3; }
    echo "$f" >> "$WORK/added"
  else
    curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/base/$f"
    cmp -s "$ROOT/$f" "$WORK/base/$f" || { echo "BLOCK live file differs: $f"; exit 3; }
    echo "$f" >> "$WORK/existing"
  fi
  echo "$f" >> "$WORK/changes"
done
php -r 'require $argv[1];require $argv[2];echo "Checkout remains ".(academy_checkout_enabled()?"enabled":"disabled")."; no setting modified\n";' "$ROOT/app/bootstrap.php" "$ROOT/app/academy.php"
if [[ -s "$WORK/changes" ]]; then
  BACKUP="$(mktemp -d "$OPS/academy-ui-backup-XXXXXXXX")"; chmod 0700 "$BACKUP"
  if [[ -s "$WORK/existing" ]]; then tar -czf "$BACKUP/source.tar.gz" -C "$ROOT" -T "$WORK/existing"; tar -tzf "$BACKUP/source.tar.gz" >/dev/null; fi
  cp "$WORK/added" "$BACKUP/added-files.txt"
  printf '%s\n' "$TARGET" > "$BACKUP/target.txt"
  echo "PASS private source backup: $BACKUP"
  STARTED=1
  while IFS= read -r f; do
    stage="$(mktemp "$(dirname "$ROOT/$f")/.academy-ui.XXXXXXXX")"
    cat "$WORK/source/$f" > "$stage"; chmod 0644 "$stage"; mv -f "$stage" "$ROOT/$f"
    cmp -s "$WORK/source/$f" "$ROOT/$f"
  done < "$WORK/changes"
fi
for route in student/register verify; do
  code="$(curl -sS --max-time 30 -o "$WORK/body" -w '%{http_code}' "https://portal.iworksgt.com/$route")"
  [[ "$code" == 200 ]] || { echo "BLOCK route /$route returned $code"; exit 3; }
  if [[ "$route" == verify ]]; then grep -qi 'certificate number' "$WORK/body"; else grep -qi 'student registration' "$WORK/body"; fi
  echo "PASS existing /$route"
done
for route in 'academy.php?action=admin' 'views/academy-admin.php'; do
  code="$(curl -sS --max-time 30 -o /dev/null -w '%{http_code}' "https://portal.iworksgt.com/$route")"
  case "$code" in 301|302|303|401|403|404) echo "PASS unauthorized access blocked: $route";; *) echo "BLOCK unexpected HTTP $code"; exit 3;; esac
done
curl -fsS --max-time 30 'https://portal.iworksgt.com/assets/css/academy.css?v=2' -o "$WORK/style"
grep -Fq '.ac-stats' "$WORK/style"
STARTED=0
echo 'SUCCESS: student-first Academy interface installed. No database, payment settings or certificate artwork changed.'
echo 'Open https://portal.iworksgt.com/academy.php?action=admin as administrator and refresh.'
