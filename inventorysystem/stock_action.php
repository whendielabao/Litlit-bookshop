<?php
/**
 * Stock Action Handler
 * Accepts POST: book_id, action_type, quantity, notes, damage_remarks
 * Returns JSON { success, message, new_quantity }
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

$bookId = (int)($_POST['book_id'] ?? 0);
$actionType = trim($_POST['action_type'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 0);
$notes = sanitizeInput($_POST['notes'] ?? '');
$damageRemarks = sanitizeInput($_POST['damage_remarks'] ?? '');
$allowedActions = ['stock_in', 'stock_out', 'restock', 'pull_out'];

if (!$bookId) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit();
}
if (!in_array($actionType, $allowedActions, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid stock action']);
    exit();
}
if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit();
}
if ($actionType === 'pull_out' && $damageRemarks === '') {
    echo json_encode(['success' => false, 'message' => 'Damage remarks are required for pull out']);
    exit();
}

$conn = getDBConnection();
$conn->begin_transaction();

try {
    $bookStmt = $conn->prepare("SELECT book_id, title, quantity FROM Books WHERE book_id = ? FOR UPDATE");
    $bookStmt->bind_param("i", $bookId);
    $bookStmt->execute();
    $book = $bookStmt->get_result()->fetch_assoc();
    $bookStmt->close();

    if (!$book) {
        throw new Exception('Book not found');
    }

    $beforeQty = (int)$book['quantity'];
    $isInbound = in_array($actionType, ['stock_in', 'restock'], true);
    $changeQty = $isInbound ? $quantity : -$quantity;
    $afterQty = $beforeQty + $changeQty;

    if ($afterQty < 0) {
        throw new Exception('Not enough stock. Available: ' . $beforeQty);
    }

    $upd = $conn->prepare("UPDATE Books SET quantity = ? WHERE book_id = ?");
    $upd->bind_param("ii", $afterQty, $bookId);
    if (!$upd->execute()) {
        throw new Exception('Could not update stock: ' . $upd->error);
    }
    $upd->close();

    $movedBy = $_SESSION['user_id'] ?? null;
    $ins = $conn->prepare("INSERT INTO InventoryMovements
        (book_id, movement_type, quantity_before, quantity_change, quantity_after, notes, damage_remarks, moved_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->bind_param("isiiissi", $bookId, $actionType, $beforeQty, $changeQty, $afterQty, $notes, $damageRemarks, $movedBy);
    if (!$ins->execute()) {
        throw new Exception('Could not log stock movement: ' . $ins->error);
    }
    $ins->close();

    $conn->commit();

    $labels = [
        'stock_in' => 'Stock In',
        'stock_out' => 'Stock Out',
        'restock' => 'Restock',
        'pull_out' => 'Pull Out',
    ];

    echo json_encode([
        'success' => true,
        'new_quantity' => $afterQty,
        'message' => $labels[$actionType] . ' recorded for "' . $book['title'] . '". New stock: ' . $afterQty,
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

closeDBConnection($conn);
