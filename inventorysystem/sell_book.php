<?php
/**
 * Sell Book Handler
 * Accepts POST with book_id and quantity; records sale and decrements stock.
 * Returns JSON { success, message }
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$book_id  = (int)($_POST['book_id'] ?? 0);
$qty      = (int)($_POST['quantity'] ?? 0);

if (!$book_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit();
}
if ($qty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit();
}

$conn = getDBConnection();

// Verify book exists and has enough stock
$stmt = $conn->prepare("SELECT book_id, title, quantity, price FROM Books WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    closeDBConnection($conn);
    exit();
}

if ((int)$book['quantity'] < $qty) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock. Available: ' . $book['quantity']]);
    closeDBConnection($conn);
    exit();
}

$price = $book['price'];
$userId = $_SESSION['user_id'] ?? null;

// Insert sold record
$ins = $conn->prepare("INSERT INTO SoldHistory (book_id, quantity, price_at_sale, sold_by) VALUES (?, ?, ?, ?)");
$ins->bind_param("iidi", $book_id, $qty, $price, $userId);

if (!$ins->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $ins->error]);
    $ins->close();
    closeDBConnection($conn);
    exit();
}
$soldId = (int)$conn->insert_id;
$ins->close();

$soldSerial = assignSerialNumber($conn, 'SoldHistory', 'sold_id', 'sold_serial', 'SL', $soldId);

// Decrement stock
$upd = $conn->prepare("UPDATE Books SET quantity = quantity - ? WHERE book_id = ?");
$upd->bind_param("ii", $qty, $book_id);
$upd->execute();
$upd->close();

$total = number_format($price * $qty, 2);
echo json_encode([
    'success' => true,
    'message' => ($soldSerial
        ? "Sale {$soldSerial} recorded: sold {$qty} × \"{$book['title']}\" (₱{$total})"
        : "Sold {$qty} × \"{$book['title']}\" (₱{$total})"),
    'sold_serial' => $soldSerial
]);

closeDBConnection($conn);
