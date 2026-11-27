#!/usr/bin/env bash
# Installs only account-local PDF dependency, then runs read-only preflight.
set -Eeuo pipefail
umask 077
SHA=9b3313edca63f571bae9d5ceab6f515b7240f52d
PY=""
for candidate in /opt/alt/python313/bin/python3.13 /opt/alt/python312/bin/python3.12 /opt/alt/python311/bin/python3.11 /opt/alt/python310/bin/python3.10 /opt/alt/python39/bin/python3.9 "$(command -v python3 || true)"; do
  if [[ -x "$candidate" ]] && "$candidate" -c 'import sys; assert sys.version_info >= (3,9)' 2>/dev/null; then PY="$candidate"; break; fi
done
[[ -n "$PY" ]] || { echo 'BLOCK Python 3.9+ unavailable'; exit 1; }
"$PY" --version
ENV="$HOME/.iworks-academy-renderer"
"$PY" -m venv "$ENV"
"$ENV/bin/python" -m pip --isolated install --only-binary=:all: --index-url https://pypi.org/simple 'reportlab==4.4.9'
"$ENV/bin/python" -c 'import reportlab; print("ReportLab:", reportlab.Version)'
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT
curl -fsSL --retry 3 "https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/$SHA/scripts/academy_host_check.sh" -o "$TMP"
bash "$TMP" "$SHA" "$HOME/academy-host-check"
