#!/usr/bin/env bash
# One interactive acceptance command; never activates payments or alters existing gateway settings.
set -Eeuo pipefail
umask 077
WORK="$(mktemp -d "$HOME/.academy-final-qa.XXXXXXXX")"
trap 'unset TEST_KEY; rm -rf "$WORK"' EXIT
PORTAL="$HOME/public_html/portal.iworksgt.com"
BASE="https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal"
curl -fsSL --retry 3 "$BASE/c698826fe244bd38ba9fbda1f841f541ffa6a908/scripts/academy_real_student_qa.php" -o "$WORK/student.php"
curl -fsSL --retry 3 "$BASE/2912bb957830abe0e3db8a3c2008a674eab52ed0/scripts/academy_paystack_sandbox.php" -o "$WORK/payment.php"
php -l "$WORK/student.php"
php -l "$WORK/payment.php"
echo '=== REAL STUDENT LOGIN TEST ==='
php "$WORK/student.php" "$PORTAL" </dev/tty
echo '=== PAYSTACK TEST-MODE PAYMENT ==='
echo 'Get your TEST secret key from the Paystack dashboard, not the live key.'
echo 'If the configured portal key is already a test key, press Enter.'
read -r -s -p 'Paystack test secret (hidden): ' TEST_KEY </dev/tty
echo
printf '%s\n' "$TEST_KEY" | php "$WORK/payment.php" "$PORTAL"
unset TEST_KEY
echo 'Student and sandbox API tests finished. No live activation or production charge performed.'
