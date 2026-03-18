<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    }
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $errors['general'] = 'Invalid email or password.';
        } else {
            ensureSessionStarted();
            $_SESSION['auth_user'] = [
                'id' => (int) $user['id'],
                'name' => (string) $user['name'],
                'email' => (string) $user['email'],
            ];

            setFlash('success', 'Welcome back, ' . (string) $user['name'] . '.');
            header('Location: index.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="card form-card auth-card">
    <h2>Login</h2>

    <?php if (!empty($errors['general'])): ?>
        <p class="alert error"><?= sanitize($errors['general']) ?></p>
    <?php endif; ?>

    <form method="post" class="grid-form auth-form">
        <div class="full-row">
            <label for="email">Email *</label>
            <input id="email" name="email" type="email" maxlength="160" value="<?= old($_POST, 'email') ?>" required>
            <small class="error-text"><?= sanitize($errors['email'] ?? '') ?></small>
        </div>
        <div class="full-row">
            <label for="password">Password *</label>
            <input id="password" name="password" type="password" minlength="8" required>
            <small class="error-text"><?= sanitize($errors['password'] ?? '') ?></small>
        </div>
        <div class="actions full-row">
            <button type="submit">Login</button>
            <a class="btn-secondary" href="register.php">Create account</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
