<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Invalid category ID.');
    redirect(BASE_URL . '/categories/index.php');
}

$stmt = $pdo->prepare('SELECT category_id AS id, category_id, name FROM categories WHERE category_id = ?');
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    setFlash('danger', 'Category not found.');
    redirect(BASE_URL . '/categories/index.php');
}

// Check if any books are associated with this category via sub_categories
$bookCheck = $pdo->prepare('SELECT COUNT(*) FROM sub_categories sc JOIN books b ON b.sub_category_id = sc.sub_category_id WHERE sc.category_id = ?');
$bookCheck->execute([$id]);
$bookCount = $bookCheck->fetchColumn();

if ($bookCount > 0) {
    setFlash('danger', 'Cannot delete category "' . e($category['name']) . '" — it is currently assigned to ' . $bookCount . ' book(s).');
    redirect(BASE_URL . '/categories/index.php');
}

// Check if sub_categories exist
$subCheck = $pdo->prepare('SELECT COUNT(*) FROM sub_categories WHERE category_id = ?');
$subCheck->execute([$id]);
if ($subCheck->fetchColumn() > 0) {
    setFlash('danger', 'Cannot delete category "' . e($category['name']) . '" — subcategories exist under it. Delete them first.');
    redirect(BASE_URL . '/categories/index.php');
}

try {
    $del = $pdo->prepare('DELETE FROM categories WHERE category_id = ?');
    $del->execute([$id]);
    setFlash('success', 'Category "' . e($category['name']) . '" deleted successfully.');
} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . e($e->getMessage()));
}

redirect(BASE_URL . '/categories/index.php');
