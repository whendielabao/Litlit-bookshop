<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireAdmin();

$conn = getDBConnection();
$usersResult = $conn->query("SELECT users_id, name, email, role, created_at FROM Users ORDER BY created_at DESC, users_id DESC");
$users = [];
if ($usersResult) {
    while ($row = $usersResult->fetch_assoc()) $users[] = $row;
}
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>List Users — Bookshop Inventory</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<main>
  <div class="page-header">
    <h1>List Users</h1>
    <a href="index.php" class="btn btn-ghost">← Dashboard</a>
  </div>

  <div class="glass-card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:1.2rem;flex-wrap:wrap;">
      <h3 style="margin:0;">List Users</h3>
      <a href="register.php" class="btn btn-primary">Create Clerk Account</a>
    </div>
    <?php if (empty($users)): ?>
      <p class="text-muted">No users found.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $user): ?>
            <tr>
              <td class="text-muted"><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($user['name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($user['email'] ?? '—') ?></td>
              <td>
                <span class="role-tag <?= htmlspecialchars(($user['role'] ?? 'clerk')) ?>">
                  <?= htmlspecialchars($user['role'] ?? 'clerk') ?>
                </span>
              </td>
              <td class="text-muted" style="white-space:nowrap;">
                <?= !empty($user['created_at']) ? date('M j, Y g:iA', strtotime($user['created_at'])) : 'N/A' ?>
              </td>
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
