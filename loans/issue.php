<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Issue Book';
$errors = [];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = intval($_POST['member_id'] ?? 0);
    $book_id = intval($_POST['book_id'] ?? 0);

    if ($member_id <= 0) {
        $errors[] = 'Please select a member.';
    }
    if ($book_id <= 0) {
        $errors[] = 'Please select a book.';
    }

    if (empty($errors)) {
        // 1. Validate Member
        $mStmt = $pdo->prepare('SELECT member_id AS id, member_id, member_no AS member_number, full_name, status FROM members WHERE member_id = ?');
        $mStmt->execute([$member_id]);
        $member = $mStmt->fetch();

        if (!$member) {
            $errors[] = 'Selected member does not exist.';
        } elseif ($member['status'] === 'suspended') {
            // Business rule: A suspended member cannot borrow books.
            $errors[] = 'Rule Violation: Member "' . $member['full_name'] . '" is currently suspended and cannot borrow books.';
        }

        // 2. Validate Book
        $bStmt = $pdo->prepare('SELECT book_id AS id, book_id, isbn, title, available_copies FROM books WHERE book_id = ?');
        $bStmt->execute([$book_id]);
        $book = $bStmt->fetch();

        if (!$book) {
            $errors[] = 'Selected book does not exist.';
        } elseif ($book['available_copies'] <= 0) {
            // Business rule: A book with zero available copies cannot be issued.
            $errors[] = 'Rule Violation: "' . $book['title'] . '" has zero available copies in stock.';
        }

        // 3. Business rule: Single Active Book Policy (1 Person = 1 Book)
        // A member can only borrow one book and cannot borrow again until the previous book is returned (cleared loan).
        if (empty($errors)) {
            $activeStmt = $pdo->prepare(
                'SELECT l.loan_id AS id, b.title 
                 FROM loans l 
                 JOIN books b ON l.book_id = b.book_id 
                 WHERE l.member_id = ? AND l.date_returned IS NULL 
                 LIMIT 1'
            );
            $activeStmt->execute([$member_id]);
            $existingLoan = $activeStmt->fetch();
            if ($existingLoan) {
                $errors[] = 'Rule Violation: Member "' . $member['full_name'] . '" already has an unreturned book ("' . $existingLoan['title'] . '"). A member is only permitted to borrow 1 book at a time and cannot borrow another until the previous book is returned and cleared.';
            }
        }

        // 4. Issue the book inside a Database Transaction
        if (empty($errors)) {
            $dateIssued = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+14 days')); // Business rule: Due date is set automatically (14 days)

            try {
                $pdo->beginTransaction();

                // Reduce available copies by 1 - atomically, and only while stock remains,
                // so two simultaneous issues can never push the count below zero.
                $updStmt = $pdo->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ? AND available_copies > 0');
                $updStmt->execute([$book_id]);
                if ($updStmt->rowCount() !== 1) {
                    throw new Exception('No copies available for issue at this moment.');
                }

                // Insert loan record
                $issuedBy = $_SESSION['user_id'] ?? 1;
                $loanStmt = $pdo->prepare(
                    'INSERT INTO loans (member_id, book_id, issued_by, date_issued, due_date) VALUES (?, ?, ?, ?, ?)'
                );
                $loanStmt->execute([$member_id, $book_id, $issuedBy, $dateIssued, $dueDate]);

                $pdo->commit();

                setFlash('success', 'Book "' . $book['title'] . '" successfully issued to ' . $member['full_name'] . '! Due date: ' . format_date($dueDate) . ' (14 days).');
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

// Fetch all members with their active loan counts and status
$members = $pdo->query('
    SELECT m.member_id AS id, m.member_no AS member_number, m.full_name, m.status,
           (SELECT COUNT(*) FROM loans l WHERE l.member_id = m.member_id AND l.date_returned IS NULL) AS active_loans
    FROM members m 
    ORDER BY m.full_name ASC
')->fetchAll();

// Fetch all books for selection
$books = $pdo->query('SELECT book_id AS id, isbn, title, available_copies, total_copies, sub_category_id FROM books ORDER BY title ASC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Header (Section 6.1 & 7.5) -->
<?= page_header(
    'Issue book',
    'Assign a book loan to a registered member with automated 14-day return period',
    '<a href="' . BASE_URL . '/loans/index.php" class="btn btn-secondary">' .
    '<i class="bi bi-arrow-left me-1"></i>Back to Loans' .
    '</a>'
) ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-exclamation-octagon-fill fs-5 text-danger"></i>
            <h6 class="mb-0 fw-bold">Cannot issue book:</h6>
        </div>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form method="POST" action="" class="lms-issue-form" novalidate>
    <?= csrf_field() ?>
    <div class="row g-4">
        <!-- Left Column: Selection Fields (Section 7.5) -->
        <div class="col-lg-8">
            <div class="card lms-card mb-4">
                <div class="card-header">
                    <h2 class="lms-section-title">Loan details</h2>
                </div>
                <div class="card-body p-4">
                    <!-- Member Selection -->
                    <div class="mb-4">
                        <label for="member_id" class="form-label">
                            Member <span class="text-danger">*</span>
                        </label>
                        <select name="member_id" id="member_id" class="form-select" required>
                            <option value="">Choose a member…</option>
                            <?php foreach ($members as $m): ?>
                                <?php
                                $hasLoan = ($m['active_loans'] > 0);
                                $isSusp = ($m['status'] === 'suspended');
                                $hint = '';
                                if ($isSusp) {
                                    $hint = ' (suspended)';
                                } elseif ($hasLoan) {
                                    $hint = ' (already has 1 active loan)';
                                } else {
                                    $hint = ' (eligible)';
                                }
                                $isSelected = (isset($_POST['member_id']) && (int) $_POST['member_id'] === (int) $m['id']);
                                ?>
                                <option value="<?= $m['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                    <?= e($m['full_name']) ?> (<?= e($m['member_number']) ?>)<?= $hint ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Each member can borrow one book at a time and must return it before borrowing another.
                        </div>
                    </div>

                    <!-- Book Selection -->
                    <div class="mb-3">
                        <label for="book_id" class="form-label">
                            Book <span class="text-danger">*</span>
                        </label>
                        <select name="book_id" id="book_id" class="form-select" required>
                            <option value="">Choose a book…</option>
                            <?php foreach ($books as $b): ?>
                                <?php
                                $copies = (int) $b['available_copies'];
                                $hint = ($copies > 0) ? " ({$copies} of {$b['total_copies']} available)" : " (none available)";
                                $isSelected = (isset($_POST['book_id']) && (int) $_POST['book_id'] === (int) $b['id']);
                                ?>
                                <option value="<?= $b['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                    <?= e($b['title']) ?> (ISBN: <?= e($b['isbn']) ?>)<?= $hint ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Only books with at least one copy on the shelf can be issued.
                        </div>
                    </div>
                </div>
            </div>

            <div class="lms-form-sticky-bar">
                <a href="<?= BASE_URL ?>/loans/index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Issue book
                </button>
            </div>
        </div>

        <!-- Right Column: Sticky Loan Summary (Section 7.5) -->
        <div class="col-lg-4">
            <div class="card lms-card lms-sticky-summary">
                <div class="card-header">
                    <h2 class="lms-section-title">Loan summary</h2>
                </div>
                <div class="card-body">
                    <div class="lms-summary-row">
                        <div class="lms-eyebrow mb-1">Borrower</div>
                        <div id="summaryMember" class="lms-summary-value">None selected</div>
                    </div>

                    <div class="lms-summary-row">
                        <div class="lms-eyebrow mb-1">Book</div>
                        <div id="summaryBook" class="lms-summary-value">None selected</div>
                    </div>

                    <div class="lms-summary-row d-flex gap-3">
                        <div class="flex-fill">
                            <div class="lms-eyebrow mb-1">Issued</div>
                            <div class="lms-summary-value lms-tabular"><?= date('d M Y') ?></div>
                        </div>
                        <div class="flex-fill">
                            <div class="lms-eyebrow mb-1">Due back</div>
                            <div class="lms-summary-value lms-tabular text-primary"><?= date('d M Y', strtotime('+14 days')) ?></div>
                        </div>
                    </div>

                    <div class="lms-policy-box">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-shield-check text-success fs-5"></i>
                            <div>
                                <strong>Lending rules</strong>
                                <ul>
                                    <li>Loans last 14 days.</li>
                                    <li>One book per member at a time.</li>
                                    <li>Suspended members cannot borrow.</li>
                                    <li>Late returns: 5% of the book price per day.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>