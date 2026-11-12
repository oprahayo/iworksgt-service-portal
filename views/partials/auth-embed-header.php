<?php
/**
 * Standalone sign-in/register panel for the public-site dialog.
 * Authentication remains on the portal's own origin and session.
 */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($title ?? 'iWorks Account') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/auth-clean.css">
</head>
<body class="iw-auth-embed">
<main class="iw-auth-embed-main">
  <?php if ($message = flash('success')): ?><div class="iw-auth-message success" role="status"><?= e($message) ?></div><?php endif; ?>
  <?php if ($error = flash('error')): ?><div class="iw-auth-message error" role="alert"><?= e($error) ?></div><?php endif; ?>
