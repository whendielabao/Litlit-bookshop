<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$conn = getDBConnection();

// Optional filter by book_id
$bookFilter = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;

// Month filter: ?month=YYYY-MM (defaults to current month)
$monthParam = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) $monthParam = date('Y-m');
$monthStart = $monthParam . '-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart)); // last day of month

// --- Monthly summary: totals per month (for the month selector) ---
$monthlySql = "SELECT DATE_FORMAT(sold_at, '%Y-%m') AS month_key,
               SUM(quantity) AS total_qty,
               SUM(quantity * price_at_sale) AS total_revenue
               FROM SoldHistory";
if ($bookFilter) $monthlySql .= " WHERE book_id = " . $bookFilter;
$monthlySql .= " GROUP BY month_key ORDER BY month_key DESC";
$monthlyResult = $conn->query($monthlySql);
$monthlySummary = [];
if ($monthlyResult) {
    while ($r = $monthlyResult->fetch_assoc()) $monthlySummary[$r['month_key']] = $r;
}

// --- Sales for the selected month ---
$sql = "SELECT sh.sold_id, sh.sold_serial, sh.quantity, sh.price_at_sale, sh.sold_at,
  b.book_id, b.book_serial, b.title, b.author, b.isbn, b.book_cover,
        u.name AS sold_by_name
        FROM SoldHistory sh
        LEFT JOIN Books b ON sh.book_id = b.book_id
        LEFT JOIN Users u ON sh.sold_by = u.users_id
        WHERE sh.sold_at >= ? AND sh.sold_at < DATE_ADD(?, INTERVAL 1 DAY)";
if ($bookFilter) $sql .= " AND sh.book_id = " . $bookFilter;
$sql .= " ORDER BY sh.sold_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $monthStart, $monthEnd);
$stmt->execute();
$result = $stmt->get_result();
$sales = [];
while ($r = $result->fetch_assoc()) $sales[] = $r;
$stmt->close();

// Get book title for filter heading
$filterTitle = '';
if ($bookFilter) {
    $bStmt = $conn->prepare("SELECT title FROM Books WHERE book_id = ?");
    $bStmt->bind_param("i", $bookFilter);
    $bStmt->execute();
    $bRow = $bStmt->get_result()->fetch_assoc();
    if ($bRow) $filterTitle = $bRow['title'];
    $bStmt->close();
}

// Current month totals
$curMonthQty     = 0;
$curMonthRevenue = 0;
foreach ($sales as $s) {
    $curMonthQty     += (int)$s['quantity'];
    $curMonthRevenue += $s['price_at_sale'] * $s['quantity'];
}

closeDBConnection($conn);

// Helper: resolve cover src
function soldImgSrc($val) {
    if (!$val) return '';
    if (str_starts_with($val,'url:'))  return substr($val, 4);
    if (str_starts_with($val,'http')) return $val;
    if (str_starts_with($val,'file:')) return 'uploads/'.substr($val,5);
    return 'uploads/'.$val;
}

// Build base URL for month links (preserve book_id filter)
$baseUrl = 'sold_history.php';
if ($bookFilter) $baseUrl .= '?book_id=' . $bookFilter;

include 'includes/header.php';
?>

<!-- Toolbar -->
<div class="books-toolbar">
  <h2 style="font-family:'Lora',Georgia,serif;font-size:1.1rem;color:var(--navy);margin:0;">
    📋 Sold History
    <?php if ($filterTitle): ?>
      <span style="font-weight:400;font-size:.85rem;color:var(--text-2);"> — <?= htmlspecialchars($filterTitle) ?></span>
    <?php endif; ?>
  </h2>
  <?php if ($bookFilter): ?>
    <a href="sold_history.php" class="btn btn-ghost btn-sm">View All</a>
  <?php endif; ?>
</div>

<!-- Monthly Summary Cards -->
<div class="monthly-summary">
  <div class="monthly-header">
    <h3 class="monthly-title">📅 <?= date('F Y', strtotime($monthStart)) ?></h3>
    <div class="month-nav">
      <?php
        $prevMonth = date('Y-m', strtotime($monthStart . ' -1 month'));
        $nextMonth = date('Y-m', strtotime($monthStart . ' +1 month'));
        $sep = $bookFilter ? '&' : '?';
        $sepFirst = $bookFilter ? '&' : '?';
      ?>
      <a href="<?= htmlspecialchars($baseUrl . ($bookFilter ? '&' : '?') . 'month=' . $prevMonth) ?>" class="btn btn-ghost btn-sm">← <?= date('M', strtotime($prevMonth . '-01')) ?></a>
      <?php if ($monthParam !== date('Y-m')): ?>
        <a href="<?= htmlspecialchars($baseUrl . ($bookFilter ? '&' : '?') . 'month=' . date('Y-m')) ?>" class="btn btn-ghost btn-sm">Today</a>
      <?php endif; ?>
      <?php if ($monthParam < date('Y-m')): ?>
        <a href="<?= htmlspecialchars($baseUrl . ($bookFilter ? '&' : '?') . 'month=' . $nextMonth) ?>" class="btn btn-ghost btn-sm"><?= date('M', strtotime($nextMonth . '-01')) ?> →</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="stats-row" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem;">
    <div class="stat-chip">
      <div class="stat-num"><?= $curMonthQty ?></div>
      <div class="stat-lbl">Items Sold</div>
    </div>
    <div class="stat-chip">
      <div class="stat-num" style="color:var(--green);">₱<?= number_format($curMonthRevenue, 2) ?></div>
      <div class="stat-lbl">Total Revenue</div>
    </div>
    <div class="stat-chip">
      <div class="stat-num"><?= count($sales) ?></div>
      <div class="stat-lbl">Transactions</div>
    </div>
  </div>
