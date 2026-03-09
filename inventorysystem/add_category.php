<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireAdmin();

$message = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['category_name'] ?? '');
    if ($name === '') {
        $error = 'Category name is required.';
    } else {
        $conn  = getDBConnection();
        $check = $conn->prepare("SELECT category_id FROM Category WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "Category \"$name\" already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO Category (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) $message = "Category \"$name\" added.";
            else                  $error   = 'Database error — please try again.';
            $stmt->close();
        }
        $check->close();
        closeDBConnection($conn);
    }
}

// Fetch existing categories for display
$conn = getDBConnection();
$rows = $conn->query("SELECT category_id, name FROM Category ORDER BY name");
$categories = [];
while ($row = $rows->fetch_assoc()) $categories[] = $row;
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Categories — Bookshop Inventory</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<main>
  <div class="page-header">
    <h1>Manage Categories</h1>
    <a href="index.php" class="btn btn-ghost">← Dashboard</a>
  </div>

  <div class="glass-card" style="max-width:560px">
    <h3 style="margin-bottom:1.4rem">Add Category</h3>
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?=   htmlspecialchars($error)   ?></div><?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Category Name <span class="req">*</span></label>
        <input type="text" name="category_name" placeholder="e.g. Science Fiction" required autofocus>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Add Category</button>
      </div>
    </form>
  </div>

  <?php if (!empty($categories)): ?>
  <div class="glass-card" style="max-width:560px">
    <h3 style="margin-bottom:1.2rem">Existing Categories</h3>
    <table class="books-table">
      <thead><tr><th>#</th><th>Name</th></tr></thead>
      <tbody>
        <?php foreach ($categories as $i => $cat): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($cat['name']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
