<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

logout_user();
flash('success', 'You are logged out.');
// Persist the newly rotated anonymous PHP session before the browser follows
// the redirect. This prevents stale CSRF tokens immediately after logout.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
redirect(url('login'));
