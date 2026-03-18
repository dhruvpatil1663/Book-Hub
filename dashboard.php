<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$authUser = currentUser();
$sellerId = (int) $authUser['id'];

$summaryStmt = $pdo->prepare('
    SELECT
        COUNT(*) AS total_listings,
        SUM(CASE WHEN status = "Available" THEN 1 ELSE 0 END) AS available_listings,
        SUM(CASE WHEN status = "Sold" THEN 1 ELSE 0 END) AS sold_listings,
        COALESCE(SUM(stock), 0) AS total_stock
    FROM books
    WHERE seller_id = :seller_id
');
$summaryStmt->execute([':seller_id' => $sellerId]);
$summary = $summaryStmt->fetch() ?: [
    'total_listings' => 0,
    'available_listings' => 0,
    'sold_listings' => 0,
    'total_stock' => 0,
];

$revenueStmt = $pdo->prepare('
    SELECT
        COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_revenue,
        COALESCE(SUM(oi.quantity), 0) AS total_units_sold
    FROM order_items oi
    WHERE oi.seller_id = :seller_id
');
$revenueStmt->execute([':seller_id' => $sellerId]);
$revenue = $revenueStmt->fetch() ?: ['total_revenue' => 0, 'total_units_sold' => 0];

$recentOrdersStmt = $pdo->prepare('
    SELECT
        oi.book_id,
        b.title,
        oi.quantity,
        oi.unit_price,
        o.created_at,
        u.name AS buyer_name
    FROM order_items oi
    INNER JOIN orders o ON o.id = oi.order_id
    INNER JOIN books b ON b.id = oi.book_id
    INNER JOIN users u ON u.id = o.buyer_id
    WHERE oi.seller_id = :seller_id
    ORDER BY o.created_at DESC
    LIMIT 10
');
$recentOrdersStmt->execute([':seller_id' => $sellerId]);
$recentOrders = $recentOrdersStmt->fetchAll();

$myListingsStmt = $pdo->prepare('SELECT id, title, price, status, stock, created_at FROM books WHERE seller_id = :seller_id ORDER BY created_at DESC LIMIT 8');
$myListingsStmt->execute([':seller_id' => $sellerId]);
$myListings = $myListingsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="stats-grid">
    <article class="card stat-card">
        <p class="meta">Total Listings</p>
        <h3><?= (int) $summary['total_listings'] ?></h3>
    </article>
    <article class="card stat-card">
        <p class="meta">Available Listings</p>
        <h3><?= (int) $summary['available_listings'] ?></h3>
    </article>
    <article class="card stat-card">
        <p class="meta">Units Sold</p>
        <h3><?= (int) $revenue['total_units_sold'] ?></h3>
    </article>
    <article class="card stat-card">
        <p class="meta">Revenue</p>
        <h3>$<?= number_format((float) $revenue['total_revenue'], 2) ?></h3>
    </article>
</section>

<section class="card detail-card">
    <div class="list-header">
        <h2>Recent Sales</h2>
        <a href="create.php">Add New Listing</a>
    </div>

    <?php if (!$recentOrders): ?>
        <p class="meta">No sales yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Buyer</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><a href="view.php?id=<?= (int) $order['book_id'] ?>"><?= sanitize((string) $order['title']) ?></a></td>
                            <td><?= sanitize((string) $order['buyer_name']) ?></td>
                            <td><?= (int) $order['quantity'] ?></td>
                            <td>$<?= number_format((float) $order['unit_price'], 2) ?></td>
                            <td>$<?= number_format((float) $order['unit_price'] * (int) $order['quantity'], 2) ?></td>
                            <td><?= sanitize((string) $order['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card detail-card">
    <h2>My Latest Listings</h2>

    <?php if (!$myListings): ?>
        <p class="meta">No listings yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Stock</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myListings as $listing): ?>
                        <tr>
                            <td><?= sanitize((string) $listing['title']) ?></td>
                            <td>$<?= number_format((float) $listing['price'], 2) ?></td>
                            <td><?= sanitize((string) $listing['status']) ?></td>
                            <td><?= (int) $listing['stock'] ?></td>
                            <td><?= sanitize((string) $listing['created_at']) ?></td>
                            <td>
                                <a href="view.php?id=<?= (int) $listing['id'] ?>">View</a>
                                <a href="edit.php?id=<?= (int) $listing['id'] ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
