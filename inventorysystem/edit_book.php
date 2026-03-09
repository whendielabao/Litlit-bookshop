<?php
/**
 * Edit Book Handler
 * Accepts POST with book fields; returns JSON { success, message }
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

$book_id    = (int)($_POST['book_id'] ?? 0);
$title      = sanitizeInput($_POST['title'] ?? '');
$author     = sanitizeInput($_POST['author'] ?? '');
$isbn       = sanitizeInput($_POST['isbn'] ?? '');
$price      = sanitizeInput($_POST['price'] ?? '');
$quantity   = sanitizeInput($_POST['quantity'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0);
$publisher  = sanitizeInput($_POST['publisher'] ?? '');
$description = sanitizeInput($_POST['description'] ?? '');
$author_bio = sanitizeInput($_POST['author_bio'] ?? '');
$book_cover = sanitizeInput($_POST['book_cover'] ?? ''); // remote URL from API
$author_photo = sanitizeInput($_POST['author_photo'] ?? '');

$errors = [];
if (!$book_id)                                        $errors[] = 'Invalid book ID';
if (empty($title))                                    $errors[] = 'Title is required';
if (empty($author))                                   $errors[] = 'Author is required';
if (empty($price) || !is_numeric($price) || $price < 0) $errors[] = 'Valid price is required';
if (!is_numeric($quantity) || $quantity < 0)          $errors[] = 'Valid quantity is required';
if (!$category_id)                                    $errors[] = 'Category is required';
if (empty($publisher))                                $errors[] = 'Publisher is required';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

$conn = getDBConnection();

// Verify book exists
$check = $conn->query("SELECT book_id FROM Books WHERE book_id = {$book_id}");
if (!$check || $check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    closeDBConnection($conn);
    exit();
}

$stmt = $conn->prepare("UPDATE Books SET
    title        = ?,
    author       = ?,
    isbn         = ?,
    price        = ?,
    quantity     = ?,
    category_id  = ?,
    publisher    = ?,
    description  = ?,
    author_bio   = ?,
    book_cover   = ?,
    author_photo = ?
WHERE book_id = ?");

$stmt->bind_param(
    "sssdiisssssi",
    $title, $author, $isbn, $price, $quantity,
    $category_id, $publisher, $description, $author_bio,
    $book_cover, $author_photo, $book_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Book updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
closeDBConnection($conn);
