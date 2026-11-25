#!/usr/bin/env bash
# One interactive acceptance command; never activates payments or alters existing gateway settings.
set -Eeuo pipefail
umask 077
WORK="$(mktemp -d "$HOME/.academy-final-qa.XXXXXXXX")"
trap 'unset TEST_KEY; rm -rf "$WORK"' EXIT
PORTAL="$HOME/public_html/portal.iworksgt.com"
BASE="https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal"
curl -fsSL --retry 3 "$BASE/228c7ddb7c5a0d0d66908f402947d7ff14c46ffd/scripts/academy_real_student_qa.php" -o "$WORK/student.php"
curl -fsSL --retry 3 "$BASE/d960b069ff1af8250dfa904244b60c3c85e477ce/scripts/academy_paystack_sandbox.php" -o "$WORK/payment.php"
php -l "$WORK/student.php"
php -l "$WORK/payment.php"
STUDENT_RESULT=3
PAYMENT_RESULT=3
echo '=== REAL STUDENT LOGIN AND APPLICATION TEST ==='
read -r -p 'Run the fresh email/OTP test? [Y/n] ' ANSWER </dev/tty
if [[ "${ANSWER,,}" != n ]]; then
  if php "$WORK/student.php" "$PORTAL" </dev/tty; then STUDENT_RESULT=0; else STUDENT_RESULT=$?; fi
else
  echo 'PENDING fresh registration/OTP and application test skipped.'
fi
echo '=== PAYSTACK TEST-MODE PAYMENT ==='
echo 'Get your TEST secret key from the Paystack dashboard, not the live key.'
echo 'If the configured portal key is already a test key, press Enter.'
read -r -s -p 'Paystack test secret (hidden): ' TEST_KEY </dev/tty
echo
if printf '%s\n' "$TEST_KEY" | php "$WORK/payment.php" "$PORTAL"; then PAYMENT_RESULT=0; else PAYMENT_RESULT=$?; fi
unset TEST_KEY
echo "Student QA result=$STUDENT_RESULT; Paystack sandbox result=$PAYMENT_RESULT (0 means passed)."
echo 'No live activation or production charge performed.'
if [[ "$STUDENT_RESULT" != 0 || "$PAYMENT_RESULT" != 0 ]]; then exit 3; fi
