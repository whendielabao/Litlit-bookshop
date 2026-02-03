<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'auth.php';

// Require login
requireLogin();

include 'includes/header.php';

// Get statistics
$conn = getDBConnection();

$stats = [
    'books' => 0,
    'categories' => 0,
    'publishers' => 0,
    'users' => 0
];

$result = $conn->query("SELECT COUNT(*) as count FROM Books");
if ($result) {
    $stats['books'] = $result->fetch_assoc()['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM Category");
if ($result) {
    $stats['categories'] = $result->fetch_assoc()['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM publisher");
if ($result) {
    $stats['publishers'] = $result->fetch_assoc()['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM Users");
if ($result) {
    $stats['users'] = $result->fetch_assoc()['count'];
}

closeDBConnection($conn);
?>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <div class="icon">📚</div>
        <h2>Books</h2>
        <p>Total: <strong><?php echo $stats['books']; ?></strong></p>
        <a href="add_book.php">Add New Book</a>
    </div>
    
    <div class="dashboard-card">
        <div class="icon">🏷️</div>
        <h2>Categories</h2>
        <p>Total: <strong><?php echo $stats['categories']; ?></strong></p>
        <a href="add_category.php">Add New Category</a>
    </div>
    
    <div class="dashboard-card">
        <div class="icon">🏢</div>
        <h2>Publishers</h2>
        <p>Total: <strong><?php echo $stats['publishers']; ?></strong></p>
        <a href="add_publisher.php">Add New Publisher</a>
    </div>
    
    <div class="dashboard-card">
        <div class="icon">👥</div>
        <h2>Users</h2>
        <p>Total: <strong><?php echo $stats['users']; ?></strong></p>
        <a href="add_user.php">Add New User</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
