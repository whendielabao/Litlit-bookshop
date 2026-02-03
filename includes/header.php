<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookshop Inventory System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <h1>📚 Bookshop Inventory System</h1>
            <nav class="main-nav">
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="add_book.php">Add Book</a></li>
                        <li><a href="add_category.php">Add Category</a></li>
                        <li><a href="add_publisher.php">Add Publisher</a></li>
                        <li><a href="add_user.php">Add User</a></li>
                        <li><a href="view_books.php">View Books</a></li>
                        <li class="user-info">
                            <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            <a href="logout.php" class="logout-btn">Logout</a>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
