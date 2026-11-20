<?php
if (!defined('BASE_URL')) define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=books_' . date('Y-m-d_His') . '.csv');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['ID', 'ISBN', 'Title', 'Author', 'Category', 'Subcategory', 'Publisher', 'Year Published', 'Total Copies', 'Available Copies']);

$isPgsql = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql');
$authorAgg = $isPgsql
    ? "COALESCE((SELECT STRING_AGG(a2.name, ', ' ORDER BY (ba2.author_role='primary') DESC, a2.name) FROM book_authors ba2 JOIN authors a2 ON ba2.author_id = a2.author_id WHERE ba2.book_id = b.book_id), '') AS author_name,"
    : "GROUP_CONCAT(DISTINCT a.name ORDER BY (ba.author_role='primary') DESC, a.name SEPARATOR ', ') AS author_name,";

$sql = "SELECT b.book_id AS id, b.isbn, b.title,
               $authorAgg
               c.name AS category_name, s.name AS subcategory_name,
               b.publisher, b.year_published, b.total_copies, b.available_copies
        FROM books b
        LEFT JOIN book_authors ba ON b.book_id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.author_id
        LEFT JOIN sub_categories s ON b.sub_category_id = s.sub_category_id
        LEFT JOIN categories c ON s.category_id = c.category_id
        GROUP BY b.book_id, b.isbn, b.title, b.publisher, b.year_published, b.total_copies, b.available_copies, c.name, s.name
        ORDER BY b.title ASC";

$stmt = $pdo->query($sql);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['isbn'],
        $row['title'],
        $row['author_name'] ?? 'N/A',
        $row['category_name'] ?? 'N/A',
        $row['subcategory_name'] ?? 'N/A',
        $row['publisher'] ?? 'N/A',
        $row['year_published'] ?? 'N/A',
        $row['total_copies'],
        $row['available_copies']
    ]);
}

fclose($output);
exit;
