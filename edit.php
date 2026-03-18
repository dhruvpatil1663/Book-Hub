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

$errors = [];

$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    exit('Book not found.');
}

if ((int) ($book['seller_id'] ?? 0) !== (int) ($authUser['id'] ?? 0)) {
    http_response_code(403);
    exit('You can only edit your own listings.');
}

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
    $_POST['existing_image'] = trim((string) ($_POST['existing_image'] ?? (string) ($book['image_path'] ?? '')));

    $upload = handleImageUpload($_FILES['image'] ?? []);
    if ($upload['error']) {
        $errors['image'] = $upload['error'];
    }

    $errors = array_merge($errors, validateBookInput($data, true));

    if (!$errors) {
        $imagePath = $upload['path'] ?: ($_POST['existing_image'] !== '' ? $_POST['existing_image'] : null);

        $sql = 'UPDATE books
                SET title = :title,
                    author = :author,
                    isbn = :isbn,
                    category = :category,
                    book_condition = :book_condition,
                    description = :description,
                    seller_name = :seller_name,
                    price = :price,
                    listing_type = :listing_type,
                    status = :status,
                    image_path = :image_path,
                    published_year = :published_year,
                    stock = :stock
                WHERE id = :id';

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
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
                ':image_path' => $imagePath,
                ':published_year' => $data['published_year'] !== '' ? (int) $data['published_year'] : null,
                ':stock' => (int) $data['stock'],
                ':id' => $id,
            ]);

            if ($upload['path'] && !empty($book['image_path']) && is_file(__DIR__ . '/' . $book['image_path'])) {
                @unlink(__DIR__ . '/' . $book['image_path']);
            }

            setFlash('success', 'Book listing updated successfully.');
            header('Location: view.php?id=' . $id);
            exit;
        } catch (PDOException $exception) {
            if (isDuplicateIsbnError($exception)) {
                $errors['isbn'] = 'This ISBN already exists in the database.';
            } else {
                $errors['general'] = 'Unable to update the book right now. Please try again.';
            }

            if ($upload['path']) {
                @unlink(__DIR__ . '/' . $upload['path']);
            }
        }
    }
} else {
    $_POST = [
        'title' => (string) $book['title'],
        'author' => (string) $book['author'],
        'isbn' => (string) $book['isbn'],
        'category' => (string) $book['category'],
        'book_condition' => (string) $book['book_condition'],
        'description' => (string) ($book['description'] ?? ''),
        'seller_name' => (string) $book['seller_name'],
        'price' => (string) $book['price'],
        'listing_type' => (string) $book['listing_type'],
        'status' => (string) $book['status'],
        'existing_image' => (string) ($book['image_path'] ?? ''),
        'published_year' => (string) ($book['published_year'] ?? ''),
        'stock' => (string) $book['stock'],
    ];
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="card form-card">
    <h2>Edit Book Listing</h2>

    <?php if (!empty($errors['general'])): ?>
        <p class="alert error"><?= sanitize($errors['general']) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="grid-form" data-validate-book>
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php include __DIR__ . '/includes/form-fields.php'; ?>
        <div class="actions full-row">
            <button type="submit">Update Listing</button>
            <a class="btn-secondary" href="view.php?id=<?= $id ?>">Cancel</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
