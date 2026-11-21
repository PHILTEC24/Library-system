<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_post(BASE_URL . '/loans/index.php');

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
    setFlash('warning', 'This book was already marked as returned on ' . format_date($loan['date_returned']) . '.');
    redirect(BASE_URL . '/loans/index.php');
}

// Calculate late fine if overdue
$returnDate = date('Y-m-d');
$dueDate    = $loan['due_date'];
$fineMessage = '';

if ($returnDate > $dueDate) {
    $diffDays = (int)(new DateTime($dueDate))->diff(new DateTime($returnDate))->format('%r%a');
    if ($diffDays > 0) {
        $finePercent = $diffDays * 5; // 5% of the book price for each delayed day
        $fineMessage = ' (Book was ' . $diffDays . ' day(s) overdue. Late fine: ' . $finePercent . '% of book price)';
    }
}

try {
    // Business rule: Issuing reduces available copies by one, and returning increases them by one.
    // Use a database transaction so the loan record and the copy count change together or not at all.
    $pdo->beginTransaction();

    // 1. Mark loan as returned
    //    Only touches a loan that is still open, so a double-submit can never
    //    increase the available copies twice.
    $updateLoan = $pdo->prepare(
        'UPDATE loans SET date_returned = ? WHERE loan_id = ? AND date_returned IS NULL'
    );
    $updateLoan->execute([$returnDate, $id]);
    if ($updateLoan->rowCount() !== 1) {
        throw new RuntimeException('this loan has already been returned.');
    }

    // 2. Increase available copies by 1 (capped at total_copies)
    $updateBook = $pdo->prepare(
        'UPDATE books SET available_copies = LEAST(total_copies, available_copies + 1) WHERE book_id = ?'
    );
    $updateBook->execute([$loan['book_id']]);

    $pdo->commit();

    setFlash('success', 'Book "' . $loan['book_title'] . '" returned successfully by ' . $loan['member_name'] . '.' . $fineMessage);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Failed to process book return: ' . $e->getMessage());
}

redirect(BASE_URL . '/loans/index.php');
