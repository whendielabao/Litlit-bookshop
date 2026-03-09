<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireAdmin();

$message = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['publisher_name']    ?? '');
    $contact = trim($_POST['contact_info'] ?? '');
    if ($name === '') {
        $error = 'Publisher name is required.';
    } else {
        $conn  = getDBConnection();
        $check = $conn->prepare("SELECT publisher FROM publisher WHERE publisher = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "Publisher \"$name\" already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO publisher (publisher, name, contact_info) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $name, $contact);
            if ($stmt->execute()) $message = "Publisher \"$name\" added.";
            else                  $error   = 'Database error — please try again.';
            $stmt->close();
        }
        $check->close();
        closeDBConnection($conn);
    }
}

$conn = getDBConnection();
$rows = $conn->query("SELECT publisher, name, contact_info FROM publisher ORDER BY name");
$publishers = [];
while ($row = $rows->fetch_assoc()) $publishers[] = $row;
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Publishers — Bookshop Inventory</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<main>
  <div class="page-header">
    <h1>Manage Publishers</h1>
    <a href="index.php" class="btn btn-ghost">← Dashboard</a>
  </div>

  <div class="glass-card" style="max-width:560px">
    <h3 style="margin-bottom:1.4rem">Add Publisher</h3>
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?=   htmlspecialchars($error)   ?></div><?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Publisher Name <span class="req">*</span></label>
        <input type="text" name="publisher_name" placeholder="e.g. Penguin Random House" required autofocus>
      </div>
      <div class="form-group">
        <label>Contact Info <span style="opacity:.6">(optional)</span></label>
        <input type="text" name="contact_info" placeholder="Email or phone">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Add Publisher</button>
      </div>
    </form>
  </div>

  <?php if (!empty($publishers)): ?>
  <div class="glass-card" style="max-width:560px">
    <h3 style="margin-bottom:1.2rem">Existing Publishers</h3>
    <table class="books-table">
      <thead><tr><th>#</th><th>Name</th><th>Contact</th></tr></thead>
      <tbody>
        <?php foreach ($publishers as $i => $pub): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($pub['name']) ?></td>
          <td><?= htmlspecialchars($pub['contact_info'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
