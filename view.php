<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$authUser = currentUser();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid book ID.');
}

$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    exit('Book not found.');
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="card detail-card">
    <h2><?= sanitize((string) $book['title']) ?></h2>
    <?php if (!empty($book['image_path'])): ?>
        <img class="book-cover" src="<?= sanitize((string) $book['image_path']) ?>" alt="<?= sanitize((string) $book['title']) ?>">
    <?php endif; ?>
    <p class="meta">By <?= sanitize((string) $book['author']) ?></p>
    <div class="detail-grid">
        <p><strong>ISBN:</strong> <?= sanitize((string) $book['isbn']) ?></p>
        <p><strong>Category:</strong> <?= sanitize((string) $book['category']) ?></p>
        <p><strong>Condition:</strong> <?= sanitize((string) $book['book_condition']) ?></p>
        <p><strong>Listing Type:</strong> <?= sanitize((string) $book['listing_type']) ?></p>
        <p><strong>Status:</strong> <?= sanitize((string) $book['status']) ?></p>
        <p><strong>Seller:</strong> <?= sanitize((string) $book['seller_name']) ?></p>
        <p><strong>Price:</strong> $<?= number_format((float) $book['price'], 2) ?></p>
        <p><strong>Stock:</strong> <?= (int) $book['stock'] ?></p>
        <p><strong>Published Year:</strong> <?= $book['published_year'] ? (int) $book['published_year'] : 'N/A' ?></p>
        <p><strong>Created At:</strong> <?= sanitize((string) $book['created_at']) ?></p>
    </div>

    <p><strong>Description:</strong></p>
    <p><?= nl2br(sanitize((string) ($book['description'] ?? 'No description provided.'))) ?></p>

    <div class="actions">
        <?php if ($authUser && (int) $authUser['id'] === (int) ($book['seller_id'] ?? 0)): ?>
            <a href="edit.php?id=<?= $id ?>">Edit</a>
        <?php endif; ?>
        <?php if ($book['status'] === 'Available' && (int) $book['stock'] > 0): ?>
            <a href="buy.php?id=<?= $id ?>">Buy This Book</a>
        <?php endif; ?>
        <a class="btn-secondary" href="index.php">Back to Listings</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
