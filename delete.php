<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$authUser = currentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid book ID.');
}

$findStmt = $pdo->prepare('SELECT seller_id, image_path FROM books WHERE id = :id');
$findStmt->execute([':id' => $id]);
$book = $findStmt->fetch();

if (!$book) {
    http_response_code(404);
    exit('Book not found.');
}

if ((int) ($book['seller_id'] ?? 0) !== (int) ($authUser['id'] ?? 0)) {
    http_response_code(403);
    exit('You can only delete your own listing.');
}

$stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
$stmt->execute([':id' => $id]);

if (!empty($book['image_path']) && is_file(__DIR__ . '/' . $book['image_path'])) {
    @unlink(__DIR__ . '/' . $book['image_path']);
}

setFlash('success', 'Book listing deleted successfully.');
header('Location: index.php');
exit;
