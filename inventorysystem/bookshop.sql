# Sample Data for Bookshop Inventory System

INSERT INTO `Category` (`category_id`, `name`, `description`) VALUES
(1, 'Fiction', 'Fictional literature and novels'),
(2, 'Technology', 'Computer science and technology'),
(3, 'Business', 'Business and management books'),
(4, 'Science', 'Scientific books');

INSERT INTO `publisher` (`publisher`, `name`, `contact_info`) VALUES
('Penguin', 'Penguin Random House', 'contact@penguin.com'),
('OReilly', 'OReilly Media', 'info@oreilly.com'),
('Addison-Wesley', 'Addison-Wesley', 'contact@aw.com');

INSERT INTO `Books` (`title`, `author`, `price`, `quantity`, `category_id`, `publisher`) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 15.99, 25, 1, 'Penguin'),
('1984', 'George Orwell', 16.99, 20, 1, 'Penguin'),
('Clean Code', 'Robert C. Martin', 45.99, 15, 2, 'Addison-Wesley'),
('JavaScript: The Good Parts', 'Douglas Crockford', 32.99, 8, 2, 'OReilly'),
('Good to Great', 'Jim Collins', 28.99, 22, 3, 'Penguin'),
('A Brief History of Time', 'Stephen Hawking', 26.99, 18, 4, 'Penguin');

INSERT INTO `Users` (`name`, `email`) VALUES
('John Smith', 'john@email.com'),
('Sarah Johnson', 'sarah@email.com');
