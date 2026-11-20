<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Invalid author ID.');
    redirect(BASE_URL . '/authors/index.php');
}

// Verify author exists
$stmt = $pdo->prepare("SELECT author_id AS id, author_id, name FROM authors WHERE author_id = ? LIMIT 1");
$stmt->execute([$id]);
$author = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$author) {
    setFlash('error', 'Author not found.');
    redirect(BASE_URL . '/authors/index.php');
}

// Check if author has books
$chk = $pdo->prepare("SELECT COUNT(*) FROM book_authors WHERE author_id = ?");
$chk->execute([$id]);
$bookCount = (int)$chk->fetchColumn();

if ($bookCount > 0) {
    setFlash('error', 'Cannot delete: author has books in the system.');
    redirect(BASE_URL . '/authors/index.php');
}

// Safe to delete
$del = $pdo->prepare("DELETE FROM authors WHERE author_id = ?");
$del->execute([$id]);
setFlash('success', 'Author "' . $author['name'] . '" deleted successfully.');
redirect(BASE_URL . '/authors/index.php');
