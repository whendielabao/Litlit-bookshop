<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireAdmin();

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
  <title>List Categories — Bookshop Inventory</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<main>
  <div class="page-header">
    <h1>List Categories</h1>
    <a href="index.php" class="btn btn-ghost">← Dashboard</a>
  </div>

  <div class="glass-card" style="max-width:560px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:1.2rem;flex-wrap:wrap;">
      <h3 style="margin:0;">List Categories</h3>
      <a href="create_category.php" class="btn btn-primary">Create Category</a>
    </div>

    <?php if (empty($categories)): ?>
      <p class="text-muted">No categories found.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Name</th></tr></thead>
        <tbody>
          <?php foreach ($categories as $i => $cat): ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($cat['name']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
