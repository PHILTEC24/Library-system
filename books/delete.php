<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Invalid book ID.');
    header('Location: ' . BASE_URL . '/books/index.php');
    exit;
}

// Fetch the book
$bookStmt = $pdo->prepare('SELECT book_id AS id, book_id, title FROM books WHERE book_id = ?');
$bookStmt->execute([$id]);
$book = $bookStmt->fetch();

if (!$book) {
    setFlash('danger', 'Book not found.');
    header('Location: ' . BASE_URL . '/books/index.php');
    exit;
}

// Check for active loans on this book
$activeCheck = $pdo->prepare(
    'SELECT COUNT(*) FROM loans WHERE book_id = ? AND date_returned IS NULL'
);
$activeCheck->execute([$id]);
$activeCount = $activeCheck->fetchColumn();

if ($activeCount > 0) {
    setFlash('danger', 'Cannot delete "' . htmlspecialchars($book['title']) . '" — it has ' . $activeCount . ' active loan(s). Return the book(s) first.');
    header('Location: ' . BASE_URL . '/books/index.php');
    exit;
}

// Check for any historical loans to avoid foreign key restriction errors
$historyCheck = $pdo->prepare('SELECT COUNT(*) FROM loans WHERE book_id = ?');
$historyCheck->execute([$id]);
if ($historyCheck->fetchColumn() > 0) {
    setFlash('danger', 'Cannot delete "' . htmlspecialchars($book['title']) . '" — it has associated loan records in library history.');
    header('Location: ' . BASE_URL . '/books/index.php');
    exit;
}

// Safe to delete
$del = $pdo->prepare('DELETE FROM books WHERE book_id = ?');
$del->execute([$id]);

setFlash('success', 'Book "' . htmlspecialchars($book['title']) . '" deleted successfully.');
header('Location: ' . BASE_URL . '/books/index.php');
exit;
