#!/usr/bin/env bash
# Scoped, source-only release; no customer record or schema mutations.
set -Eeuo pipefail
umask 077
SHA="${1:-}"
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]] || { echo 'BLOCK immutable SHA required'; exit 3; }
BASE=c9fd31754c0fab81c9d065905741a20ee9d27d04
REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
PUBLIC="$(readlink -f "$HOME/public_html")"
ROOT="$(readlink -f "$PUBLIC/portal.iworksgt.com")"
[[ "$ROOT" == "$PUBLIC/"* && -f "$ROOT/app/config.php" && -f "$ROOT/index.php" ]]
# The established apex controller delegates to the portal code.
if ! grep -Fq 'portal.iworksgt.com/index.php' "$PUBLIC/index.php"; then
 echo 'BLOCK apex controller does not match established delegated setup; no files changed'; exit 3
fi
for cmd in curl diff patch tar mktemp cmp; do command -v "$cmd" >/dev/null; done
OPS="$HOME/iworks-release-operations"; mkdir -p "$OPS"; chmod 0700 "$OPS"
LOCK="$OPS/.academy-release-lock"; mkdir "$LOCK" || { echo 'BLOCK another release running'; exit 3; }
WORK=""; BACKUP=""; STARTED=0
finish(){
 local rc=$?
 if [[ "$STARTED" == 1 ]]; then
  echo 'Restoring original communications source.'
  [[ ! -s "$WORK/existing" ]] || tar -xzf "$BACKUP/source.tgz" -C "$ROOT" || echo "WARNING restore manually: $BACKUP"
  while IFS= read -r f; do rm -f "$ROOT/$f"; done < "$WORK/added"
 fi
 [[ -z "$WORK" ]] || rm -rf "$WORK"
 rmdir "$LOCK" || true
 return "$rc"
}
trap finish EXIT
WORK="$(mktemp -d "$OPS/communications-stage-XXXXXXXX")"
BACKUP="$(mktemp -d "$OPS/communications-backup-XXXXXXXX")"
PHP=php
for candidate in /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php; do
 if [[ -x "$candidate" ]] && "$candidate" -r 'exit(extension_loaded("pdo_mysql")&&extension_loaded("mbstring")?0:1);'; then PHP="$candidate"; break; fi
done
echo '=== iWorks Communications workspace ==='
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$SHA&per_page=30" -o "$WORK/ci.json"
"$PHP" -r '$j=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success")exit(0);}fwrite(STDERR,"BLOCK pinned CI has not passed\n");exit(3);' "$WORK/ci.json" "$SHA"
echo 'PASS pinned CI'
"$PHP" -r 'require $argv[1];db_one("SELECT id,full_name,email,phone,company_name,message,page_slug,status,created_at,updated_at FROM website_leads LIMIT 1");echo "PASS existing enquiry schema\n";' "$ROOT/app/bootstrap.php"
: > "$WORK/changes"; : > "$WORK/existing"; : > "$WORK/added"
for f in app/website_inbox.php views/partials/communications-nav.php views/website-inbox.php assets/css/communications-workspace.css views/live-inbox.php views/messages.php views/support.php views/frontend-editor.php app/domain.php views/partials/header.php index.php; do
 [[ ! -L "$ROOT/$f" && -d "$(dirname "$ROOT/$f")" ]]
 mkdir -p "$WORK/target/$(dirname "$f")" "$WORK/stage/$(dirname "$f")"
 curl -fsSL --retry 2 "$REPO/$SHA/$f" -o "$WORK/target/$f"
 if [[ -f "$ROOT/$f" ]] && cmp -s "$ROOT/$f" "$WORK/target/$f"; then continue; fi
 case "$f" in
  app/website_inbox.php|views/partials/communications-nav.php|views/website-inbox.php|assets/css/communications-workspace.css)
   [[ ! -e "$ROOT/$f" ]] || { echo "BLOCK existing custom file $f"; exit 3; }
   cp "$WORK/target/$f" "$WORK/stage/$f"; echo "$f" >> "$WORK/added";;
  *)
   [[ -f "$ROOT/$f" ]]
   curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/base"
   if cmp -s "$ROOT/$f" "$WORK/base"; then cp "$WORK/target/$f" "$WORK/stage/$f"
   else
    # Preserve unrelated live edits: apply only exact-context changes to a private staged copy.
    cp "$ROOT/$f" "$WORK/stage/$f"
    code=0; diff -u --label "$f" --label "$f" "$WORK/base" "$WORK/target/$f" > "$WORK/change.diff" || code=$?
    [[ "$code" == 1 ]] || { echo "BLOCK invalid source diff $f"; exit 3; }
    patch --batch --forward --fuzz=0 "$WORK/stage/$f" "$WORK/change.diff" > "$WORK/patch.log" || { echo "BLOCK overlapping source changes $f; no files changed"; exit 3; }
    echo "PASS exact-context source merge: $f"
   fi
   echo "$f" >> "$WORK/existing";;
 esac
 case "$f" in *.php) "$PHP" -l "$WORK/stage/$f";; esac
 echo "$f" >> "$WORK/changes"
done
# Never run a full-site release or restore old Academy/payment files.
if [[ -s "$WORK/existing" ]]; then tar -czf "$BACKUP/source.tgz" -C "$ROOT" -T "$WORK/existing"; tar -tzf "$BACKUP/source.tgz" >/dev/null; fi
cp "$WORK/added" "$BACKUP/added.txt"; cp "$WORK/changes" "$BACKUP/changed.txt"
STARTED=1
while IFS= read -r f; do
 stage="$(mktemp "$(dirname "$ROOT/$f")/.communications-XXXXXXXX")"
 cat "$WORK/stage/$f" > "$stage"
 if [[ -f "$ROOT/$f" ]]; then chmod --reference="$ROOT/$f" "$stage"; else chmod 0644 "$stage"; fi
 mv -f "$stage" "$ROOT/$f"
 cmp -s "$ROOT/$f" "$WORK/stage/$f"
done < "$WORK/changes"
curl -fsSL --retry 2 --max-time 30 "https://portal.iworksgt.com/assets/css/communications-workspace.css?v=20261127-1" -o "$WORK/live.css"
cmp -s "$WORK/live.css" "$WORK/target/assets/css/communications-workspace.css"
for page in website_inbox live_inbox messages support frontend; do
 status="$(curl -sS --max-time 30 -o "$WORK/response" -w '%{http_code}' "https://portal.iworksgt.com/index.php?page=$page")"
 case "$status" in 301|302|303|307|308|401|403) ;; *) echo "BLOCK unauthenticated route $page returned $status"; exit 3;; esac
done
status="$(curl -sS -L --max-time 35 -o "$WORK/home.html" -w '%{http_code}' 'https://iworksgt.com/')"
[[ "$status" == 200 ]] || { echo "BLOCK public website HTTP $status"; exit 3; }
STARTED=0
echo "PASS Communications workspace installed. Private backup: $BACKUP"
echo 'Existing enquiries and messages preserved; no customer emails sent or database records changed by installer.'
