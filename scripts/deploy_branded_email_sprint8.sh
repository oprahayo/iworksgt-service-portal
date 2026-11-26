#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
SHA="$1"
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]]
BASE=49ed55de7ff64b0385318b224d0e0b5c8e7ec1b4
REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
PUBLIC="$(readlink -f "$HOME/public_html")"
ROOT="$(readlink -f "$PUBLIC/portal.iworksgt.com")"
[[ "$ROOT" == "$PUBLIC/"* && -f "$ROOT/app/config.php" ]]
OPS="$HOME/iworks-release-operations"
mkdir -p "$OPS"
chmod 0700 "$OPS"
WORK="$(mktemp -d "$OPS/mail-brand-XXXXXXXX")"
BACKUP="$WORK/backup"
mkdir -p "$BACKUP"
echo "Release staging and backup: $WORK"
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$SHA&per_page=30" -o "$WORK/ci.json"
php -r '$j=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success")exit(0);}fwrite(STDERR,"BLOCK pinned CI not successful\n");exit(3);' "$WORK/ci.json" "$SHA"
for f in app/support_email.php app/helpers.php app/academy.php tests/branded_transactional_email_test.php assets/branding/iworks-social-share.png; do
 mkdir -p "$WORK/target/$(dirname "$f")"
 curl -fsSL --retry 2 "$REPO/$SHA/$f" -o "$WORK/target/$f"
 case "$f" in *.php) php -l "$WORK/target/$f";; esac
done
PY="$HOME/.iworks-academy-renderer/bin/python"
"$PY" -I - "$WORK/target/assets/branding" <<'PYTHON'
from pathlib import Path
from PIL import Image
import sys
p=Path(sys.argv[1])
with Image.open(p/"iworks-social-share.png") as im:
    assert im.size==(1200,630)
    im.convert("RGB").crop((112,202,1088,299)).save(p/"iworks-email-logo.png",format="PNG")
PYTHON
php "$WORK/target/tests/branded_transactional_email_test.php"
: > "$WORK/manifest"
for f in app/support_email.php app/helpers.php app/academy.php; do
 curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/base.php"
 for root in "$ROOT" "$PUBLIC"; do
  if [[ "$root" == "$PUBLIC" && ( "$f" == app/academy.php || ! -f "$root/$f" || "$root/$f" -ef "$ROOT/$f" ) ]]; then continue; fi
  [[ -f "$root/$f" && ! -L "$root/$f" ]]
  if cmp -s "$root/$f" "$WORK/target/$f"; then continue; fi
  matched=0
  if cmp -s "$root/$f" "$WORK/base.php"; then matched=1; fi
  for prior in 69d59a4c23ba1b75ad184f97b1597df834345ef5 effdb546930f8b944a182d794f0d5db00e810631 5ec9cc4969935cb8a0952094fc7a1391022457eb; do
   if [[ "$matched" == 1 ]]; then break; fi
   curl -fsSL --retry 2 "$REPO/$prior/$f" -o "$WORK/prior.php"
   if cmp -s "$root/$f" "$WORK/prior.php"; then matched=1; fi
  done
  [[ "$matched" == 1 ]] || { echo "BLOCK unrecognized prior mail source: $root/$f"; exit 3; }
  printf '%s|%s\n' "$root" "$f" >> "$WORK/manifest"
  tag=portal; [[ "$root" != "$PUBLIC" ]] || tag=public
  mkdir -p "$BACKUP/$tag/app"
  cp -p "$root/$f" "$BACKUP/$tag/$f"
 done
done
LOGO=assets/branding/iworks-email-logo.png
[[ ! -L "$ROOT/$LOGO" && -d "$ROOT/assets/branding" ]]
if [[ -f "$ROOT/$LOGO" ]]; then
 cmp -s "$ROOT/$LOGO" "$WORK/target/$LOGO" || { echo 'BLOCK existing logo differs'; exit 3; }
else
 install -m 0644 "$WORK/target/$LOGO" "$ROOT/$LOGO"
fi
curl -fsSL --retry 2 --max-time 30 "https://portal.iworksgt.com/$LOGO" -o "$WORK/live.png"
cmp -s "$WORK/live.png" "$WORK/target/$LOGO"
rollback(){
 while IFS='|' read -r root f; do
  tag=portal; [[ "$root" != "$PUBLIC" ]] || tag=public
  cp -p "$BACKUP/$tag/$f" "$root/$f"
 done < "$WORK/manifest"
 echo "Mail source restored. Backup: $BACKUP"
}
trap 'rollback' ERR
while IFS='|' read -r root f; do
 stage="$(mktemp "$root/app/.mail-brand.XXXXXXXX")"
 cat "$WORK/target/$f" > "$stage"
 chmod --reference="$root/$f" "$stage"
 mv -f "$stage" "$root/$f"
 cmp -s "$root/$f" "$WORK/target/$f"
done < "$WORK/manifest"
php -r 'require $argv[1];$h=iworks_email_html("Your iWorks GT login OTP","Your OTP code is: 123456");if(!str_contains($h,"123456")||!str_contains($h,"iworks-email-logo.png"))exit(1);' "$ROOT/app/support_email.php"
trap - ERR
echo "PASS branded mail installed. Backup: $BACKUP"
echo 'No live email was sent. Test using your own account.'
