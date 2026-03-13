<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireAdmin();

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name']            ?? '');
    $email           = trim($_POST['email']           ?? '');
    $password        = $_POST['password']        ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $conn   = getDBConnection();
    $result = registerUser($name, $email, $password, $confirmPassword, $conn, 'clerk');
    closeDBConnection($conn);
    if ($result['success']) $success = 'Clerk account created successfully!';
    else                    $errors  = $result['errors'];
}
?>
<?php include 'includes/header.php'; ?>
  <div class="page-header">
    <h1>Create Clerk Account</h1>
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
        <div class="hint-text">Minimum 6 characters</div>
      </div>
      <div class="form-group">
        <label>Confirm Password <span class="req">*</span></label>
        <input type="password" name="confirm_password" required>
      </div>
      <div class="hint-text">New users created here are always assigned the Sales Clerk role.</div>
      <div class="form-actions mt-2">
        <button type="submit" class="btn btn-primary">Create Clerk Account</button>
        <a href="index.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
<?php include 'includes/footer.php'; ?>
