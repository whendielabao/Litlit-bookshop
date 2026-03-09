<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';

if (isLoggedIn()) { header("Location: index.php"); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        $conn = getDBConnection();
        if (loginUser($email, $password, $conn)) {
            closeDBConnection($conn);
            session_regenerate_id(true);
            session_write_close();
            header("Location: index.php", true, 302);
            exit();
        } else {
            $error = 'Invalid email or password';
            closeDBConnection($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Bookshop Inventory</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-brand">
    <h1>📚 BookSys</h1>
    <div class="auth-tagline">FUTURISTIC INVENTORY SYSTEM</div>
  </div>

  <div class="auth-box">
    <h2>Sign In</h2>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Email <span class="req">*</span></label>
        <input type="email" name="email" required autofocus>
      </div>
      <div class="form-group">
        <label>Password <span class="req">*</span></label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block mt-2">Login</button>
    </form>

    <div class="auth-footer-link">No account? <a href="register.php">Register →</a></div>
  </div>
</div>
</body>
</html>
