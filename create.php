<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$authUser = currentUser();

$errors = [];

$_POST['seller_name'] = (string) ($authUser['name'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'author' => trim((string) ($_POST['author'] ?? '')),
        'isbn' => trim((string) ($_POST['isbn'] ?? '')),
        'category' => trim((string) ($_POST['category'] ?? '')),
        'book_condition' => trim((string) ($_POST['book_condition'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'seller_name' => (string) ($authUser['name'] ?? ''),
        'price' => trim((string) ($_POST['price'] ?? '')),
        'listing_type' => trim((string) ($_POST['listing_type'] ?? '')),
        'status' => trim((string) ($_POST['status'] ?? '')),
        'published_year' => trim((string) ($_POST['published_year'] ?? '')),
        'stock' => trim((string) ($_POST['stock'] ?? '')),
    ];

    $_POST['seller_name'] = $data['seller_name'];

    $upload = handleImageUpload($_FILES['image'] ?? []);
    if ($upload['error']) {
        $errors['image'] = $upload['error'];
    }

    $errors = array_merge($errors, validateBookInput($data));

    if (!$errors) {
        $sql = 'INSERT INTO books (seller_id, title, author, isbn, category, book_condition, description, seller_name, price, listing_type, status, image_path, published_year, stock)
                VALUES (:seller_id, :title, :author, :isbn, :category, :book_condition, :description, :seller_name, :price, :listing_type, :status, :image_path, :published_year, :stock)';

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':seller_id' => (int) $authUser['id'],
                ':title' => $data['title'],
                ':author' => $data['author'],
                ':isbn' => $data['isbn'],
                ':category' => $data['category'],
                ':book_condition' => $data['book_condition'],
                ':description' => $data['description'] !== '' ? $data['description'] : null,
                ':seller_name' => $data['seller_name'],
                ':price' => (float) $data['price'],
                ':listing_type' => $data['listing_type'],
                ':status' => $data['status'],
                ':image_path' => $upload['path'],
                ':published_year' => $data['published_year'] !== '' ? (int) $data['published_year'] : null,
                ':stock' => (int) $data['stock'],
            ]);

            setFlash('success', 'Book listing created successfully.');
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            if (isDuplicateIsbnError($exception)) {
                $errors['isbn'] = 'This ISBN already exists in the database.';
            } else {
                $errors['general'] = 'Unable to save the book right now. Please try again.';
            }

            if ($upload['path']) {
                @unlink(__DIR__ . '/' . $upload['path']);
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="card form-card">
    <h2>Add New Book Listing</h2>

    <?php if (!empty($errors['general'])): ?>
        <p class="alert error"><?= sanitize($errors['general']) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="grid-form" data-validate-book>
        <?php include __DIR__ . '/includes/form-fields.php'; ?>
        <div class="actions full-row">
            <button type="submit">Create Listing</button>
            <a class="btn-secondary" href="index.php">Cancel</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
