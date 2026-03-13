<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireAdmin();

$conn = getDBConnection();
$rows = $conn->query("SELECT publisher, name, contact_info FROM publisher ORDER BY name");
$publishers = [];
while ($row = $rows->fetch_assoc()) $publishers[] = $row;
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>List Publishers — Bookshop Inventory</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<main>
  <div class="page-header">
    <h1>List Publishers</h1>
    <a href="index.php" class="btn btn-ghost">← Dashboard</a>
  </div>

  <div class="glass-card" style="max-width:680px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:1.2rem;flex-wrap:wrap;">
      <h3 style="margin:0;">List Publishers</h3>
      <a href="create_publisher.php" class="btn btn-primary">Create Publisher</a>
    </div>

    <?php if (empty($publishers)): ?>
      <p class="text-muted">No publishers found.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Name</th><th>Contact</th></tr></thead>
        <tbody>
          <?php foreach ($publishers as $i => $pub): ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($pub['name']) ?></td>
            <td><?= htmlspecialchars($pub['contact_info'] ?? '—') ?></td>
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
