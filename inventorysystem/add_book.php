<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$conn = getDBConnection();
$message = '';
$messageType = '';

define('ALLOWED_TYPES', ['image/jpeg','image/png','image/gif','image/webp']);
define('MAX_SIZE', 2 * 1024 * 1024);
$uploadDir = __DIR__ . '/uploads/';

function handleUpload($field, $dir, &$errs) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK)   { $errs[] = ucfirst(str_replace('_',' ',$field)).' upload error ('.$f['error'].')'; return false; }
    if ($f['size'] > MAX_SIZE)            { $errs[] = ucfirst(str_replace('_',' ',$field)).' must be under 2 MB'; return false; }
    $fi = finfo_open(FILEINFO_MIME_TYPE); $mime = finfo_file($fi,$f['tmp_name']); finfo_close($fi);
    if (!in_array($mime, ALLOWED_TYPES))  { $errs[] = ucfirst(str_replace('_',' ',$field)).' must be an image (JPEG/PNG/GIF/WebP)'; return false; }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $name = uniqid($field.'_',true).'.'.$ext;
    if (!move_uploaded_file($f['tmp_name'], $dir.$name)) { $errs[] = 'Could not save '.$field; return false; }
    return $name;
}

$categories = $conn->query("SELECT * FROM Category ORDER BY name");
$publishers  = $conn->query("SELECT * FROM publisher ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $title       = sanitizeInput($_POST['title'] ?? '');
    $author      = sanitizeInput($_POST['author'] ?? '');
    $isbn        = sanitizeInput($_POST['isbn'] ?? '');
    $price       = sanitizeInput($_POST['price'] ?? '');
    $quantity    = sanitizeInput($_POST['quantity'] ?? '0');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $publisher   = sanitizeInput($_POST['publisher'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $author_bio  = sanitizeInput($_POST['author_bio'] ?? '');
    $book_cover_url  = sanitizeInput($_POST['book_cover_url'] ?? '');   // from API
    $author_photo_url = sanitizeInput($_POST['author_photo_url'] ?? '');
    $added_by    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if (empty($title))                                       $errors[] = 'Title is required';
    if (empty($author))                                      $errors[] = 'Author is required';
    if (empty($price) || !is_numeric($price) || $price < 0) $errors[] = 'Valid price is required';
    if (!is_numeric($quantity) || $quantity < 0)             $errors[] = 'Valid quantity is required';
    if (!$category_id)                                       $errors[] = 'Category is required';
    if (empty($publisher))                                   $errors[] = 'Publisher is required';

    // File uploads (override API cover if a file is chosen)
    $book_cover_file   = handleUpload('book_cover',   $uploadDir, $errors);
    $author_photo_file = handleUpload('author_photo', $uploadDir, $errors);
    $final_cover       = $book_cover_file  ?? ($book_cover_url  ?: null);
    $final_author_img  = $author_photo_file ?? ($author_photo_url ?: null);

    // Prefix 'file:' for local files, 'url:' for remote
    if ($book_cover_file)   $final_cover     = 'file:'.$book_cover_file;
    elseif ($book_cover_url) $final_cover = 'url:'.$book_cover_url;
    if ($author_photo_file)   $final_author_img = 'file:'.$author_photo_file;
    elseif ($author_photo_url) $final_author_img = 'url:'.$author_photo_url;

    if (empty($errors)) {
        // Check for existing book with same title
        $dupStmt = $conn->prepare("SELECT book_id, title, publisher, quantity FROM Books WHERE LOWER(title) = LOWER(?) LIMIT 1");
        $dupStmt->bind_param("s", $title);
        $dupStmt->execute();
        $existing = $dupStmt->get_result()->fetch_assoc();
        $dupStmt->close();

        if ($existing) {
            if ($existing['publisher'] === $publisher) {
                // Same title + same publisher → add quantity to existing book
                $addQty = (int)$quantity;
                $updStmt = $conn->prepare("UPDATE Books SET quantity = quantity + ? WHERE book_id = ?");
                $updStmt->bind_param("ii", $addQty, $existing['book_id']);
                if ($updStmt->execute()) {
                    $newQty = (int)$existing['quantity'] + $addQty;
                    $message = "Book \"{$title}\" already exists. Added {$addQty} to stock (new qty: {$newQty}).";
                    $messageType = 'success';
                    $_POST = [];
                } else {
                    $message = 'Database error: ' . $updStmt->error;
                    $messageType = 'error';
                }
                $updStmt->close();
            } else {
                // Same title + different publisher → error
                $message = "A book titled \"{$existing['title']}\" already exists under a different publisher. Please use a different title or match the existing publisher.";
                $messageType = 'error';
            }
        } else {
            // No duplicate — insert new book
            $stmt = $conn->prepare("INSERT INTO Books
                (title,author,isbn,price,quantity,category_id,publisher,description,author_bio,book_cover,author_photo,added_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssdiisssss" . "i",
                $title,$author,$isbn,$price,$quantity,$category_id,
                $publisher,$description,$author_bio,$final_cover,$final_author_img,$added_by);

            if ($stmt->execute()) {
                $message = "Book \"{$title}\" added successfully!";
                $messageType = 'success';
                $_POST = [];
            } else {
                // clean up uploaded files on error
                if ($book_cover_file  && file_exists($uploadDir.$book_cover_file))   unlink($uploadDir.$book_cover_file);
                if ($author_photo_file && file_exists($uploadDir.$author_photo_file)) unlink($uploadDir.$author_photo_file);
                $message = 'Database error: '.$stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

include 'includes/header.php';
?>

<div class="form-container glass-card">
  <h2>Add New Book</h2>

  <?php if ($message): ?>
  <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>">
    <?= $message ?>
  </div>
  <?php endif; ?>

  <!-- ── Smart-Sync fetch row ── -->
  <div class="form-group">
    <label>Smart-Sync <span style="color:var(--text-3);text-transform:none;font-weight:400;font-size:.72rem;">— enter ISBN or Title, then fetch</span></label>
    <div class="isbn-wrap">
      <input type="text" id="smart_query" placeholder="e.g.  978-0-7432-7356-5  or  The Great Gatsby" autocomplete="off">
      <select id="smart_type" style="width:auto;flex-shrink:0;padding:10px 10px;">
        <option value="title">Title</option>
        <option value="isbn">ISBN</option>
      </select>
      <button type="button" class="btn btn-fetch" id="btn_fetch" onclick="fetchBookInfo()">
        <span class="spinner"></span>
        <span class="btn-text">⚡ Fetch</span>
      </button>
    </div>
    <div class="hint-text">Automatically fills in title, author, cover &amp; description from Open Library / Google Books.</div>
  </div>

  <!-- API preview -->
  <div class="api-preview" id="api_preview">
    <img id="pv_img" src="" alt="cover">
    <div class="api-preview-info">
      <div class="pv-title" id="pv_title"></div>
      <div class="pv-author" id="pv_author"></div>
      <div class="pv-desc"  id="pv_desc"></div>
    </div>
  </div>

  <!-- Duplicate warning -->
  <div class="dup-banner" id="dup_banner" style="display:none;">
    ⚠ <strong id="dup_title"></strong> already exists under a different publisher.
    You cannot add the same title with a different publisher.
  </div>
  <div class="dup-banner" id="dup_same_banner" style="display:none;border-color:var(--green);background:var(--green-dim);color:var(--green);">
    ✅ <strong id="dup_same_title"></strong> already exists with the same publisher (qty: <strong id="dup_same_qty"></strong>).
    Submitting will automatically add to the existing stock.
  </div>

  <form method="POST" action="" enctype="multipart/form-data" id="add-book-form">
    <!-- Hidden API data -->
    <input type="hidden" name="book_cover_url"   id="h_cover_url">
    <input type="hidden" name="author_photo_url" id="h_author_url">

    <div class="form-row">
      <div class="form-group">
        <label>ISBN <span class="hint-text" style="text-transform:none">(auto-filled)</span></label>
        <input type="text" name="isbn" id="f_isbn" value="<?= htmlspecialchars($_POST['isbn'] ?? '') ?>" placeholder="978-…">
      </div>
      <div class="form-group">
        <label>Year / Published</label>
        <input type="text" name="year" id="f_year" value="<?= htmlspecialchars($_POST['year'] ?? '') ?>" placeholder="Auto-filled">
      </div>
    </div>

    <div class="form-group">
      <label>Title <span class="req">*</span></label>
      <input type="text" name="title" id="f_title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Author <span class="req">*</span></label>
        <input type="text" name="author" id="f_author" value="<?= htmlspecialchars($_POST['author'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Author Photo <span class="hint-text" style="text-transform:none">(auto or upload)</span></label>
        <input type="file" name="author_photo" id="f_author_photo" accept="image/*">
      </div>
    </div>

    <div class="form-group">
      <label>Author Biography <span class="hint-text" style="text-transform:none">(auto-filled)</span></label>
      <textarea name="author_bio" id="f_author_bio" rows="3"><?= htmlspecialchars($_POST['author_bio'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label>Description <span class="hint-text" style="text-transform:none">(auto-filled)</span></label>
      <textarea name="description" id="f_description" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Price (₱) <span class="req">*</span></label>
        <input type="number" name="price" id="f_price" step="0.01" min="0" placeholder="0.00" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Quantity <span class="req">*</span></label>
        <input type="number" name="quantity" id="f_quantity" min="0" value="<?= htmlspecialchars($_POST['quantity'] ?? '0') ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Category <span class="req">*</span></label>
        <select name="category_id" id="f_category" required>
          <option value="">— Select —</option>
          <?php while ($r = $categories->fetch_assoc()): ?>
          <option value="<?= $r['category_id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $r['category_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($r['name']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Publisher <span class="req">*</span></label>
        <select name="publisher" required>
          <option value="">— Select —</option>
          <?php while ($r = $publishers->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($r['publisher']) ?>" <?= (isset($_POST['publisher']) && $_POST['publisher'] == $r['publisher']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($r['name']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Book Cover Image <span class="hint-text" style="text-transform:none">(auto-fetched or upload)</span></label>
      <input type="file" name="book_cover" accept="image/*">
      <div class="hint-text">Max 2 MB · JPEG / PNG / GIF / WebP. If left empty, the API cover is used.</div>
    </div>

    <button type="submit" class="btn btn-primary btn-block mt-2">Add Book to Inventory</button>
  </form>
</div>

<script>
// ── Smart-Sync ────────────────────────────────────────────────────────
let dupCheckTimer = null;

async function fetchBookInfo() {
  const query = document.getElementById('smart_query').value.trim();
  const type  = document.getElementById('smart_type').value;
  if (!query) return;

  const btn = document.getElementById('btn_fetch');
  btn.classList.add('loading');

  try {
    const r    = await fetch(`api_fetch_book.php?q=${encodeURIComponent(query)}&type=${type}`);
    const data = await r.json();

    if (data.error) {
      alert('Smart-Sync: ' + data.error);
      btn.classList.remove('loading');
      return;
    }

    // Populate fields
    if (data.title)      document.getElementById('f_title').value       = data.title;
    if (data.author)     document.getElementById('f_author').value      = data.author;
    if (data.isbn)       document.getElementById('f_isbn').value        = data.isbn;
    if (data.year)       document.getElementById('f_year').value        = data.year;
    if (data.description) document.getElementById('f_description').value = data.description;
    if (data.author_bio) document.getElementById('f_author_bio').value  = data.author_bio;

    // Store remote URLs in hidden inputs
    document.getElementById('h_cover_url').value  = data.cover_url  || '';
    document.getElementById('h_author_url').value = data.author_photo || '';

    // Show preview card
    const prev = document.getElementById('api_preview');
    const img  = document.getElementById('pv_img');
    if (data.cover_url) { img.src = data.cover_url; img.style.display='block'; }
    else                { img.style.display='none'; }
    document.getElementById('pv_title').textContent  = data.title;
    document.getElementById('pv_author').textContent = data.author;
    document.getElementById('pv_desc').textContent   = data.description;
    prev.classList.add('visible');

    // Trigger duplicate check
    checkDuplicate(data.title, data.isbn);

  } catch(e) {
    alert('Network error: ' + e.message);
  } finally {
    btn.classList.remove('loading');
  }
}

// Real-time duplicate detection on title change
document.getElementById('f_title').addEventListener('input', function () {
  clearTimeout(dupCheckTimer);
  dupCheckTimer = setTimeout(() => checkDuplicate(this.value, ''), 600);
});

async function checkDuplicate(title, isbn) {
  if (!title && !isbn) return;
  const pub = document.querySelector('#add-book-form select[name="publisher"]').value;
  let params = isbn ? `isbn=${encodeURIComponent(isbn)}` : `title=${encodeURIComponent(title)}`;
  if (pub) params += `&publisher=${encodeURIComponent(pub)}`;
  const r    = await fetch(`check_duplicate.php?${params}`);
  const data = await r.json();
  const errBanner  = document.getElementById('dup_banner');
  const sameBanner = document.getElementById('dup_same_banner');
  const submitBtn  = document.querySelector('#add-book-form button[type="submit"]');
  if (data.duplicate) {
    if (data.same_publisher) {
      // Same title + same publisher → will auto-add qty
      errBanner.style.display = 'none';
      document.getElementById('dup_same_title').textContent = data.book_title;
      document.getElementById('dup_same_qty').textContent   = data.quantity;
      sameBanner.style.display = 'flex';
      submitBtn.disabled = false;
    } else {
      // Same title + different publisher → block
      document.getElementById('dup_title').textContent = data.book_title;
      errBanner.style.display = 'flex';
      sameBanner.style.display = 'none';
      submitBtn.disabled = true;
    }
  } else {
    errBanner.style.display = 'none';
    sameBanner.style.display = 'none';
    submitBtn.disabled = false;
  }
}

// Re-check when publisher changes
document.querySelector('#add-book-form select[name="publisher"]').addEventListener('change', function() {
  const title = document.getElementById('f_title').value.trim();
  if (title) checkDuplicate(title, '');
});

// Allow Enter key in fetch field
document.getElementById('smart_query').addEventListener('keydown', e => {
  if (e.key === 'Enter') { e.preventDefault(); fetchBookInfo(); }
});
</script>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
