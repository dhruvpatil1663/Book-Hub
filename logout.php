<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

ensureSessionStarted();
unset($_SESSION['auth_user']);
setFlash('success', 'You have logged out successfully.');

header('Location: index.php');
exit;
