<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Issue Book';
$errors = [];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = intval($_POST['member_id'] ?? 0);
    $book_id   = intval($_POST['book_id'] ?? 0);

    if ($member_id <= 0) {
        $errors[] = 'Please select a member.';
    }
    if ($book_id <= 0) {
        $errors[] = 'Please select a book.';
    }

    if (empty($errors)) {
        // 1. Validate Member
        $mStmt = $pdo->prepare('SELECT id, member_number, full_name, status FROM members WHERE id = ?');
        $mStmt->execute([$member_id]);
        $member = $mStmt->fetch();

        if (!$member) {
            $errors[] = 'Selected member does not exist.';
        } elseif ($member['status'] === 'suspended') {
            // Business rule: A suspended member cannot borrow books.
            $errors[] = 'Rule Violation: Member "' . e($member['full_name']) . '" is currently suspended and cannot borrow books.';
        }

        // 2. Validate Book
        $bStmt = $pdo->prepare('SELECT id, isbn, title, available_copies FROM books WHERE id = ?');
        $bStmt->execute([$book_id]);
        $book = $bStmt->fetch();

        if (!$book) {
            $errors[] = 'Selected book does not exist.';
        } elseif ($book['available_copies'] <= 0) {
            // Business rule: A book with zero available copies cannot be issued.
            $errors[] = 'Rule Violation: "' . e($book['title']) . '" has zero available copies in stock.';
        }

        // 3. Business rule: A member cannot borrow the same book twice at the same time.
        if (empty($errors)) {
            $dupStmt = $pdo->prepare('SELECT COUNT(*) FROM loans WHERE member_id = ? AND book_id = ? AND date_returned IS NULL');
            $dupStmt->execute([$member_id, $book_id]);
            if ($dupStmt->fetchColumn() > 0) {
                $errors[] = 'Rule Violation: Member "' . e($member['full_name']) . '" already has an active (unreturned) loan for "' . e($book['title']) . '".';
            }
        }

        // 4. Issue the book inside a Database Transaction
        if (empty($errors)) {
            $dateIssued = date('Y-m-d');
            $dueDate    = date('Y-m-d', strtotime('+14 days')); // Business rule: Due date is set automatically (14 days)

            try {
                $pdo->beginTransaction();

                // Re-check available copies with row lock (FOR UPDATE)
                $lockStmt = $pdo->prepare('SELECT available_copies FROM books WHERE id = ? FOR UPDATE');
                $lockStmt->execute([$book_id]);
                $currentAvail = (int)$lockStmt->fetchColumn();

                if ($currentAvail <= 0) {
                    throw new Exception('No copies available for issue at this moment.');
                }

                // Insert loan record
                $loanStmt = $pdo->prepare(
                    'INSERT INTO loans (member_id, book_id, date_issued, due_date, status) VALUES (?, ?, ?, ?, "active")'
                );
                $loanStmt->execute([$member_id, $book_id, $dateIssued, $dueDate]);

                // Reduce available copies by 1
                $updStmt = $pdo->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE id = ?');
                $updStmt->execute([$book_id]);

                $pdo->commit();

                setFlash('success', 'Book "' . e($book['title']) . '" successfully issued to ' . e($member['full_name']) . '! Due date: ' . $dueDate . ' (14 days).');
                redirect(BASE_URL . '/loans/index.php');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Transaction failed: ' . $e->getMessage();
            }
        }
    }
}

// Fetch active members
$members = $pdo->query('SELECT id, member_number, full_name FROM members WHERE status = "active" ORDER BY full_name ASC')->fetchAll();

// Fetch books with available copies > 0
$books = $pdo->query('SELECT id, isbn, title, available_copies, total_copies FROM books WHERE available_copies > 0 ORDER BY title ASC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="flex-fill py-4">
<div class="container" style="max-width: 700px;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0"><i class="bi bi-box-arrow-up-right me-2"></i>Issue Book</h2>
    <a href="<?= BASE_URL ?>/loans/index.php" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back to Loans
    </a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <h6 class="alert-heading fw-bold"><i class="bi bi-exclamation-octagon-fill me-1"></i>Cannot Issue Book:</h6>
      <ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?>
          <li><?= $err ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white py-3">
      <h5 class="card-title mb-0"><i class="bi bi-bookmark-plus me-2"></i>Loan Details</h5>
    </div>
    <div class="card-body p-4">
      <form method="POST" action="">
        <!-- Member Selection -->
        <div class="mb-3">
          <label for="member_id" class="form-label fw-semibold">
            Member <span class="text-danger">*</span>
          </label>
          <select name="member_id" id="member_id" class="form-select" required>
            <option value="">-- Choose an Active Member --</option>
            <?php foreach ($members as $m): ?>
              <option value="<?= $m['id'] ?>" <?= (isset($_POST['member_id']) && $_POST['member_id'] == $m['id']) ? 'selected' : '' ?>>
                <?= e($m['member_number']) ?> - <?= e($m['full_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text text-muted">Only active members can borrow books. Suspended members are excluded.</div>
        </div>

        <!-- Book Selection -->
        <div class="mb-3">
          <label for="book_id" class="form-label fw-semibold">
            Book <span class="text-danger">*</span>
          </label>
          <select name="book_id" id="book_id" class="form-select" required>
            <option value="">-- Choose an Available Book --</option>
            <?php foreach ($books as $b): ?>
              <option value="<?= $b['id'] ?>" <?= (isset($_POST['book_id']) && $_POST['book_id'] == $b['id']) ? 'selected' : '' ?>>
                <?= e($b['title']) ?> (ISBN: <?= e($b['isbn']) ?>) &mdash; <?= $b['available_copies'] ?>/<?= $b['total_copies'] ?> copies available
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text text-muted">Only books with at least 1 copy available are shown.</div>
        </div>

        <!-- Loan Period Information Card -->
        <div class="card bg-light border-0 mb-4">
          <div class="card-body">
            <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-info-circle me-1"></i>Automatic Loan Rules</h6>
            <div class="row g-2 small text-muted">
              <div class="col-sm-6">
                <strong>Issue Date:</strong> <?= date('Y-m-d') ?> (Today)
              </div>
              <div class="col-sm-6">
                <strong>Due Date:</strong> <?= date('Y-m-d', strtotime('+14 days')) ?> (Auto: +14 days)
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="<?= BASE_URL ?>/loans/index.php" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-1"></i>Issue Book
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
