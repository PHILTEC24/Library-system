<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Invalid member ID.');
    redirect(BASE_URL . '/members/index.php');
}

// Fetch member
$stmt = $pdo->prepare('SELECT member_id AS id, member_id, full_name, member_no AS member_number FROM members WHERE member_id = ?');
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    setFlash('danger', 'Member not found.');
    redirect(BASE_URL . '/members/index.php');
}

// Check business rule: Check active loans
$activeLoanStmt = $pdo->prepare('SELECT COUNT(*) FROM loans WHERE member_id = ? AND date_returned IS NULL');
$activeLoanStmt->execute([$id]);
$activeLoans = $activeLoanStmt->fetchColumn();

if ($activeLoans > 0) {
    setFlash('danger', 'Cannot delete "' . e($member['full_name']) . '" (' . e($member['member_number']) . '): member currently has ' . $activeLoans . ' unreturned book(s). Please return all books before deleting.');
    redirect(BASE_URL . '/members/index.php');
}

// Check if any loans exist at all (foreign key constraint)
$loanStmt = $pdo->prepare('SELECT COUNT(*) FROM loans WHERE member_id = ?');
$loanStmt->execute([$id]);
$totalLoans = $loanStmt->fetchColumn();

if ($totalLoans > 0) {
    setFlash('danger', 'Cannot delete "' . e($member['full_name']) . '" (' . e($member['member_number']) . '): member has ' . $totalLoans . ' associated loan record(s). Cannot delete a member with active or historical loans.');
    redirect(BASE_URL . '/members/index.php');
}

try {
    // Delete member
    $delStmt = $pdo->prepare('DELETE FROM members WHERE member_id = ?');
    $delStmt->execute([$id]);

    setFlash('success', 'Member "' . e($member['full_name']) . '" was successfully deleted.');
} catch (PDOException $e) {
    setFlash('danger', 'Could not delete member due to a database constraint. Error: ' . e($e->getMessage()));
}

redirect(BASE_URL . '/members/index.php');