</div>

<!-- Past Months Overview -->
<?php if (count($monthlySummary) > 1): ?>
<details class="past-months-details">
  <summary class="past-months-toggle">📊 View All Months Summary (<?= count($monthlySummary) ?> months with sales)</summary>
  <div class="table-wrap" style="margin-top:.75rem;">
    <table>
      <thead>
        <tr>
          <th>Month</th>
          <th>Items Sold</th>
          <th>Total Revenue</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($monthlySummary as $mk => $ms): ?>
        <tr<?= $mk === $monthParam ? ' style="background:var(--gold-dim);"' : '' ?>>
          <td><strong><?= date('F Y', strtotime($mk . '-01')) ?></strong></td>
          <td><span class="qty-badge"><?= (int)$ms['total_qty'] ?></span></td>
          <td><span class="price-tag">₱<?= number_format($ms['total_revenue'], 2) ?></span></td>
          <td>
            <?php if ($mk === $monthParam): ?>
              <span class="text-muted" style="font-size:.75rem;">Currently viewing</span>
            <?php else: ?>
              <a href="<?= htmlspecialchars($baseUrl . ($bookFilter ? '&' : '?') . 'month=' . $mk) ?>" class="btn btn-ghost btn-sm">View</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</details>
<?php endif; ?>

<!-- Sales Detail Table -->
<?php if (empty($sales)): ?>
<div class="empty-state">
  <div class="es-icon">🧾</div>
  <p>No sales recorded for <?= date('F Y', strtotime($monthStart)) ?>.</p>
</div>
<?php else: ?>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Sale Serial</th>
        <th>Cover</th>
        <th>Title / Serial</th>
        <th>Author</th>
        <th>Qty Sold</th>
        <th>Price Each</th>
        <th>Subtotal</th>
        <th>Sold By</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($sales as $i => $s): ?>
      <?php
        $coverSrc = soldImgSrc($s['book_cover'] ?? '');
        $sub = $s['price_at_sale'] * $s['quantity'];
        $soldSerial = $s['sold_serial'] ?: buildSerialNumber('SL', (int)$s['sold_id']);
      ?>
      <tr>
        <td class="text-muted"><?= $i + 1 ?></td>
        <td><span class="text-muted" style="white-space:nowrap;"><?= htmlspecialchars($soldSerial) ?></span></td>
        <td class="td-center">
          <?php if ($coverSrc): ?>
            <img src="<?= htmlspecialchars($coverSrc) ?>" alt="cover" class="thumb-book">
          <?php else: ?><span class="no-img">—</span><?php endif; ?>
        </td>
        <td>
          <strong><?= htmlspecialchars($s['title'] ?? 'Deleted Book') ?></strong>
          <?php if (!empty($s['book_serial'])): ?><br><small class="text-muted">Book: <?= htmlspecialchars($s['book_serial']) ?></small><?php endif; ?>
          <?php if (!empty($s['isbn'])): ?><br><small class="text-muted"><?= htmlspecialchars($s['isbn']) ?></small><?php endif; ?>
        </td>
        <td><?= htmlspecialchars($s['author'] ?? '—') ?></td>
        <td><span class="qty-badge"><?= (int)$s['quantity'] ?></span></td>
        <td><span class="price-tag">₱<?= number_format($s['price_at_sale'], 2) ?></span></td>
        <td><span class="price-tag">₱<?= number_format($sub, 2) ?></span></td>
        <td class="text-muted"><?= htmlspecialchars($s['sold_by_name'] ?? 'Unknown') ?></td>
        <td class="text-muted" style="white-space:nowrap;font-size:.75rem;">
          <?= $s['sold_at'] ? date('M j, Y g:iA', strtotime($s['sold_at'])) : 'N/A' ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="background:var(--bg-panel);">
        <td colspan="7" style="text-align:right;font-weight:700;font-size:.85rem;">Month Total</td>
        <td><span class="price-tag" style="font-size:.92rem;">₱<?= number_format($curMonthRevenue, 2) ?></span></td>
        <td colspan="2"></td>
      </tr>
    </tfoot>
  </table>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
