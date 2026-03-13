<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$userRole    = getUserRole();
$userName    = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookshop Inventory System</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="main-header">
  <div class="container header-inner">

    <!-- Brand -->
    <a href="index.php" class="brand" style="text-decoration:none;">
      <span class="brand-icon">📚</span>
      <span class="brand-name">BookSys</span>
    </a>

    <!-- Primary Nav -->
    <nav class="main-nav">
      <?php if (isLoggedIn()): ?>

        <a href="index.php"      class="<?= $currentPage==='index.php'      ?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          <span class="nav-label">Dashboard</span>
        </a>

        <a href="view_books.php" class="<?= $currentPage==='view_books.php' ?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          <span class="nav-label">View Books</span>
        </a>

        <a href="add_book.php"   class="<?= $currentPage==='add_book.php'   ?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          <span class="nav-label">Add Book</span>
        </a>

        <a href="sold_history.php" class="<?= $currentPage==='sold_history.php' ?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          <span class="nav-label">Sold History</span>
        </a>

        <!-- System menu (admin only) -->
        <?php if (isAdmin()): ?>
        <div class="nav-system-wrap">
          <button class="nav-system-btn" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 0 1 21 12a10 10 0 0 1-1.93 5.07M4.93 4.93A10 10 0 0 0 3 12a10 10 0 0 0 1.93 5.07M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
            <span class="nav-label">System</span>
            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="system-dropdown">
              <a href="add_category.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>
                List Categories
              </a>
              <a href="add_publisher.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                List Publishers
              </a>
              <a href="add_user.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                List Users
              </a>
          </div>
        </div>
        <?php endif; ?>

      <?php else: ?>
        <a href="login.php"    class="<?= $currentPage==='login.php'    ?'active':'' ?>">Login</a>
      <?php endif; ?>
    </nav>

    <!-- User area -->
    <?php if (isLoggedIn()): ?>
    <div class="nav-user">
      <div class="user-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        <?= htmlspecialchars($userName) ?>
        <span class="role-tag <?= $userRole ?>"><?= $userRole ?></span>
      </div>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    <?php endif; ?>

  </div>
</header>
<script>
(function(){
  const wrap = document.querySelector('.nav-system-wrap');
  const btn  = document.querySelector('.nav-system-btn');
  if (!wrap || !btn) return;
  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    wrap.classList.toggle('open');
  });
  document.addEventListener('click', function(e) {
    if (!wrap.contains(e.target)) wrap.classList.remove('open');
  });
})();
</script>
<main class="container <?= isset($pageContainerClass) ? htmlspecialchars($pageContainerClass) : '' ?>">
