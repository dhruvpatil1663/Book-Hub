<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$authUser = currentUser();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid book ID.');
}

$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    exit('Book not found.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = (int) ($_POST['quantity'] ?? 0);

    if ($quantity <= 0) {
        $errors['quantity'] = 'Quantity must be greater than zero.';
    }

    if ($quantity > (int) $book['stock']) {
        $errors['quantity'] = 'Requested quantity exceeds stock.';
    }

    if ((int) ($book['seller_id'] ?? 0) === (int) $authUser['id']) {
        $errors['general'] = 'You cannot buy your own listing.';
    }

    if ($book['status'] !== 'Available' || (int) $book['stock'] <= 0) {
        $errors['general'] = 'This listing is not available for purchase.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $lockStmt = $pdo->prepare('SELECT id, seller_id, price, stock, status FROM books WHERE id = :id FOR UPDATE');
            $lockStmt->execute([':id' => $id]);
            $lockedBook = $lockStmt->fetch();

            if (!$lockedBook || $lockedBook['status'] !== 'Available' || (int) $lockedBook['stock'] < $quantity) {
                throw new RuntimeException('Stock changed. Please try again.');
            }

            $unitPrice = (float) $lockedBook['price'];
            $totalAmount = $unitPrice * $quantity;
            $newStock = (int) $lockedBook['stock'] - $quantity;
            $newStatus = $newStock === 0 ? 'Sold' : 'Available';

            $orderStmt = $pdo->prepare('INSERT INTO orders (buyer_id, total_amount) VALUES (:buyer_id, :total_amount)');
            $orderStmt->execute([
                ':buyer_id' => (int) $authUser['id'],
                ':total_amount' => $totalAmount,
            ]);

            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, book_id, seller_id, quantity, unit_price) VALUES (:order_id, :book_id, :seller_id, :quantity, :unit_price)');
            $itemStmt->execute([
                ':order_id' => $orderId,
                ':book_id' => $id,
                ':seller_id' => (int) ($lockedBook['seller_id'] ?? 0),
                ':quantity' => $quantity,
                ':unit_price' => $unitPrice,
            ]);

            $updateBookStmt = $pdo->prepare('UPDATE books SET stock = :stock, status = :status WHERE id = :id');
            $updateBookStmt->execute([
                ':stock' => $newStock,
                ':status' => $newStatus,
                ':id' => $id,
            ]);

            $pdo->commit();

            setFlash('success', 'Order placed successfully. Total: $' . number_format($totalAmount, 2));
            header('Location: view.php?id=' . $id);
            exit;
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors['general'] = $throwable->getMessage() ?: 'Unable to place order right now.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="card form-card auth-card">
    <h2>Buy Book</h2>

    <p><strong><?= sanitize((string) $book['title']) ?></strong> by <?= sanitize((string) $book['author']) ?></p>
    <p class="meta">Price: $<?= number_format((float) $book['price'], 2) ?> | Available stock: <?= (int) $book['stock'] ?></p>

    <?php if (!empty($errors['general'])): ?>
        <p class="alert error"><?= sanitize($errors['general']) ?></p>
    <?php endif; ?>

    <form method="post" class="grid-form auth-form">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="full-row">
            <label for="quantity">Quantity *</label>
            <input id="quantity" name="quantity" type="number" min="1" max="<?= (int) $book['stock'] ?>" value="<?= old($_POST, 'quantity') !== '' ? old($_POST, 'quantity') : '1' ?>" required>
            <small class="error-text"><?= sanitize($errors['quantity'] ?? '') ?></small>
        </div>
        <div class="actions full-row">
            <button type="submit">Confirm Purchase</button>
            <a class="btn-secondary" href="view.php?id=<?= $id ?>">Cancel</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
