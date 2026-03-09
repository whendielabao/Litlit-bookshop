<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireAdmin();

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $confirm  =      $_POST['confirm']  ?? '';
    $role     =      $_POST['role']     ?? 'clerk';
    $conn   = getDBConnection();
    $result = registerUser($name, $email, $password, $confirm, $conn, $role);
    closeDBConnection($conn);
    if ($result['success']) $success = 'User created successfully!';
    else                    $errors  = $result['errors'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add User — Bookshop Inventory</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<main>
  <div class="page-header">
    <h1>Add User</h1>
    <a href="index.php" class="btn btn-ghost">← Dashboard</a>
  </div>

  <div class="glass-card" style="max-width:560px">
    <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Full Name <span class="req">*</span></label>
        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Email <span class="req">*</span></label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Password <span class="req">*</span></label>
        <input type="password" name="password" required>
      </div>
      <div class="form-group">
        <label>Confirm Password <span class="req">*</span></label>
        <input type="password" name="confirm" required>
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role">
          <option value="clerk" <?= (!isset($_POST['role']) || $_POST['role']==='clerk') ? 'selected' : '' ?>>Sales Clerk</option>
          <option value="admin" <?= (isset($_POST['role']) && $_POST['role']==='admin')  ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Create User</button>
        <a href="index.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
