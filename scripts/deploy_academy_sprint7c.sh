#!/usr/bin/env bash
# Sprint 7C: scoped source-only release, never touches payment or database configuration.
set -Eeuo pipefail
umask 077
TARGET="${1:-}"
[[ "$TARGET" =~ ^[0-9a-f]{40}$ ]] || { echo 'BLOCK immutable target SHA required'; exit 3; }
BASE=a3450229ae279f1c76f8dd4428437eb72d548a38
REJECTED=6d9caf96fa15891cbaea21ab1aea1351c70996d5
SPRINT7B=687479e3e4604ca6251398ab509c5093b6c6af3e
REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
ROOT="$(readlink -f "$HOME/public_html/portal.iworksgt.com")"
HTML="$(readlink -f "$HOME/public_html")"
[[ "$ROOT" == "$HTML/"* && -f "$ROOT/app/config.php" && -d "$ROOT/scripts" ]] || { echo 'BLOCK unexpected portal root'; exit 3; }
OPS="$HOME/iworks-release-operations"; mkdir -p "$OPS"; chmod 0700 "$OPS"
LOCK="$OPS/.academy-release-lock"
mkdir "$LOCK" 2>/dev/null || { echo 'BLOCK another Academy release running'; exit 3; }
WORK=""; BACKUP=""; STARTED=0
finish(){
  local rc=$?
  if [[ "$STARTED" == 1 ]]; then
    echo 'Release failed; restoring prior source.'
    if [[ -s "$WORK/existing" ]]; then tar -xzf "$BACKUP/source.tar.gz" -C "$ROOT" || echo 'WARNING manual rollback required'; fi
    while IFS= read -r f; do rm -f -- "$ROOT/$f"; done < "$WORK/added"
  fi
  [[ -z "$WORK" ]] || rm -rf -- "$WORK"
  rmdir "$LOCK" || true
  return "$rc"
}
trap finish EXIT
WORK="$(mktemp -d "$OPS/.academy-preview.XXXXXXXX")"
echo '=== Academy Sprint 7C: original certificate previews + NOT VALID watermark ==='
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$TARGET&per_page=30" -o "$WORK/ci.json"
CI_STATE="$(php -r '$j=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]){echo ($r["status"]??"")==="completed"?($r["conclusion"]??"failure"):"pending";exit;}}echo "pending";' "$WORK/ci.json" "$TARGET")"
[[ "$CI_STATE" == success ]] || { echo "BLOCK pinned CI: $CI_STATE; no production changes"; exit 3; }
echo 'PASS pinned CI'
PY="$HOME/.iworks-academy-renderer/bin/python"
FONTS="$HOME/.local/share/fonts/iworks-academy"
[[ -x "$PY" ]] || { echo 'BLOCK private Python renderer unavailable'; exit 3; }
"$PY" -I -c 'from PIL import Image, ImageFont, ImageDraw; assert hasattr(Image,"Resampling")'
for font in Lato-Regular.ttf Lato-Bold.ttf Lato-Black.ttf LiberationSerif-Bold.ttf; do [[ -r "$FONTS/$font" ]] || { echo "BLOCK missing preview font $font"; exit 3; }; done
: > "$WORK/existing"; : > "$WORK/added"; : > "$WORK/changes"
for f in academy.php app/academy.php scripts/render_academy_preview.py scripts/academy_beta_acceptance.php; do
  [[ ! -L "$ROOT/$f" ]] || { echo "BLOCK symlink $f"; exit 3; }
  mkdir -p "$WORK/target/$(dirname "$f")"
  curl -fsSL --retry 2 "$REPO/$TARGET/$f" -o "$WORK/target/$f"
  case "$f" in *.php) php -l "$WORK/target/$f";; esac
  if [[ -f "$ROOT/$f" ]] && cmp -s "$ROOT/$f" "$WORK/target/$f"; then continue; fi
  if [[ ! -e "$ROOT/$f" && ( "$f" == scripts/render_academy_preview.py || "$f" == scripts/academy_beta_acceptance.php ) ]]; then
    echo "$f" >> "$WORK/added"
  else
    [[ -f "$ROOT/$f" ]] || { echo "BLOCK missing $f"; exit 3; }
    MATCH=0
    for ref in "$BASE" "$SPRINT7B" "$REJECTED"; do
      if curl -fsSL --retry 2 "$REPO/$ref/$f" -o "$WORK/base" && cmp -s "$ROOT/$f" "$WORK/base"; then MATCH=1; break; fi
    done
    [[ "$MATCH" == 1 ]] || { echo "BLOCK unexpected source drift: $f; install Sprint 7B first or review local changes"; exit 3; }
    echo "$f" >> "$WORK/existing"
  fi
  echo "$f" >> "$WORK/changes"
