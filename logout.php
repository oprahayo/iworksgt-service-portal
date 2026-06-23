<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

logout_user();
flash('success', 'You are logged out.');
redirect(url('login'));
