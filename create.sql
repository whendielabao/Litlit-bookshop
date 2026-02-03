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
    `title` VARCHAR(255),
    `author` VARCHAR(255),
    `price` DECIMAL(10,2),
    `quantity` INTEGER DEFAULT 0,
    `category_id` INTEGER NOT NULL,
    `publisher` VARCHAR(40),
    `sales_id` INTEGER,
    CONSTRAINT `PK_Books` PRIMARY KEY (`book_id`)
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
