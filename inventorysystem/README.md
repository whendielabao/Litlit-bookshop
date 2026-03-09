# Bookshop Inventory System

A complete inventory management system for bookshops built with PHP and MySQL.

## Features

✅ Add new books with validation
✅ Add categories and publishers
✅ Add users
✅ View all books in inventory
✅ Responsive design
✅ Modular code structure
✅ Input validation and sanitization
✅ Success/error feedback messages

## Setup Instructions

1. **Create the database:**
   ```bash
   mysql -u root -p < create.sql
   ```

2. **Configure database connection:**
   Edit `config.php` and update the database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'bookshop_inventory');
   ```

3. **Access the system:**
   Open your browser and navigate to:
   ```
   http://localhost/inventorysystem/
   ```

## File Structure

