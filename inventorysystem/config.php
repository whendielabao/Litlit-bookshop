<?php
/**
 * Database Configuration File
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bookshop_inventory');

// Create connection
function getDBConnection() {
    // First, connect without specifying a database to create the database if needed
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if (!$conn->query($sql)) {
        die("Error creating database: " . $conn->error);
    }
    
    // Now select the database
    if (!$conn->select_db(DB_NAME)) {
        die("Error selecting database: " . $conn->error);
    }
    
    $conn->set_charset("utf8");
    
    // Create tables if they don't exist
    createTablesIfNotExist($conn);
    
    return $conn;
}

// Create tables if they don't exist
function createTablesIfNotExist($conn) {
    // Check if Books table exists
    $result = $conn->query("SHOW TABLES LIKE 'Books'");
    if ($result && $result->num_rows > 0) {
        // Tables exist, but check if we need to add missing columns to Users table
        $conn->query("ALTER TABLE `Users` MODIFY COLUMN `name` VARCHAR(100)");
        $conn->query("ALTER TABLE `Users` MODIFY COLUMN `email` VARCHAR(100)");
        
        // Add password column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `Users` LIKE 'password'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `Users` ADD COLUMN `password` VARCHAR(255)");
        }
        
        // Add created_at column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `Users` LIKE 'created_at'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `Users` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
        
        // Add role column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `Users` LIKE 'role'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `Users` ADD COLUMN `role` ENUM('admin','clerk') NOT NULL DEFAULT 'clerk'");
            // Make first user an admin
            $conn->query("UPDATE `Users` SET `role` = 'admin' WHERE `users_id` = (SELECT min_id FROM (SELECT MIN(users_id) as min_id FROM `Users`) t)");
        }
        
        // Add book_cover column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `Books` LIKE 'book_cover'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `Books` ADD COLUMN `book_cover` VARCHAR(500) DEFAULT NULL");
        }
        
        // Add author_photo column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `Books` LIKE 'author_photo'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `Books` ADD COLUMN `author_photo` VARCHAR(500) DEFAULT NULL");
        }
        
        // Add added_by column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `Books` LIKE 'added_by'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `Books` ADD COLUMN `added_by` INTEGER DEFAULT NULL");
        }
        
        // Add added_at column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `Books` LIKE 'added_at'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `Books` ADD COLUMN `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
        
        // Add isbn column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `Books` LIKE 'isbn'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `Books` ADD COLUMN `isbn` VARCHAR(20) DEFAULT NULL");
        }

        // Add book serial column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `Books` LIKE 'book_serial'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `Books` ADD COLUMN `book_serial` VARCHAR(30) DEFAULT NULL AFTER `book_id`");
        }
        
        // Add description column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `Books` LIKE 'description'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `Books` ADD COLUMN `description` TEXT DEFAULT NULL");
        }
        
        // Add author_bio column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `Books` LIKE 'author_bio'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `Books` ADD COLUMN `author_bio` TEXT DEFAULT NULL");
        }
        
        // Create SoldHistory table if it doesn't exist
        $conn->query("CREATE TABLE IF NOT EXISTS `SoldHistory` (
            `sold_id` INTEGER NOT NULL AUTO_INCREMENT,
            `sold_serial` VARCHAR(30) DEFAULT NULL,
            `book_id` INTEGER NOT NULL,
            `quantity` INTEGER NOT NULL,
            `price_at_sale` DECIMAL(10,2) NOT NULL,
            `sold_by` INTEGER DEFAULT NULL,
            `sold_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `PK_SoldHistory` PRIMARY KEY (`sold_id`)
        )");

        // Create InventoryMovements table if it doesn't exist
        $conn->query("CREATE TABLE IF NOT EXISTS `InventoryMovements` (
            `movement_id` INTEGER NOT NULL AUTO_INCREMENT,
            `book_id` INTEGER NOT NULL,
            `movement_type` ENUM('stock_in','stock_out','restock','pull_out') NOT NULL,
            `quantity_before` INTEGER NOT NULL DEFAULT 0,
            `quantity_change` INTEGER NOT NULL,
            `quantity_after` INTEGER NOT NULL DEFAULT 0,
            `notes` VARCHAR(255) DEFAULT NULL,
            `damage_remarks` VARCHAR(255) DEFAULT NULL,
            `moved_by` INTEGER DEFAULT NULL,
            `moved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `PK_InventoryMovements` PRIMARY KEY (`movement_id`)
        )");

        $result = $conn->query("SHOW COLUMNS FROM `InventoryMovements` LIKE 'damage_remarks'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `InventoryMovements` ADD COLUMN `damage_remarks` VARCHAR(255) DEFAULT NULL AFTER `notes`");
        }

        $result = $conn->query("SHOW COLUMNS FROM `SoldHistory` LIKE 'sold_serial'");
        if (!$result || $result->num_rows == 0) {
            $conn->query("ALTER TABLE `SoldHistory` ADD COLUMN `sold_serial` VARCHAR(30) DEFAULT NULL AFTER `sold_id`");
        }

        backfillSerialColumn($conn, 'Books', 'book_id', 'book_serial', 'BK');
        backfillSerialColumn($conn, 'SoldHistory', 'sold_id', 'sold_serial', 'SL');
        ensureUniqueIndex($conn, 'Books', 'uniq_books_book_serial', 'book_serial');
        ensureUniqueIndex($conn, 'SoldHistory', 'uniq_soldhistory_sold_serial', 'sold_serial');

        // Ensure default admin account exists
        $adminHash = '$2y$10$bqDrGu3HyMsXWAqoCpYcrumDaaplDkK0Ff5VDQ4XtUR5Umsv.9lHS';
        $conn->query("INSERT IGNORE INTO `Users` (name, email, password, role)
            SELECT 'Admin','admin@bookshop.com','$adminHash','admin'
            FROM DUAL WHERE NOT EXISTS (
                SELECT 1 FROM `Users` WHERE email='admin@bookshop.com'
            )");

        return; // Tables already exist
    }
    
    // Create Users table
    $conn->query("CREATE TABLE IF NOT EXISTS `Users` (
        `users_id` INTEGER NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100),
        `email` VARCHAR(100) UNIQUE,
        `password` VARCHAR(255),
        `role` ENUM('admin','clerk') NOT NULL DEFAULT 'clerk',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `PK_Users` PRIMARY KEY (`users_id`)
    )");
    
    // Create Category table
    $conn->query("CREATE TABLE IF NOT EXISTS `Category` (
        `category_id` INTEGER NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(40),
        `description` VARCHAR(40),
        CONSTRAINT `PK_Category` PRIMARY KEY (`category_id`)
    )");
    
    // Create publisher table
    $conn->query("CREATE TABLE IF NOT EXISTS `publisher` (
        `publisher` VARCHAR(40) NOT NULL,
        `name` VARCHAR(40),
        `contact_info` VARCHAR(40),
        CONSTRAINT `PK_publisher` PRIMARY KEY (`publisher`)
    )");
    
    // Create Sales table
    $conn->query("CREATE TABLE IF NOT EXISTS `Sales` (
        `sales_id` INTEGER NOT NULL AUTO_INCREMENT,
        `sales_date` VARCHAR(40),
        `users_id` INTEGER NOT NULL,
        CONSTRAINT `PK_Sales` PRIMARY KEY (`sales_id`)
    )");
    
    // Create Books table
    $conn->query("CREATE TABLE IF NOT EXISTS `Books` (
        `book_id` INTEGER NOT NULL AUTO_INCREMENT,
        `book_serial` VARCHAR(30) DEFAULT NULL,
        `isbn` VARCHAR(20) DEFAULT NULL,
        `title` VARCHAR(255),
        `author` VARCHAR(255),
        `description` TEXT DEFAULT NULL,
        `author_bio` TEXT DEFAULT NULL,
        `price` DECIMAL(10,2),
        `quantity` INTEGER DEFAULT 0,
        `category_id` INTEGER NOT NULL,
        `publisher` VARCHAR(40),
        `sales_id` INTEGER,
        `book_cover` VARCHAR(500) DEFAULT NULL,
        `author_photo` VARCHAR(500) DEFAULT NULL,
        `added_by` INTEGER DEFAULT NULL,
        `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `PK_Books` PRIMARY KEY (`book_id`)
    )");
    
    // Create Sales item table
    $conn->query("CREATE TABLE IF NOT EXISTS `Sales item` (
        `sale_id` INTEGER NOT NULL AUTO_INCREMENT,
        `quantity` INTEGER,
        `publisher` VARCHAR(40) NOT NULL,
        `category_id` INTEGER NOT NULL,
        CONSTRAINT `PK_Sales item` PRIMARY KEY (`sale_id`)
    )");

    // Create SoldHistory table
    $conn->query("CREATE TABLE IF NOT EXISTS `SoldHistory` (
        `sold_id` INTEGER NOT NULL AUTO_INCREMENT,
        `sold_serial` VARCHAR(30) DEFAULT NULL,
        `book_id` INTEGER NOT NULL,
        `quantity` INTEGER NOT NULL,
        `price_at_sale` DECIMAL(10,2) NOT NULL,
        `sold_by` INTEGER DEFAULT NULL,
        `sold_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `PK_SoldHistory` PRIMARY KEY (`sold_id`)
    )");

    // Create inventory movement table
    $conn->query("CREATE TABLE IF NOT EXISTS `InventoryMovements` (
        `movement_id` INTEGER NOT NULL AUTO_INCREMENT,
        `book_id` INTEGER NOT NULL,
        `movement_type` ENUM('stock_in','stock_out','restock','pull_out') NOT NULL,
        `quantity_before` INTEGER NOT NULL DEFAULT 0,
        `quantity_change` INTEGER NOT NULL,
        `quantity_after` INTEGER NOT NULL DEFAULT 0,
        `notes` VARCHAR(255) DEFAULT NULL,
        `damage_remarks` VARCHAR(255) DEFAULT NULL,
        `moved_by` INTEGER DEFAULT NULL,
        `moved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `PK_InventoryMovements` PRIMARY KEY (`movement_id`)
    )");

    backfillSerialColumn($conn, 'Books', 'book_id', 'book_serial', 'BK');
    backfillSerialColumn($conn, 'SoldHistory', 'sold_id', 'sold_serial', 'SL');
    ensureUniqueIndex($conn, 'Books', 'uniq_books_book_serial', 'book_serial');
    ensureUniqueIndex($conn, 'SoldHistory', 'uniq_soldhistory_sold_serial', 'sold_serial');
    
    // Add foreign key constraints (ignore if they already exist)
    $conn->query("ALTER TABLE `Sales` ADD CONSTRAINT `Users_Sales` 
        FOREIGN KEY (`users_id`) REFERENCES `Users` (`users_id`)");
    
    $conn->query("ALTER TABLE `Books` ADD CONSTRAINT `Category_Books` 
        FOREIGN KEY (`category_id`) REFERENCES `Category` (`category_id`)");
    
    $conn->query("ALTER TABLE `Books` ADD CONSTRAINT `Publisher_Books` 
        FOREIGN KEY (`publisher`) REFERENCES `publisher` (`publisher`)");
    
    $conn->query("ALTER TABLE `Books` ADD CONSTRAINT `Sales_Books` 
        FOREIGN KEY (`sales_id`) REFERENCES `Sales` (`sales_id`)");

    // Seed default admin account
    $adminHash = '$2y$10$bqDrGu3HyMsXWAqoCpYcrumDaaplDkK0Ff5VDQ4XtUR5Umsv.9lHS';
    $conn->query("INSERT IGNORE INTO `Users` (name, email, password, role)
        VALUES ('Admin','admin@bookshop.com','$adminHash','admin')");
}

// Close connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function buildSerialNumber($prefix, $id) {
    return $prefix . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
}

function assignSerialNumber($conn, $table, $idColumn, $serialColumn, $prefix, $id) {
    $serial = buildSerialNumber($prefix, $id);
    $sql = "UPDATE `{$table}` SET `{$serialColumn}` = ? WHERE `{$idColumn}` = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("si", $serial, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok ? $serial : false;
}

function backfillSerialColumn($conn, $table, $idColumn, $serialColumn, $prefix) {
    $sql = "SELECT `{$idColumn}` FROM `{$table}` WHERE `{$serialColumn}` IS NULL OR `{$serialColumn}` = '' ORDER BY `{$idColumn}` ASC";
    $result = $conn->query($sql);
    if (!$result) {
        return;
    }

    while ($row = $result->fetch_assoc()) {
        assignSerialNumber($conn, $table, $idColumn, $serialColumn, $prefix, (int)$row[$idColumn]);
    }
}

function ensureUniqueIndex($conn, $table, $indexName, $columnName) {
    $result = $conn->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");
    if ($result && $result->num_rows > 0) {
        return;
    }

    $conn->query("ALTER TABLE `{$table}` ADD UNIQUE INDEX `{$indexName}` (`{$columnName}`)");
}
?>
