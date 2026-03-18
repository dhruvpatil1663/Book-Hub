CREATE DATABASE IF NOT EXISTS book_hub;
USE book_hub;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id INT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    author VARCHAR(120) NOT NULL,
    isbn VARCHAR(20) NOT NULL,
    category VARCHAR(80) NOT NULL,
    book_condition ENUM('New','Like New','Good','Fair') NOT NULL DEFAULT 'Good',
    description TEXT NULL,
    seller_name VARCHAR(120) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    listing_type ENUM('Buy','Resell') NOT NULL DEFAULT 'Resell',
    status ENUM('Available','Sold') NOT NULL DEFAULT 'Available',
    image_path VARCHAR(255) NULL,
    published_year SMALLINT UNSIGNED NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_books_isbn UNIQUE (isbn),
    CONSTRAINT chk_books_price CHECK (price >= 0),
    CONSTRAINT chk_books_stock CHECK (stock >= 0),
    CONSTRAINT fk_books_seller_id FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT UNSIGNED NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_buyer_id FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_orders_total CHECK (total_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    seller_id INT UNSIGNED NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_items_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_book_id FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE RESTRICT,
    CONSTRAINT fk_order_items_seller_id FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_order_items_quantity CHECK (quantity > 0),
    CONSTRAINT chk_order_items_price CHECK (unit_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes that keep list/search operations fast even at 500+ listings.
CREATE INDEX idx_books_title_author ON books (title, author);
CREATE INDEX idx_books_category_status ON books (category, status);
CREATE INDEX idx_books_price_status ON books (price, status);
CREATE INDEX idx_books_created_at ON books (created_at);
CREATE INDEX idx_books_seller_status ON books (seller_id, status);

CREATE INDEX idx_orders_buyer_created_at ON orders (buyer_id, created_at);

CREATE INDEX idx_order_items_seller_created_at ON order_items (seller_id, created_at);
CREATE INDEX idx_order_items_book_id ON order_items (book_id);

INSERT INTO books (
    seller_id,
    title,
    author,
    isbn,
    category,
    book_condition,
    description,
    seller_name,
    price,
    listing_type,
    status,
    published_year,
    stock
) VALUES
 (NULL, 'Atomic Habits', 'James Clear', '9780735211292', 'Self-Help', 'Like New', 'Small habits, big results.', 'Aarav', 12.99, 'Resell', 'Available', 2018, 3),
 (NULL, 'The Pragmatic Programmer', 'Andrew Hunt', '9780135957059', 'Technology', 'Good', 'A practical software classic.', 'Mia', 23.50, 'Resell', 'Available', 2019, 2),
 (NULL, 'Clean Code', 'Robert C. Martin', '9780132350884', 'Technology', 'Fair', 'Guide to writing maintainable code.', 'Liam', 18.00, 'Resell', 'Sold', 2008, 0),
 (NULL, 'Sapiens', 'Yuval Noah Harari', '9780062316097', 'History', 'New', 'A brief history of humankind.', 'Noah', 16.75, 'Buy', 'Available', 2015, 5),
 (NULL, 'The Alchemist', 'Paulo Coelho', '9780061122415', 'Fiction', 'Good', 'A journey of purpose and destiny.', 'Emma', 9.99, 'Resell', 'Available', 1993, 4)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    author = VALUES(author),
    category = VALUES(category),
    seller_name = VALUES(seller_name),
    price = VALUES(price),
    status = VALUES(status),
    stock = VALUES(stock);
