<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireAdmin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['publisher_name'] ?? '');
    $contact = trim($_POST['contact_info'] ?? '');

    if ($name === '') {
        $error = 'Publisher name is required.';
    } else {
        $conn = getDBConnection();
        $check = $conn->prepare("SELECT publisher FROM publisher WHERE publisher = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Publisher \"$name\" already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO publisher (publisher, name, contact_info) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $name, $contact);
            if ($stmt->execute()) {
                $message = "Publisher \"$name\" added.";
                $_POST = [];
            } else {
                $error = 'Database error — please try again.';
            }
            $stmt->close();
        }

        $check->close();
        closeDBConnection($conn);
    }
}
?>
<?php include 'includes/header.php'; ?>
  <div class="page-header">
    <h1>Create Publisher</h1>
    <a href="add_publisher.php" class="btn btn-ghost">← List Publishers</a>
  </div>

  <div class="glass-card" style="max-width:560px">
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Publisher Name <span class="req">*</span></label>
        <input type="text" name="publisher_name" placeholder="e.g. Penguin Random House" value="<?= htmlspecialchars($_POST['publisher_name'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label>Contact Info <span style="opacity:.6">(optional)</span></label>
        <input type="text" name="contact_info" placeholder="Email or phone" value="<?= htmlspecialchars($_POST['contact_info'] ?? '') ?>">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Create Publisher</button>
        <a href="add_publisher.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
<?php include 'includes/footer.php'; ?>
