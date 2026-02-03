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
        
        return; // Tables already exist
    }
    
    // Create Users table
    $conn->query("CREATE TABLE IF NOT EXISTS `Users` (
        `users_id` INTEGER NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100),
        `email` VARCHAR(100) UNIQUE,
        `password` VARCHAR(255),
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
        `title` VARCHAR(255),
        `author` VARCHAR(255),
        `price` DECIMAL(10,2),
        `quantity` INTEGER DEFAULT 0,
        `category_id` INTEGER NOT NULL,
        `publisher` VARCHAR(40),
        `sales_id` INTEGER,
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
    
    // Add foreign key constraints (ignore if they already exist)
    $conn->query("ALTER TABLE `Sales` ADD CONSTRAINT `Users_Sales` 
        FOREIGN KEY (`users_id`) REFERENCES `Users` (`users_id`)");
    
    $conn->query("ALTER TABLE `Books` ADD CONSTRAINT `Category_Books` 
        FOREIGN KEY (`category_id`) REFERENCES `Category` (`category_id`)");
    
    $conn->query("ALTER TABLE `Books` ADD CONSTRAINT `Publisher_Books` 
        FOREIGN KEY (`publisher`) REFERENCES `publisher` (`publisher`)");
    
    $conn->query("ALTER TABLE `Books` ADD CONSTRAINT `Sales_Books` 
        FOREIGN KEY (`sales_id`) REFERENCES `Sales` (`sales_id`)");
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
?>
