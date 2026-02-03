<?php
require_once 'config.php';
include 'includes/header.php';

$conn = getDBConnection();
$message = '';
$messageType = '';

// Get categories and publishers for dropdowns
$categories = $conn->query("SELECT * FROM Category ORDER BY name");
$publishers = $conn->query("SELECT * FROM publisher ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    // Validate inputs
    $title = sanitizeInput($_POST['title'] ?? '');
    $author = sanitizeInput($_POST['author'] ?? '');
    $price = sanitizeInput($_POST['price'] ?? '');
    $quantity = sanitizeInput($_POST['quantity'] ?? '');
    $category_id = sanitizeInput($_POST['category_id'] ?? '');
    $publisher = sanitizeInput($_POST['publisher'] ?? '');
    
    // Validation
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($author)) {
        $errors[] = "Author is required";
    }
    
    if (empty($price) || !is_numeric($price) || $price < 0) {
        $errors[] = "Valid price is required";
    }
    
    if (empty($quantity) || !is_numeric($quantity) || $quantity < 0) {
        $errors[] = "Valid quantity is required";
    }
    
    if (empty($category_id)) {
        $errors[] = "Category is required";
    }
    
    if (empty($publisher)) {
        $errors[] = "Publisher is required";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO Books (title, author, price, quantity, category_id, publisher) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $title, $author, $price, $quantity, $category_id, $publisher);
        
        if ($stmt->execute()) {
            $message = "Book added successfully! Book ID: " . $stmt->insert_id;
            $messageType = "success";
            
            // Clear form
            $_POST = [];
        } else {
            $message = "Error adding book: " . $stmt->error;
            $messageType = "error";
        }
        
        $stmt->close();
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}

?>

<div class="form-container">
    <h2>Add New Book</h2>
    
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" value="<?php echo $_POST['title'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="author">Author *</label>
            <input type="text" id="author" name="author" value="<?php echo $_POST['author'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="price">Price *</label>
            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $_POST['price'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="quantity">Quantity *</label>
            <input type="number" id="quantity" name="quantity" min="0" value="<?php echo $_POST['quantity'] ?? '0'; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="category_id">Category *</label>
            <select id="category_id" name="category_id" required>
                <option value="">-- Select Category --</option>
                <?php while ($row = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $row['category_id']; ?>" 
                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $row['category_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($row['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="publisher">Publisher *</label>
            <select id="publisher" name="publisher" required>
                <option value="">-- Select Publisher --</option>
                <?php while ($row = $publishers->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['publisher']); ?>" 
                        <?php echo (isset($_POST['publisher']) && $_POST['publisher'] == $row['publisher']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($row['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-block">Add Book</button>
    </form>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
