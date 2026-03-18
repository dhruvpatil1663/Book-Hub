<?php

declare(strict_types=1);

$authUser = currentUser();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book-Hub</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <h1>Book-Hub</h1>
        <nav>
            <a href="index.php">Listings</a>
            <?php if ($authUser): ?>
                <a href="create.php">Add Book</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Logout (<?= sanitize((string) $authUser['name']) ?>)</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
    <?php if ($flash): ?>
        <p class="alert <?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= sanitize((string) $flash['message']) ?></p>
    <?php endif; ?>
