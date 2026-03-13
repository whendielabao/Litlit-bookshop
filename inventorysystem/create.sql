# ---------------------------------------------------------------------- #
# Script generated with: DeZign for Databases V8.1.2                     #
# Target DBMS:           MySQL 5                                         #
# Project file:          whendiel_ERD.dez                                #
# Project name:                                                          #
# Author:                                                                #
# Script type:           Database creation script                        #
# Created on:            2025-10-09 09:54                                #
# ---------------------------------------------------------------------- #


# ---------------------------------------------------------------------- #
# Add tables                                                             #
# ---------------------------------------------------------------------- #

# ---------------------------------------------------------------------- #
# Add table "Users"                                                      #
# ---------------------------------------------------------------------- #

CREATE TABLE `Users` (
    `users_id` INTEGER NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(40),
    `email` VARCHAR(40),
    CONSTRAINT `PK_Users` PRIMARY KEY (`users_id`)
);

# ---------------------------------------------------------------------- #
# Add table "Sales"                                                      #
# ---------------------------------------------------------------------- #

CREATE TABLE `Sales` (
    `sales_id` INTEGER NOT NULL AUTO_INCREMENT,
    `sales_date` VARCHAR(40),
    `users_id` INTEGER NOT NULL,
    CONSTRAINT `PK_Sales` PRIMARY KEY (`sales_id`)
);

# ---------------------------------------------------------------------- #
# Add table "publisher"                                                  #
# ---------------------------------------------------------------------- #

CREATE TABLE `publisher` (
    `publisher` VARCHAR(40) NOT NULL,
    `name` VARCHAR(40),
    `contact_info` VARCHAR(40),
    CONSTRAINT `PK_publisher` PRIMARY KEY (`publisher`)
);

# ---------------------------------------------------------------------- #
# Add table "Category"                                                   #
# ---------------------------------------------------------------------- #

CREATE TABLE `Category` (
    `category_id` INTEGER NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(40),
    `description` VARCHAR(40),
    CONSTRAINT `PK_Category` PRIMARY KEY (`category_id`)
);

# ---------------------------------------------------------------------- #
# Add table "Sales item"                                                 #
# ---------------------------------------------------------------------- #

CREATE TABLE `Sales item` (
    `sale_id` INTEGER NOT NULL AUTO_INCREMENT,
    `quantity` INTEGER,
    `publisher` VARCHAR(40) NOT NULL,
    `category_id` INTEGER NOT NULL,
    CONSTRAINT `PK_Sales item` PRIMARY KEY (`sale_id`)
);

# ---------------------------------------------------------------------- #
# Add table "Books"                                                      #
# ---------------------------------------------------------------------- #

CREATE TABLE `Books` (
    `book_id` INTEGER NOT NULL AUTO_INCREMENT,
    `book_serial` VARCHAR(30),
    `title` VARCHAR(255),
    `author` VARCHAR(255),
    `price` DECIMAL(10,2),
    `quantity` INTEGER DEFAULT 0,
    `category_id` INTEGER NOT NULL,
    `publisher` VARCHAR(40),
    `sales_id` INTEGER,
    `book_cover` VARCHAR(255) DEFAULT NULL,
    `author_photo` VARCHAR(255) DEFAULT NULL,
    `added_by` INTEGER DEFAULT NULL,
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `PK_Books` PRIMARY KEY (`book_id`)
);

# ---------------------------------------------------------------------- #
# Add table "SoldHistory"                                               #
# ---------------------------------------------------------------------- #

CREATE TABLE `SoldHistory` (
    `sold_id` INTEGER NOT NULL AUTO_INCREMENT,
    `sold_serial` VARCHAR(30),
    `book_id` INTEGER NOT NULL,
    `quantity` INTEGER NOT NULL,
    `price_at_sale` DECIMAL(10,2) NOT NULL,
    `sold_by` INTEGER DEFAULT NULL,
    `sold_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `PK_SoldHistory` PRIMARY KEY (`sold_id`)
);

# ---------------------------------------------------------------------- #
# Add table "InventoryMovements"                                        #
# ---------------------------------------------------------------------- #

CREATE TABLE `InventoryMovements` (
    `movement_id` INTEGER NOT NULL AUTO_INCREMENT,
    `book_id` INTEGER NOT NULL,
    `movement_type` ENUM('stock_in','stock_out','restock','pull_out') NOT NULL,
    `quantity_before` INTEGER NOT NULL DEFAULT 0,
    `quantity_change` INTEGER NOT NULL,
    `quantity_after` INTEGER NOT NULL DEFAULT 0,
    `notes` VARCHAR(255),
    `damage_remarks` VARCHAR(255),
    `moved_by` INTEGER,
    `moved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `PK_InventoryMovements` PRIMARY KEY (`movement_id`)
);

