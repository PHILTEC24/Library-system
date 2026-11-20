<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=members_' . date('Y-m-d_His') . '.csv');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['ID', 'Member Number', 'Full Name', 'Email', 'Phone', 'Status', 'Date Joined']);

$stmt = $pdo->query('SELECT member_id AS id, member_no AS member_number, full_name, email, phone, status, date_joined FROM members ORDER BY member_no ASC');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['member_number'],
        $row['full_name'],
        $row['email'],
        $row['phone'] ?? '',
        $row['status'],
        $row['date_joined']
    ]);
}

fclose($output);
exit;
