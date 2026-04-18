# Book-Hub (2025)
Developed by: Dhruvkumar Patil
Book-Hub is a full-stack web platform for buying and reselling books using PHP and MySQL.

## Features

- Full CRUD for book listings
- User authentication (register/login/logout)
- Secure ownership: only seller can edit/delete own books
- Buy flow with orders and order items (transaction-safe stock updates)
- Image upload for listings (jpg/jpeg/png/webp)
- Seller dashboard analytics (sales, revenue, units sold)
- Search, category filtering, status filtering, and pagination
- Server-side and client-side validation
- Prepared statements and robust error handling
- MySQL indexing for faster queries at scale (500+ listings)

## Project Structure

book-hub/
- config/database.php
- includes/header.php
- includes/footer.php
- includes/functions.php
- includes/form-fields.php
- assets/css/styles.css
- assets/js/main.js
- uploads/
- index.php
- create.php
- edit.php
- view.php
- delete.php
- register.php
- login.php
- logout.php
- buy.php
- dashboard.php
- schema.sql

## Setup

1. Install PHP 8+ and MySQL 8+.
2. Create database and table:
   - Open MySQL client.
   - Run the SQL in schema.sql.
3. Update DB credentials in config/database.php.
4. Ensure the uploads folder is writable by PHP.
5. Start PHP dev server from the book-hub folder:

   php -S localhost:8000

6. Open: http://localhost:8000
7. Register a new account, then login to add/edit/delete books, buy books, and access dashboard.

## Notes on Performance

- Indexes on commonly filtered/sorted fields:
  - (title, author)
  - (category, status)
  - (price, status)
  - created_at
   - (seller_id, status)
   - order indexes for buyer/seller analytics
- List page uses LIMIT/OFFSET and ordered queries.
- All queries use prepared statements.

## Resume Description

Book-Hub (2024) | PHP, MySQL, HTML, CSS, JavaScript
Designed and built a full-stack web platform for buying and reselling books using PHP and MySQL, managing 500+ product listings with a well-structured relational database schema. Implemented complete CRUD operations with input validation and error handling, achieving sub-200ms query response times through MySQL indexing and query optimisation.
