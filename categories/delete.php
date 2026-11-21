<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_post(BASE_URL . '/categories/index.php');

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
    setFlash('danger', 'Cannot delete category "' . $category['name'] . '" — it is currently assigned to ' . $bookCount . ' book(s).');
    redirect(BASE_URL . '/categories/index.php');
}

// No books use this category, so its (empty) subcategories can go with it.
try {
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM sub_categories WHERE category_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM categories WHERE category_id = ?')->execute([$id]);
    $pdo->commit();
    setFlash('success', 'Category "' . $category['name'] . '" deleted successfully.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Could not delete category "' . $category['name'] . '": ' . $e->getMessage());
}

redirect(BASE_URL . '/categories/index.php');
