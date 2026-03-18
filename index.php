<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$authUser = currentUser();

$search = trim((string) ($_GET['search'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(title LIKE :search OR author LIKE :search OR isbn LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($category !== '') {
    $where[] = 'category = :category';
    $params[':category'] = $category;
}

if ($status !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM books {$whereSql}");
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalRows = (int) ($countStmt->fetch()['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$listSql = "
    SELECT id, seller_id, title, author, isbn, category, book_condition, seller_name, price, listing_type, status, stock, image_path, created_at
    FROM books
    {$whereSql}
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset
";

$listStmt = $pdo->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$books = $listStmt->fetchAll();

$categories = $pdo->query('SELECT DISTINCT category FROM books ORDER BY category ASC')->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/header.php';
?>

<section class="card filter-card">
    <form method="get" class="grid-form">
        <div>
            <label for="search">Search</label>
            <input id="search" name="search" type="text" value="<?= sanitize($search) ?>" placeholder="Title, author, ISBN">
        </div>
        <div>
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">All</option>
                <?php foreach ($categories as $item): ?>
                    <option value="<?= sanitize((string) $item) ?>" <?= $category === $item ? 'selected' : '' ?>><?= sanitize((string) $item) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                <option value="Available" <?= $status === 'Available' ? 'selected' : '' ?>>Available</option>
                <option value="Sold" <?= $status === 'Sold' ? 'selected' : '' ?>>Sold</option>
            </select>
        </div>
        <div class="actions">
            <button type="submit">Filter</button>
            <a class="btn-secondary" href="index.php">Reset</a>
        </div>
    </form>
</section>

<section>
    <div class="list-header">
        <h2>Book Listings</h2>
        <p><?= $totalRows ?> result(s)</p>
    </div>

    <?php if (!$books): ?>
        <div class="card empty-state">No books found. Try adding a new listing.</div>
    <?php else: ?>
        <div class="grid-cards">
            <?php foreach ($books as $book): ?>
                <article class="card book-card">
                    <?php if (!empty($book['image_path'])): ?>
                        <img class="book-thumb" src="<?= sanitize((string) $book['image_path']) ?>" alt="<?= sanitize((string) $book['title']) ?>">
                    <?php endif; ?>
                    <span class="pill <?= $book['status'] === 'Available' ? 'ok' : 'muted' ?>"><?= sanitize((string) $book['status']) ?></span>
                    <h3><?= sanitize((string) $book['title']) ?></h3>
                    <p class="meta">By <?= sanitize((string) $book['author']) ?> | <?= sanitize((string) $book['category']) ?></p>
                    <p class="price">$<?= number_format((float) $book['price'], 2) ?></p>
                    <p class="meta">Seller: <?= sanitize((string) $book['seller_name']) ?> | Stock: <?= (int) $book['stock'] ?></p>
                    <div class="card-actions">
                        <a href="view.php?id=<?= (int) $book['id'] ?>">View</a>
                        <?php if ($authUser && (int) $authUser['id'] === (int) $book['seller_id']): ?>
                            <a href="edit.php?id=<?= (int) $book['id'] ?>">Edit</a>
                            <form method="post" action="delete.php" data-confirm-delete>
                                <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                                <button type="submit" class="danger">Delete</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($book['status'] === 'Available' && (int) $book['stock'] > 0): ?>
                            <a href="buy.php?id=<?= (int) $book['id'] ?>">Buy</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php
                $query = $_GET;
                $query['page'] = $i;
                $url = 'index.php?' . http_build_query($query);
                ?>
                <a href="<?= sanitize($url) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
