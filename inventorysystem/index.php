<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$conn  = getDBConnection();
$books = (int)($conn->query("SELECT COUNT(*) c FROM Books")->fetch_assoc()['c'] ?? 0);
$cats  = (int)($conn->query("SELECT COUNT(*) c FROM Category")->fetch_assoc()['c'] ?? 0);
$pubs  = (int)($conn->query("SELECT COUNT(*) c FROM publisher")->fetch_assoc()['c'] ?? 0);
$users = (int)($conn->query("SELECT COUNT(*) c FROM Users")->fetch_assoc()['c'] ?? 0);
closeDBConnection($conn);

include 'includes/header.php';
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
<div class="alert alert-error" style="margin-bottom:1.5rem;">
  ⚠ Access denied. Admin privileges required.
</div>
<?php endif; ?>

<div class="dash-hero">
  <h1>Welcome back, <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span></h1>
  <p>Your futuristic bookshop command centre — fast, smart, minimal.</p>

  <!-- Two primary action cards -->
  <div class="action-cards">
    <a href="view_books.php" class="action-card primary">
      <span class="ac-icon">📖</span>
      <span class="ac-label">View Books</span>
      <span class="ac-sub">Browse, search &amp; filter the full catalogue</span>
    </a>
    <a href="add_book.php" class="action-card">
      <span class="ac-icon">➕</span>
      <span class="ac-label">Add New Book</span>
      <span class="ac-sub">Smart-sync via ISBN or title lookup</span>
    </a>
  </div>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-chip"><div class="stat-num"><?= $books ?></div><div class="stat-lbl">Books</div></div>
    <div class="stat-chip"><div class="stat-num"><?= $cats  ?></div><div class="stat-lbl">Categories</div></div>
    <div class="stat-chip"><div class="stat-num"><?= $pubs  ?></div><div class="stat-lbl">Publishers</div></div>
    <div class="stat-chip"><div class="stat-num"><?= $users ?></div><div class="stat-lbl">Users</div></div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
