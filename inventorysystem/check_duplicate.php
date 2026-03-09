<?php
/**
 * Duplicate Book Check
 * GET ?title=...&publisher=... or ?isbn=...
 * Returns JSON { duplicate, book_id, book_title, quantity, publisher, out_of_stock, same_publisher }
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

$conn  = getDBConnection();
$title = trim($_GET['title'] ?? '');
$isbn  = trim($_GET['isbn'] ?? '');
$pub   = trim($_GET['publisher'] ?? '');

if (empty($title) && empty($isbn)) {
    echo json_encode(['duplicate' => false]);
    closeDBConnection($conn);
    exit();
}

if ($isbn) {
    $stmt = $conn->prepare("SELECT book_id, title, quantity, publisher FROM Books WHERE isbn = ? LIMIT 1");
    $stmt->bind_param("s", $isbn);
} else {
    $stmt = $conn->prepare("SELECT book_id, title, quantity, publisher FROM Books WHERE LOWER(title) = LOWER(?) LIMIT 1");
    $stmt->bind_param("s", $title);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $isOutOfStock  = (int)$row['quantity'] === 0;
    $samePublisher = ($pub !== '' && $row['publisher'] === $pub);
    echo json_encode([
        'duplicate'      => true,
        'book_id'        => $row['book_id'],
        'book_title'     => $row['title'],
        'quantity'       => $row['quantity'],
        'publisher'      => $row['publisher'],
        'out_of_stock'   => $isOutOfStock,
        'same_publisher' => $samePublisher,
    ]);
} else {
    echo json_encode(['duplicate' => false]);
}
$stmt->close();
closeDBConnection($conn);