# ---------------------------------------------------------------------- #
# Add foreign key constraints                                            #
# ---------------------------------------------------------------------- #

ALTER TABLE `Sales` ADD CONSTRAINT `Users_Sales` 
    FOREIGN KEY (`users_id`) REFERENCES `Users` (`users_id`);

ALTER TABLE `Sales item` ADD CONSTRAINT `publisher_Sales item` 
    FOREIGN KEY (`publisher`) REFERENCES `publisher` (`publisher`);

ALTER TABLE `Sales item` ADD CONSTRAINT `Category_Sales item` 
    FOREIGN KEY (`category_id`) REFERENCES `Category` (`category_id`);

ALTER TABLE `Books` ADD CONSTRAINT `Category_Books` 
    FOREIGN KEY (`category_id`) REFERENCES `Category` (`category_id`);

ALTER TABLE `Books` ADD CONSTRAINT `Publisher_Books` 
    FOREIGN KEY (`publisher`) REFERENCES `publisher` (`publisher`);

ALTER TABLE `Books` ADD CONSTRAINT `Sales_Books` 
    FOREIGN KEY (`sales_id`) REFERENCES `Sales` (`sales_id`);


# ---------------------------------------------------------------------- #
# Add sample data                                                        #
# ---------------------------------------------------------------------- #

INSERT INTO `Category` (`category_id`, `name`, `description`) VALUES
(1, 'Fiction', 'Fictional literature and novels'),
(2, 'Technology', 'Computer science and technology'),
(3, 'Business', 'Business and management books'),
(4, 'Science', 'Scientific books'),
(5, 'Self-Help', 'Personal development and growth'),
(6, 'History', 'Historical non-fiction');

INSERT INTO `publisher` (`publisher`, `name`, `contact_info`) VALUES
('Penguin', 'Penguin Random House', 'contact@penguin.com'),
('OReilly', 'OReilly Media', 'info@oreilly.com'),
('Addison-Wesley', 'Addison-Wesley', 'contact@aw.com'),
('HarperCollins', 'HarperCollins Publishers', 'info@harpercollins.com'),
('Wiley', 'John Wiley & Sons', 'contact@wiley.com');

INSERT INTO `Books` (`book_serial`, `title`, `author`, `price`, `quantity`, `category_id`, `publisher`) VALUES
('BK-000001', 'The Great Gatsby', 'F. Scott Fitzgerald', 15.99, 25, 1, 'Penguin'),
('BK-000002', '1984', 'George Orwell', 16.99, 20, 1, 'Penguin'),
('BK-000003', 'Clean Code', 'Robert C. Martin', 45.99, 15, 2, 'Addison-Wesley'),
('BK-000004', 'JavaScript: The Good Parts', 'Douglas Crockford', 32.99, 8, 2, 'OReilly'),
('BK-000005', 'Good to Great', 'Jim Collins', 28.99, 22, 3, 'Penguin'),
('BK-000006', 'A Brief History of Time', 'Stephen Hawking', 26.99, 18, 4, 'Penguin'),
('BK-000007', 'To Kill a Mockingbird', 'Harper Lee', 14.99, 30, 1, 'HarperCollins'),
('BK-000008', 'The Pragmatic Programmer', 'David Thomas', 49.99, 10, 2, 'Addison-Wesley'),
('BK-000009', 'Atomic Habits', 'James Clear', 22.99, 35, 5, 'Penguin'),
('BK-000010', 'Sapiens', 'Yuval Noah Harari', 24.99, 12, 6, 'HarperCollins'),
('BK-000011', 'Design Patterns', 'Erich Gamma', 54.99, 7, 2, 'Addison-Wesley'),
('BK-000012', 'The Lean Startup', 'Eric Ries', 27.99, 19, 3, 'Wiley');

INSERT INTO `Sales item` (`quantity`, `publisher`, `category_id`) VALUES
(3, 'Penguin', 1),
(2, 'Addison-Wesley', 2),
(5, 'OReilly', 2),
(1, 'HarperCollins', 1),
(4, 'Penguin', 3);

INSERT INTO `Users` (`name`, `email`) VALUES
('John Smith', 'john@email.com'),
('Sarah Johnson', 'sarah@email.com');
