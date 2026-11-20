<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Invalid member ID.');
    redirect(BASE_URL . '/members/index.php');
}

// Fetch member
$stmt = $pdo->prepare('SELECT id, full_name, member_number FROM members WHERE id = ?');
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    setFlash('danger', 'Member not found.');
    redirect(BASE_URL . '/members/index.php');
}

// Check business rule: A member with unreturned books cannot be deleted
$activeLoanStmt = $pdo->prepare('SELECT COUNT(*) FROM loans WHERE member_id = ? AND date_returned IS NULL');
$activeLoanStmt->execute([$id]);
$activeLoans = $activeLoanStmt->fetchColumn();

if ($activeLoans > 0) {
    setFlash('danger', 'Cannot delete "' . e($member['full_name']) . '" (' . e($member['member_number']) . '): member currently has ' . $activeLoans . ' unreturned book(s). Please return all books before deleting.');
    redirect(BASE_URL . '/members/index.php');
}

try {
    $pdo->beginTransaction();

    // Detach returned loan records to prevent foreign key constraint failure
    $detachStmt = $pdo->prepare('UPDATE loans SET member_id = NULL WHERE member_id = ? AND date_returned IS NOT NULL');
    $detachStmt->execute([$id]);

    // Delete member
    $delStmt = $pdo->prepare('DELETE FROM members WHERE id = ?');
    $delStmt->execute([$id]);

    $pdo->commit();
    setFlash('success', 'Member "' . e($member['full_name']) . '" was successfully deleted.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Could not delete member due to a database constraint. Error: ' . e($e->getMessage()));
}

redirect(BASE_URL . '/members/index.php');
