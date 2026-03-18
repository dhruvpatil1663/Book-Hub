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
    $data = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'password' => (string) ($_POST['password'] ?? ''),
        'confirm_password' => (string) ($_POST['confirm_password'] ?? ''),
    ];

    $errors = validateAuthInput($data, true);

    if (!$errors) {
        $sql = 'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)';

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':email' => strtolower($data['email']),
                ':password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            ]);

            setFlash('success', 'Registration successful. Please login.');
            header('Location: login.php');
            exit;
        } catch (PDOException $exception) {
            if (isset($exception->errorInfo[1]) && (int) $exception->errorInfo[1] === 1062) {
                $errors['email'] = 'An account with this email already exists.';
            } else {
                $errors['general'] = 'Unable to register right now. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="card form-card auth-card">
    <h2>Create Account</h2>

    <?php if (!empty($errors['general'])): ?>
        <p class="alert error"><?= sanitize($errors['general']) ?></p>
    <?php endif; ?>

    <form method="post" class="grid-form auth-form">
        <div class="full-row">
            <label for="name">Full Name *</label>
            <input id="name" name="name" type="text" maxlength="120" value="<?= old($_POST, 'name') ?>" required>
            <small class="error-text"><?= sanitize($errors['name'] ?? '') ?></small>
        </div>
        <div class="full-row">
            <label for="email">Email *</label>
            <input id="email" name="email" type="email" maxlength="160" value="<?= old($_POST, 'email') ?>" required>
            <small class="error-text"><?= sanitize($errors['email'] ?? '') ?></small>
        </div>
        <div>
            <label for="password">Password *</label>
            <input id="password" name="password" type="password" minlength="8" required>
            <small class="error-text"><?= sanitize($errors['password'] ?? '') ?></small>
        </div>
        <div>
            <label for="confirm_password">Confirm Password *</label>
            <input id="confirm_password" name="confirm_password" type="password" minlength="8" required>
            <small class="error-text"><?= sanitize($errors['confirm_password'] ?? '') ?></small>
        </div>
        <div class="actions full-row">
            <button type="submit">Register</button>
            <a class="btn-secondary" href="login.php">Already have an account?</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
