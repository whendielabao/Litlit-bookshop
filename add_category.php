<?php
require_once 'config.php';
include 'includes/header.php';

$conn = getDBConnection();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    // Validate inputs
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = "Category name is required";
    }
    
    if (strlen($name) > 40) {
        $errors[] = "Category name must not exceed 40 characters";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO Category (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            $message = "Category added successfully! Category ID: " . $stmt->insert_id;
            $messageType = "success";
            
            // Clear form
            $_POST = [];
        } else {
            $message = "Error adding category: " . $stmt->error;
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
    <h2>Add New Category</h2>
    
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Category Name *</label>
            <input type="text" id="name" name="name" maxlength="40" value="<?php echo $_POST['name'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" maxlength="40"><?php echo $_POST['description'] ?? ''; ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-block">Add Category</button>
    </form>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
