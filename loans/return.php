<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Invalid loan ID.');
    redirect(BASE_URL . '/loans/index.php');
}

// Fetch the loan with book and member info
$stmt = $pdo->prepare(
    'SELECT l.loan_id AS id, l.loan_id, l.book_id, l.member_id, l.date_issued, l.due_date, l.date_returned,
            b.title AS book_title, b.available_copies, b.total_copies,
            m.full_name AS member_name, m.member_no AS member_number
     FROM loans l
     JOIN books b ON l.book_id = b.book_id
     JOIN members m ON l.member_id = m.member_id
     WHERE l.loan_id = ?'
);
$stmt->execute([$id]);
$loan = $stmt->fetch();

if (!$loan) {
    setFlash('danger', 'Loan record not found.');
    redirect(BASE_URL . '/loans/index.php');
}

if ($loan['date_returned'] !== null) {
    setFlash('warning', 'This book was already marked as returned on ' . e($loan['date_returned']) . '.');
    redirect(BASE_URL . '/loans/index.php');
}

// Calculate late fine if overdue
$returnDate = date('Y-m-d');
$dueDate    = $loan['due_date'];
$fineMessage = '';

if ($returnDate > $dueDate) {
    $diffDays = (int)ceil((strtotime($returnDate) - strtotime($dueDate)) / (60 * 60 * 24));
    if ($diffDays > 0) {
        $fineRatePerDay = 0.50; // $0.50 per day
        $fineAmount = $diffDays * $fineRatePerDay;
        $fineMessage = ' (Book was ' . $diffDays . ' day(s) overdue. Calculated late fine: $' . number_format($fineAmount, 2) . ')';
    }
}

try {
    // Business rule: Issuing reduces available copies by one, and returning increases them by one.
    // Use a database transaction so the loan record and the copy count change together or not at all.
    $pdo->beginTransaction();

    // 1. Mark loan as returned
    $updateLoan = $pdo->prepare(
        'UPDATE loans SET date_returned = ? WHERE loan_id = ?'
    );
    $updateLoan->execute([$returnDate, $id]);

    // 2. Increase available copies by 1 (capped at total_copies)
    $updateBook = $pdo->prepare(
        'UPDATE books SET available_copies = LEAST(total_copies, available_copies + 1) WHERE book_id = ?'
    );
    $updateBook->execute([$loan['book_id']]);

    $pdo->commit();

    setFlash('success', 'Book "' . e($loan['book_title']) . '" returned successfully by ' . e($loan['member_name']) . '.' . $fineMessage);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Failed to process book return: ' . e($e->getMessage()));
}

redirect(BASE_URL . '/loans/index.php');
