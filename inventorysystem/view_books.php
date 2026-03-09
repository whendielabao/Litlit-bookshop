<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$conn = getDBConnection();

// Fetch all books with related info
$sql = "SELECT b.book_id, b.isbn, b.title, b.author, b.price, b.quantity,
        b.book_cover, b.author_photo, b.added_at, b.description, b.author_bio,
        b.category_id, b.publisher,
        c.name  AS category_name,
        p.name  AS publisher_name,
        u.name  AS added_by_name
        FROM Books b
        LEFT JOIN Category c ON b.category_id = c.category_id
        LEFT JOIN publisher p ON b.publisher   = p.publisher
        LEFT JOIN Users u     ON b.added_by    = u.users_id
        ORDER BY b.added_at DESC, b.book_id DESC";
$result = $conn->query($sql);
$books  = [];
while ($r = $result->fetch_assoc()) $books[] = $r;

// Categories list for filter chips + edit modal dropdown
$catResult = $conn->query("SELECT * FROM Category ORDER BY name");
$categories = [];
while ($r = $catResult->fetch_assoc()) $categories[] = $r;

// Publishers for edit modal dropdown
$pubResult = $conn->query("SELECT * FROM publisher ORDER BY name");
$publishers = [];
while ($r = $pubResult->fetch_assoc()) $publishers[] = $r;

// Pre-open edit modal if ?edit=id is in URL
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

closeDBConnection($conn);

// Helper: resolve cover src
function imgSrc($val) {
    if (!$val) return '';
    if (str_starts_with($val,'url:'))  return substr($val, 4);
    if (str_starts_with($val,'http')) return $val;
    // legacy local path
    if (str_starts_with($val,'file:')) return 'uploads/'.substr($val,5);
    return 'uploads/'.$val;
}

include 'includes/header.php';
?>

<?php
// Count per category for chips
$catCounts = [];
foreach ($books as $b) {
    $cn = $b['category_name'] ?? 'Uncategorised';
    $catCounts[$cn] = ($catCounts[$cn] ?? 0) + 1;
}
arsort($catCounts);
?>

<!-- Genre Filter Chips -->
<div class="genre-filter-bar">
  <div class="chips-label">Filter by Genre</div>
  <div class="chips-wrap" id="chips">
    <span class="genre-chip active" data-cat="all" onclick="filterByChip(this,'all')">
      All <span class="chip-count"><?= count($books) ?></span>
    </span>
    <?php foreach ($catCounts as $cn => $cnt): ?>
    <span class="genre-chip" data-cat="<?= htmlspecialchars($cn) ?>" onclick="filterByChip(this,'<?= htmlspecialchars($cn,ENT_QUOTES) ?>')">
      <?= htmlspecialchars($cn) ?> <span class="chip-count"><?= $cnt ?></span>
    </span>
    <?php endforeach; ?>
  </div>
</div>

<!-- Toolbar -->
<div class="books-toolbar">
  <div class="search-input-wrap">
    <span class="search-icon">🔍</span>
    <input type="search" id="book_search" placeholder="Search title, author, ISBN…" oninput="applyFilters()">
  </div>
  <span class="result-count" id="result_count"><?= count($books) ?> books</span>
  <?php if (isAdmin()): ?>
  <a href="add_book.php" class="btn btn-primary btn-sm">+ Add Book</a>
  <?php endif; ?>
</div>

<?php if (empty($books)): ?>
<div class="empty-state">
  <div class="es-icon">📚</div>
  <p>No books in the inventory yet. <a href="add_book.php">Add the first one.</a></p>
