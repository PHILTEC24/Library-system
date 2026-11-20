<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=loans_' . date('Y-m-d_His') . '.csv');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['Loan ID', 'Book Title', 'ISBN', 'Member Name', 'Member Number', 'Date Issued', 'Due Date', 'Date Returned', 'Status']);

$sql = "SELECT l.loan_id AS id, b.title AS book_title, b.isbn, m.full_name AS member_name, m.member_no AS member_number,
               l.date_issued, l.due_date, l.date_returned,
               CASE
                   WHEN l.date_returned IS NOT NULL THEN 'returned'
                   WHEN l.due_date < CURDATE() THEN 'overdue'
                   ELSE 'active'
               END AS status
        FROM loans l
        LEFT JOIN books b ON l.book_id = b.book_id
        LEFT JOIN members m ON l.member_id = m.member_id
        ORDER BY l.loan_id DESC";

$stmt = $pdo->query($sql);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['book_title'] ?? 'Deleted Book',
        $row['isbn'] ?? 'N/A',
        $row['member_name'] ?? 'Deleted Member',
        $row['member_number'] ?? 'N/A',
        $row['date_issued'],
        $row['due_date'],
        $row['date_returned'] ?? 'Not Returned',
        ucfirst($row['status'])
    ]);
}

fclose($output);
exit;