done
# Verify Sprint 7B training and fast-action files have not been skipped.
for f in app/academy_assessments.php views/academy-student.php views/academy-learning.php assets/js/academy-actions.js assets/css/academy.css; do
  curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/dependency"
  cmp -s "$ROOT/$f" "$WORK/dependency" || { echo "BLOCK Sprint 7B dependency differs: $f"; exit 3; }
done
# Test all four original, hash-pinned private templates before deployment.
php -r 'require $argv[1];foreach(["orange","purple","green","blue"] as $colour){$png=academy_preview_png(["colour"=>$colour,"student_name"=>"ACADEMY TEST STUDENT","course_title"=>"Web Development","duration"=>"12 Weeks"]);if(substr($png,0,8)!=="\x89PNG\r\n\x1a\n")exit(1);}echo "PASS all four original certificate previews on cPanel\n";' "$WORK/target/app/academy.php"
if [[ -s "$WORK/changes" ]]; then
  BACKUP="$(mktemp -d "$OPS/academy-sprint7c-backup-XXXXXXXX")"
  if [[ -s "$WORK/existing" ]]; then tar -czf "$BACKUP/source.tar.gz" -C "$ROOT" -T "$WORK/existing"; tar -tzf "$BACKUP/source.tar.gz" >/dev/null; fi
  cp "$WORK/added" "$BACKUP/added-files.txt"
  printf '%s\n' "$TARGET" > "$BACKUP/release.txt"
  STARTED=1
  # Install renderer before switching the HTTP endpoint.
  for f in scripts/render_academy_preview.py app/academy.php scripts/academy_beta_acceptance.php academy.php; do
    grep -Fxq "$f" "$WORK/changes" || continue
    stage="$(mktemp "$(dirname "$ROOT/$f")/.academy-preview.XXXXXXXX")"
    cat "$WORK/target/$f" > "$stage"
    if [[ -f "$ROOT/$f" ]]; then chmod --reference="$ROOT/$f" "$stage"; else chmod 0644 "$stage"; fi
    mv -f "$stage" "$ROOT/$f"
    cmp -s "$ROOT/$f" "$WORK/target/$f"
  done
  echo "PASS private backup: $BACKUP"
fi
# Exercise installed PHP adapter, including its private process and output validation.
php -r 'require $argv[1];$png=academy_preview_png(["colour"=>"purple","student_name"=>"ACADEMY TEST STUDENT","course_title"=>"Web Development","duration"=>"12 Weeks"]);if(substr($png,0,8)!=="\x89PNG\r\n\x1a\n")exit(1);echo "PASS PHP restricted preview\n";' "$ROOT/app/academy.php"
# Unauthenticated visitors must not obtain image or PDF bytes.
for route in 'preview_image' 'download&id=1'; do
  code="$(curl -sS --max-time 30 -o "$WORK/http" -w '%{http_code}' "https://portal.iworksgt.com/academy.php?action=$route")"
  case "$code" in 301|302|303|307|308|401|403|404) ;; *) echo "BLOCK unauthenticated access check returned $code"; exit 3;; esac
done
STARTED=0
echo 'PASS corrected Sprint 7C installed. Log in as an approved unpaid student to verify preview and payment flow.'
