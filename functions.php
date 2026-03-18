<?php

declare(strict_types=1);

function ensureSessionStarted(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function currentUser(): ?array
{
    ensureSessionStarted();
    return $_SESSION['auth_user'] ?? null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to continue.');
        header('Location: login.php');
        exit;
    }
}

function setFlash(string $type, string $message): void
{
    ensureSessionStarted();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    ensureSessionStarted();
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function old(array $source, string $key): string
{
    return isset($source[$key]) ? sanitize((string) $source[$key]) : '';
}

function validateBookInput(array $input, bool $isUpdate = false): array
{
    $errors = [];

    $required = ['title', 'author', 'isbn', 'category', 'book_condition', 'seller_name', 'price', 'listing_type', 'status', 'stock'];

    foreach ($required as $field) {
        if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
            $errors[$field] = 'This field is required.';
        }
    }

    if (!empty($input['isbn']) && !preg_match('/^[0-9Xx\-]{10,20}$/', (string) $input['isbn'])) {
        $errors['isbn'] = 'ISBN should be 10-20 chars and contain numbers, X, or dash.';
    }

    if (isset($input['price']) && (!is_numeric($input['price']) || (float) $input['price'] < 0)) {
        $errors['price'] = 'Price must be a positive number.';
    }

    if (isset($input['stock']) && filter_var($input['stock'], FILTER_VALIDATE_INT) === false) {
        $errors['stock'] = 'Stock must be an integer.';
    }

    if (isset($input['stock']) && (int) $input['stock'] < 0) {
        $errors['stock'] = 'Stock cannot be negative.';
    }

    if (!empty($input['published_year'])) {
        $currentYear = (int) date('Y');
        if (filter_var($input['published_year'], FILTER_VALIDATE_INT) === false || (int) $input['published_year'] < 1450 || (int) $input['published_year'] > $currentYear) {
            $errors['published_year'] = 'Published year is invalid.';
        }
    }

    $allowedConditions = ['New', 'Like New', 'Good', 'Fair'];
    $allowedListingTypes = ['Buy', 'Resell'];
    $allowedStatus = ['Available', 'Sold'];

    if (!empty($input['book_condition']) && !in_array($input['book_condition'], $allowedConditions, true)) {
        $errors['book_condition'] = 'Invalid condition selected.';
    }

    if (!empty($input['listing_type']) && !in_array($input['listing_type'], $allowedListingTypes, true)) {
        $errors['listing_type'] = 'Invalid listing type selected.';
    }

    if (!empty($input['status']) && !in_array($input['status'], $allowedStatus, true)) {
        $errors['status'] = 'Invalid status selected.';
    }

    return $errors;
}

function validateAuthInput(array $input, bool $isRegistration = true): array
{
    $errors = [];

    if (empty(trim((string) ($input['name'] ?? '')))) {
        $errors['name'] = 'Name is required.';
    }

    if (empty(trim((string) ($input['email'] ?? '')))) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email format is invalid.';
    }

    if (empty((string) ($input['password'] ?? ''))) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen((string) $input['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($isRegistration) {
        if (empty((string) ($input['confirm_password'] ?? ''))) {
            $errors['confirm_password'] = 'Confirm password is required.';
        } elseif ((string) $input['password'] !== (string) $input['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
    }

    return $errors;
}

function handleImageUpload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Image upload failed.'];
    }

    $tmpName = $file['tmp_name'] ?? '';
    $size = (int) ($file['size'] ?? 0);
    $originalName = (string) ($file['name'] ?? '');

    if ($size > 2 * 1024 * 1024) {
        return ['path' => null, 'error' => 'Image must be 2MB or smaller.'];
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $allowed, true)) {
        return ['path' => null, 'error' => 'Allowed image types: jpg, jpeg, png, webp.'];
    }

    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
        return ['path' => null, 'error' => 'Unable to initialize upload directory.'];
    }

    $filename = uniqid('book_', true) . '.' . $extension;
    $targetPath = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        return ['path' => null, 'error' => 'Unable to store uploaded image.'];
    }

    return ['path' => 'uploads/' . $filename, 'error' => null];
}

function isDuplicateIsbnError(PDOException $exception): bool
{
    return isset($exception->errorInfo[1]) && (int) $exception->errorInfo[1] === 1062;
}
