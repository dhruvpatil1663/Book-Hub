<?php

declare(strict_types=1);

$conditions = ['New', 'Like New', 'Good', 'Fair'];
$listingTypes = ['Buy', 'Resell'];
$statuses = ['Available', 'Sold'];
?>

<div>
    <label for="title">Title *</label>
    <input id="title" name="title" type="text" maxlength="180" value="<?= old($_POST, 'title') ?>" required>
    <small class="error-text"><?= sanitize($errors['title'] ?? '') ?></small>
</div>
<div>
    <label for="author">Author *</label>
    <input id="author" name="author" type="text" maxlength="120" value="<?= old($_POST, 'author') ?>" required>
    <small class="error-text"><?= sanitize($errors['author'] ?? '') ?></small>
</div>
<div>
    <label for="isbn">ISBN *</label>
    <input id="isbn" name="isbn" type="text" maxlength="20" value="<?= old($_POST, 'isbn') ?>" required>
    <small class="error-text"><?= sanitize($errors['isbn'] ?? '') ?></small>
</div>
<div>
    <label for="category">Category *</label>
    <input id="category" name="category" type="text" maxlength="80" value="<?= old($_POST, 'category') ?>" required>
    <small class="error-text"><?= sanitize($errors['category'] ?? '') ?></small>
</div>
<div>
    <label for="book_condition">Condition *</label>
    <select id="book_condition" name="book_condition" required>
        <option value="">Select</option>
        <?php foreach ($conditions as $condition): ?>
            <option value="<?= $condition ?>" <?= old($_POST, 'book_condition') === $condition ? 'selected' : '' ?>><?= $condition ?></option>
        <?php endforeach; ?>
    </select>
    <small class="error-text"><?= sanitize($errors['book_condition'] ?? '') ?></small>
</div>
<div>
    <label for="seller_name">Seller Name *</label>
    <input id="seller_name" name="seller_name" type="text" maxlength="120" value="<?= old($_POST, 'seller_name') ?>" readonly required>
    <small class="error-text"><?= sanitize($errors['seller_name'] ?? '') ?></small>
</div>
<div>
    <label for="price">Price (USD) *</label>
    <input id="price" name="price" type="number" min="0" step="0.01" value="<?= old($_POST, 'price') ?>" required>
    <small class="error-text"><?= sanitize($errors['price'] ?? '') ?></small>
</div>
<div>
    <label for="stock">Stock *</label>
    <input id="stock" name="stock" type="number" min="0" step="1" value="<?= old($_POST, 'stock') ?>" required>
    <small class="error-text"><?= sanitize($errors['stock'] ?? '') ?></small>
</div>
<div>
    <label for="listing_type">Listing Type *</label>
    <select id="listing_type" name="listing_type" required>
        <option value="">Select</option>
        <?php foreach ($listingTypes as $type): ?>
            <option value="<?= $type ?>" <?= old($_POST, 'listing_type') === $type ? 'selected' : '' ?>><?= $type ?></option>
        <?php endforeach; ?>
    </select>
    <small class="error-text"><?= sanitize($errors['listing_type'] ?? '') ?></small>
</div>
<div>
    <label for="status">Status *</label>
    <select id="status" name="status" required>
        <option value="">Select</option>
        <?php foreach ($statuses as $itemStatus): ?>
            <option value="<?= $itemStatus ?>" <?= old($_POST, 'status') === $itemStatus ? 'selected' : '' ?>><?= $itemStatus ?></option>
        <?php endforeach; ?>
    </select>
    <small class="error-text"><?= sanitize($errors['status'] ?? '') ?></small>
</div>
<div>
    <label for="published_year">Published Year</label>
    <input id="published_year" name="published_year" type="number" min="1450" max="<?= (int) date('Y') ?>" value="<?= old($_POST, 'published_year') ?>">
    <small class="error-text"><?= sanitize($errors['published_year'] ?? '') ?></small>
</div>
<div class="full-row">
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4" maxlength="1000"><?= old($_POST, 'description') ?></textarea>
</div>
<div class="full-row">
    <label for="image">Book Image</label>
    <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp">
    <?php if (!empty($_POST['existing_image'])): ?>
        <p class="meta">Current image: <?= sanitize((string) $_POST['existing_image']) ?></p>
        <input type="hidden" name="existing_image" value="<?= old($_POST, 'existing_image') ?>">
    <?php endif; ?>
    <small class="error-text"><?= sanitize($errors['image'] ?? '') ?></small>
</div>
