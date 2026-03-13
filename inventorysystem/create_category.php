<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireAdmin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['category_name'] ?? '');
    if ($name === '') {
        $error = 'Category name is required.';
    } else {
        $conn = getDBConnection();
        $check = $conn->prepare("SELECT category_id FROM Category WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Category \"$name\" already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO Category (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $message = "Category \"$name\" added.";
                $_POST = [];
            } else {
                $error = 'Database error — please try again.';
            }
            $stmt->close();
        }
        $check->close();
        closeDBConnection($conn);
    }
}
?>
<?php include 'includes/header.php'; ?>
  <div class="page-header">
    <h1>Create Category</h1>
    <a href="add_category.php" class="btn btn-ghost">← List Categories</a>
  </div>

  <div class="glass-card" style="max-width:560px">
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Category Name <span class="req">*</span></label>
        <input type="text" name="category_name" placeholder="e.g. Science Fiction" value="<?= htmlspecialchars($_POST['category_name'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Create Category</button>
        <a href="add_category.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
<?php include 'includes/footer.php'; ?>
