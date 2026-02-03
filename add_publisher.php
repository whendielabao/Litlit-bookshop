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
    $publisher_code = sanitizeInput($_POST['publisher'] ?? '');
    $name = sanitizeInput($_POST['name'] ?? '');
    $contact_info = sanitizeInput($_POST['contact_info'] ?? '');
    
    // Validation
    if (empty($publisher_code)) {
        $errors[] = "Publisher code is required";
    }
    
    if (strlen($publisher_code) > 40) {
        $errors[] = "Publisher code must not exceed 40 characters";
    }
    
    if (empty($name)) {
        $errors[] = "Publisher name is required";
    }
    
    if (strlen($name) > 40) {
        $errors[] = "Publisher name must not exceed 40 characters";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO publisher (publisher, name, contact_info) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $publisher_code, $name, $contact_info);
        
        if ($stmt->execute()) {
            $message = "Publisher added successfully!";
            $messageType = "success";
            
            // Clear form
            $_POST = [];
        } else {
            if ($conn->errno == 1062) {
                $message = "Publisher code already exists. Please use a different code.";
            } else {
                $message = "Error adding publisher: " . $stmt->error;
            }
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
    <h2>Add New Publisher</h2>
    
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="publisher">Publisher Code *</label>
            <input type="text" id="publisher" name="publisher" maxlength="40" value="<?php echo $_POST['publisher'] ?? ''; ?>" required>
            <small>Unique identifier for the publisher (e.g., PUB001)</small>
        </div>
        
        <div class="form-group">
            <label for="name">Publisher Name *</label>
            <input type="text" id="name" name="name" maxlength="40" value="<?php echo $_POST['name'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="contact_info">Contact Information</label>
            <input type="text" id="contact_info" name="contact_info" maxlength="40" value="<?php echo $_POST['contact_info'] ?? ''; ?>">
            <small>Email or phone number</small>
        </div>
        
        <button type="submit" class="btn btn-block">Add Publisher</button>
    </form>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
