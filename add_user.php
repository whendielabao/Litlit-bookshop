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
    $email = sanitizeInput($_POST['email'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (strlen($name) > 40) {
        $errors[] = "Name must not exceed 40 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (strlen($email) > 40) {
        $errors[] = "Email must not exceed 40 characters";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO Users (name, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $email);
        
        if ($stmt->execute()) {
            $message = "User added successfully! User ID: " . $stmt->insert_id;
            $messageType = "success";
            
            // Clear form
            $_POST = [];
        } else {
            $message = "Error adding user: " . $stmt->error;
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
    <h2>Add New User</h2>
    
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" maxlength="40" value="<?php echo $_POST['name'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" maxlength="40" value="<?php echo $_POST['email'] ?? ''; ?>" required>
        </div>
        
        <button type="submit" class="btn btn-block">Add User</button>
    </form>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
