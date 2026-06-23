<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$email = strtolower(trim($_GET['email'] ?? ''));
$name = trim($_GET['name'] ?? '');
$phone = trim($_GET['phone'] ?? '');
$timestamp = (int) ($_GET['ts'] ?? 0);
$signature = (string) ($_GET['sig'] ?? '');

if ($email === '' || $name === '' || $timestamp <= 0 || $signature === '') {
    http_response_code(400);
    exit('Invalid SSO payload.');
}

if (abs(time() - $timestamp) > 300) {
    http_response_code(401);
    exit('SSO payload expired.');
}

$expected = hash_hmac('sha256', $email . '|' . $name . '|' . $timestamp, sso_secret());
if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid SSO signature.');
}

$user = find_user_by_email($email);

if (!$user) {
    $randomPassword = bin2hex(random_bytes(8));
    $userId = create_user([
        'full_name' => $name,
        'email' => $email,
        'phone' => $phone,
        'password' => $randomPassword,
        'role' => 'client',
        'email_verified' => true,
    ]);
    login_user($userId);
} else {
    if (!is_email_verified($user)) {
        mark_email_verified((int) $user['id']);
    }
    login_user((int) $user['id']);
}

flash('success', 'Signed in via iworksgt.com');
redirect(url('dashboard'));
