<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=books_' . date('Y-m-d_His') . '.csv');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['ID', 'ISBN', 'Title', 'Author', 'Category', 'Publisher', 'Year Published', 'Total Copies', 'Available Copies']);

$sql = "SELECT b.id, b.isbn, b.title, a.name AS author_name, c.name AS category_name,
               b.publisher, b.year_published, b.total_copies, b.available_copies
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN categories c ON b.category_id = c.id
        ORDER BY b.title ASC";

$stmt = $pdo->query($sql);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['isbn'],
        $row['title'],
        $row['author_name'] ?? 'N/A',
        $row['category_name'] ?? 'N/A',
        $row['publisher'] ?? 'N/A',
        $row['year_published'] ?? 'N/A',
        $row['total_copies'],
        $row['available_copies']
    ]);
}

fclose($output);
exit;