</div>
<?php else: ?>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Cover</th>
        <th>Title / ISBN</th>
        <th>Author</th>
        <th>Category</th>
        <th>Publisher</th>
        <th>Price</th>
        <th>Qty</th>
        <th>Added By</th>
        <th>Added On</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="books_tbody">
      <?php foreach ($books as $b): ?>
      <?php
        $coverSrc  = imgSrc($b['book_cover']  ?? '');
        $authorSrc = imgSrc($b['author_photo'] ?? '');
        $qty = (int)$b['quantity'];
        $qtyClass = $qty <= 0 ? 'low' : ($qty >= 10 ? 'high' : '');
        $cat = $b['category_name'] ?? 'Uncategorised';
      ?>
      <tr data-category="<?= htmlspecialchars($cat) ?>" data-search="<?= htmlspecialchars(strtolower($b['title'].' '.$b['author'].' '.($b['isbn']??''))) ?>">
        <td class="td-center">
          <?php if ($coverSrc): ?>
            <img src="<?= htmlspecialchars($coverSrc) ?>" alt="cover" class="thumb-book">
          <?php else: ?><span class="no-img">—</span><?php endif; ?>
        </td>
        <td>
          <strong><?= htmlspecialchars($b['title']) ?></strong>
          <?php if ($b['isbn']): ?><br><small class="text-muted"><?= htmlspecialchars($b['isbn']) ?></small><?php endif; ?>
        </td>
        <td>
          <div style="display:flex;align-items:center;gap:7px;">
            <?php if ($authorSrc): ?><img src="<?= htmlspecialchars($authorSrc) ?>" class="thumb-author" alt="author"><?php endif; ?>
            <?= htmlspecialchars($b['author']) ?>
          </div>
        </td>
        <td><?= htmlspecialchars($cat) ?></td>
        <td><?= htmlspecialchars($b['publisher_name'] ?? 'N/A') ?></td>
        <td><span class="price-tag">₱<?= number_format($b['price'],2) ?></span></td>
        <td><span class="qty-badge <?= $qtyClass ?>"><?= $qty ?></span></td>
        <td class="text-muted"><?= htmlspecialchars($b['added_by_name'] ?? 'Unknown') ?></td>
        <td class="text-muted" style="white-space:nowrap;font-size:.75rem;">
          <?= $b['added_at'] ? date('M j, Y', strtotime($b['added_at'])) : 'N/A' ?>
        </td>
        <td>
          <div class="table-actions">
            <button class="btn btn-ghost btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($b),ENT_QUOTES) ?>)">✏️ Edit</button>
            <button class="btn btn-sold btn-sm" onclick="openSellModal(<?= (int)$b['book_id'] ?>, <?= htmlspecialchars(json_encode($b['title']),ENT_QUOTES) ?>, <?= (int)$b['quantity'] ?>, <?= (float)$b['price'] ?>)">💰 Sold</button>
            <a href="sold_history.php?book_id=<?= (int)$b['book_id'] ?>" class="btn btn-ghost btn-sm" title="View sold history for this book">📋 History</a>
            <?php if (isAdmin()): ?>
            <button class="btn btn-danger btn-sm" onclick="deleteBook(<?= (int)$b['book_id'] ?>, <?= htmlspecialchars(json_encode($b['title']),ENT_QUOTES) ?>)">🗑️ Delete</button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<!-- ── Sell Modal ── -->
<div class="modal-overlay" id="sell_modal">
  <div class="modal" style="max-width:420px;">
    <div class="modal-header">
      <span class="modal-title">Record Sale</span>
      <button class="modal-close" onclick="closeSellModal()" type="button">&times;</button>
    </div>
    <div class="modal-body">
      <div class="alert alert-success" id="sell_success" style="display:none;"></div>
      <div class="alert alert-error" id="sell_error" style="display:none;"></div>
      <p style="font-size:.85rem;margin-bottom:1rem;">
        <strong id="sell_book_title"></strong><br>
        <span class="text-muted">Price: <span class="price-tag" id="sell_book_price"></span></span>
        &nbsp;·&nbsp;
        <span class="text-muted">Stock: <span id="sell_book_stock"></span></span>
      </p>
      <form id="sell_form">
        <input type="hidden" name="book_id" id="sell_book_id">
        <div class="form-group">
          <label>Quantity to sell <span class="req">*</span></label>
          <input type="number" name="quantity" id="sell_qty" min="1" value="1" required>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost" onclick="closeSellModal()">Cancel</button>
      <button type="button" class="btn btn-sold" onclick="submitSell()">Confirm Sale</button>
    </div>
  </div>
</div>

