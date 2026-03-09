<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';

if (isLoggedIn()) { header("Location: index.php"); exit(); }

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name']            ?? '');
    $email           = trim($_POST['email']           ?? '');
    $password        = $_POST['password']        ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role            = $_POST['role']             ?? 'clerk';
    $conn   = getDBConnection();
    $result = registerUser($name, $email, $password, $confirmPassword, $conn, $role);
    closeDBConnection($conn);
    if ($result['success']) $success = $result['message'];
    else                    $errors  = $result['errors'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — Bookshop Inventory</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-brand">
    <h1>📚 BookSys</h1>
    <div class="auth-tagline">FUTURISTIC INVENTORY SYSTEM</div>
  </div>

  <div class="auth-box">
    <h2>Create Account</h2>

    <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?> <a href="login.php">Login →</a></div>
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
      <input type="hidden" name="role" value="clerk">
      <button type="submit" class="btn btn-primary btn-block mt-2">Create Account</button>
    </form>

    <div class="auth-footer-link">Already have an account? <a href="login.php">Login →</a></div>
  </div>
</div>
</body>
</html>
