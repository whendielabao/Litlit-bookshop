<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'auth.php';

requireLogin();

include 'includes/header.php';

$conn = getDBConnection();

// Get all books with related information
$query = "SELECT b.book_id, b.title, b.author, b.price, b.quantity,
          c.name as category_name,
          p.name as publisher_name
          FROM Books b
          LEFT JOIN Category c ON b.category_id = c.category_id
          LEFT JOIN publisher p ON b.publisher = p.publisher
          ORDER BY b.book_id DESC";

$result = $conn->query($query);
?>

<div class="table-container">
    <h2>All Books</h2>
    
    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Publisher</th>
                    <th>Price</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['book_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['author']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['publisher_name'] ?? 'N/A'); ?></td>
                        <td>$<?php echo number_format($row['price'], 2); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="message warning">
            No books found in the inventory. <a href="add_book.php">Add your first book</a>
        </div>
    <?php endif; ?>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
