<?php
/**
 * Delete Book Handler (Admin only)
 * Accepts POST with book_id; returns JSON { success, message }
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin only.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$book_id = (int)($_POST['book_id'] ?? 0);

if (!$book_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit();
}

$conn = getDBConnection();

// Get book title before deleting
$stmt = $conn->prepare("SELECT title FROM Books WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    closeDBConnection($conn);
    exit();
}

$del = $conn->prepare("DELETE FROM Books WHERE book_id = ?");
$del->bind_param("i", $book_id);

if ($del->execute()) {
    echo json_encode(['success' => true, 'message' => "\"{$book['title']}\" has been deleted."]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $del->error]);
}

$del->close();
closeDBConnection($conn);