<!-- ── Edit Modal ── -->
<div class="modal-overlay" id="edit_modal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Book</span>
      <button class="modal-close" onclick="closeModal()" type="button">&times;</button>
    </div>
    <div class="modal-body">
      <div class="alert alert-success" id="edit_success" style="display:none;"></div>
      <div class="alert alert-error"   id="edit_error"   style="display:none;"></div>

      <!-- Smart-Sync in modal -->
      <div class="form-group">
        <label>Smart-Sync</label>
        <div class="isbn-wrap">
          <input type="text" id="m_smart_query" placeholder="ISBN or title to re-fetch" autocomplete="off">
          <select id="m_smart_type" style="width:auto;flex-shrink:0;padding:10px 10px;">
            <option value="title">Title</option>
            <option value="isbn">ISBN</option>
          </select>
          <button type="button" class="btn btn-fetch btn-sm" id="m_btn_fetch" onclick="modalFetch()">
            <span class="spinner"></span>
            <span class="btn-text">⚡ Fetch</span>
          </button>
        </div>
      </div>

      <form id="edit_form">
        <input type="hidden" name="book_id" id="m_book_id">
        <input type="hidden" name="book_cover"   id="m_cover_url">
        <input type="hidden" name="author_photo" id="m_author_url">

        <div class="form-row">
          <div class="form-group">
            <label>ISBN</label>
            <input type="text" name="isbn" id="m_isbn" placeholder="978-…">
          </div>
          <div class="form-group">
            <label>Price (₱) <span class="req">*</span></label>
            <input type="number" name="price" id="m_price" step="0.01" min="0" placeholder="0.00" required>
          </div>
        </div>

        <div class="form-group">
          <label>Title <span class="req">*</span></label>
          <input type="text" name="title" id="m_title" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Author <span class="req">*</span></label>
            <input type="text" name="author" id="m_author" required>
          </div>
          <div class="form-group">
            <label>Quantity <span class="req">*</span></label>
            <input type="number" name="quantity" id="m_quantity" min="0" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Category <span class="req">*</span></label>
            <select name="category_id" id="m_category" required>
              <option value="">— Select —</option>
              <?php foreach ($categories as $c): ?>
              <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Publisher <span class="req">*</span></label>
            <select name="publisher" id="m_publisher" required>
              <option value="">— Select —</option>
              <?php foreach ($publishers as $p): ?>
              <option value="<?= htmlspecialchars($p['publisher']) ?>"><?= htmlspecialchars($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="m_description" rows="3"></textarea>
        </div>

        <div class="form-group">
          <label>Author Bio</label>
          <textarea name="author_bio" id="m_author_bio" rows="2"></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
      <button type="button" class="btn btn-primary" onclick="submitEdit()">Save Changes</button>
    </div>
  </div>
</div>

<script>
let activeCategory = 'all';

// ── Genre chip filter ────────────────────────────────────────────────
function filterByChip(el, cat) {
  document.querySelectorAll('.genre-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  activeCategory = cat;
  applyFilters();
}

function applyFilters() {
  const q = document.getElementById('book_search').value.trim().toLowerCase();
  const rows = document.querySelectorAll('#books_tbody tr');
  let visible = 0;
  rows.forEach(row => {
    const matchCat    = activeCategory === 'all' || row.dataset.category === activeCategory;
    const matchSearch = !q || row.dataset.search.includes(q);
    if (matchCat && matchSearch) { row.style.display=''; visible++; }
    else                          { row.style.display='none'; }
  });
  const rc = document.getElementById('result_count');
  if (rc) rc.textContent = visible + ' book' + (visible !== 1 ? 's' : '');
}

// ── Edit modal ───────────────────────────────────────────────────────
function openEditModal(book) {
  document.getElementById('m_book_id').value   = book.book_id;
  document.getElementById('m_isbn').value      = book.isbn    || '';
  document.getElementById('m_title').value     = book.title   || '';
  document.getElementById('m_author').value    = book.author  || '';
  document.getElementById('m_price').value     = book.price   || '';
  document.getElementById('m_quantity').value  = book.quantity|| '';
  document.getElementById('m_description').value = book.description || '';
  document.getElementById('m_author_bio').value  = book.author_bio  || '';
  document.getElementById('m_cover_url').value   = book.book_cover  || '';
  document.getElementById('m_author_url').value  = book.author_photo|| '';

  const catSel = document.getElementById('m_category');
  for (let o of catSel.options) if (o.value == book.category_id) { o.selected=true; break; }

  const pubSel = document.getElementById('m_publisher');
  for (let o of pubSel.options) if (o.value == book.publisher) { o.selected=true; break; }

  document.getElementById('edit_success').style.display='none';
  document.getElementById('edit_error').style.display='none';
  document.getElementById('edit_modal').classList.add('open');
  document.getElementById('m_smart_query').value='';
}

function closeModal() {
  document.getElementById('edit_modal').classList.remove('open');
}

// Close on backdrop click
document.getElementById('edit_modal')?.addEventListener('click', e => {
  if (e.target === e.currentTarget) closeModal();
});

// Modal Smart-Sync
async function modalFetch() {
  const q    = document.getElementById('m_smart_query').value.trim();
  const type = document.getElementById('m_smart_type').value;
  if (!q) return;
  const btn = document.getElementById('m_btn_fetch');
  btn.classList.add('loading');
  try {
    const r    = await fetch(`api_fetch_book.php?q=${encodeURIComponent(q)}&type=${type}`);
    const data = await r.json();
    if (data.error) { alert(data.error); return; }
    if (data.title)       document.getElementById('m_title').value       = data.title;
    if (data.author)      document.getElementById('m_author').value      = data.author;
    if (data.isbn)        document.getElementById('m_isbn').value        = data.isbn;
    if (data.description) document.getElementById('m_description').value = data.description;
    if (data.author_bio)  document.getElementById('m_author_bio').value  = data.author_bio;
    if (data.cover_url)   document.getElementById('m_cover_url').value   = 'url:'+data.cover_url;
  } catch(e) { alert('Network error: '+e.message); }
  finally     { btn.classList.remove('loading'); }
}

// Submit edit via AJAX
async function submitEdit() {
  const form = document.getElementById('edit_form');
  const fd   = new FormData(form);
  const suc  = document.getElementById('edit_success');
  const err  = document.getElementById('edit_error');
  suc.style.display = err.style.display = 'none';

  try {
    const r    = await fetch('edit_book.php', { method:'POST', body: fd });
    const data = await r.json();
    if (data.success) {
      suc.textContent = data.message;
      suc.style.display = 'flex';
      setTimeout(() => { closeModal(); location.reload(); }, 1200);
    } else {
      err.textContent = data.message;
      err.style.display = 'flex';
    }
  } catch(e) {
    err.textContent = 'Network error: '+e.message;
    err.style.display = 'flex';
  }
}

// Auto-open edit modal from URL ?edit=id
<?php if ($editId): ?>
(function() {
  const rows = document.querySelectorAll('#books_tbody tr');
  const btn = document.querySelector('[onclick*="openEditModal"]');
  // find the row with the matching book_id
  const allBtns = document.querySelectorAll('[onclick^="openEditModal"]');
  allBtns.forEach(b => {
    try {
      const data = JSON.parse(b.getAttribute('onclick').match(/openEditModal\((.*)\)/)[1]);
      if (data.book_id == <?= $editId ?>) b.click();
    } catch(e){}
  });
})();
<?php endif; ?>

// ── Sell modal ───────────────────────────────────────────────────────
function openSellModal(bookId, title, stock, price) {
  document.getElementById('sell_book_id').value = bookId;
  document.getElementById('sell_book_title').textContent = title;
  document.getElementById('sell_book_price').textContent = '₱' + parseFloat(price).toFixed(2);
  document.getElementById('sell_book_stock').textContent = stock;
  document.getElementById('sell_qty').value = 1;
  document.getElementById('sell_qty').max = stock;
  document.getElementById('sell_success').style.display = 'none';
  document.getElementById('sell_error').style.display = 'none';
  document.getElementById('sell_modal').classList.add('open');
}

function closeSellModal() {
  document.getElementById('sell_modal').classList.remove('open');
}

document.getElementById('sell_modal')?.addEventListener('click', e => {
  if (e.target === e.currentTarget) closeSellModal();
});

async function submitSell() {
  const fd  = new FormData(document.getElementById('sell_form'));
  const suc = document.getElementById('sell_success');
  const err = document.getElementById('sell_error');
  suc.style.display = err.style.display = 'none';

  try {
    const r    = await fetch('sell_book.php', { method:'POST', body: fd });
    const data = await r.json();
    if (data.success) {
      suc.textContent = data.message;
      suc.style.display = 'flex';
      setTimeout(() => { closeSellModal(); location.reload(); }, 1200);
    } else {
      err.textContent = data.message;
      err.style.display = 'flex';
    }
  } catch(e) {
    err.textContent = 'Network error: ' + e.message;
    err.style.display = 'flex';
  }
}

// ── Delete book (admin only) ─────────────────────────────────────────
async function deleteBook(bookId, title) {
  if (!confirm('Are you sure you want to delete "' + title + '"? This cannot be undone.')) return;
  try {
    const fd = new FormData();
    fd.append('book_id', bookId);
    const r    = await fetch('delete_book.php', { method:'POST', body: fd });
    const data = await r.json();
    if (data.success) {
      alert(data.message);
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  } catch(e) {
    alert('Network error: ' + e.message);
  }
}
</script>

<?php include 'includes/footer.php'; ?>
