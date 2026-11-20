<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Dashboard';

// Greeting based on server hour and first name
$hour = (int)date('G');
$greetingTime = ($hour < 12) ? 'Good morning' : (($hour < 17) ? 'Good afternoon' : 'Good evening');
$fullName = $_SESSION['user_name'] ?? 'Librarian';
$firstName = explode(' ', trim($fullName))[0];

// Key Statistics (Aligned with new ERD)
$totalBooks         = (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$totalCopiesOnShelf = (int)$pdo->query('SELECT COALESCE(SUM(available_copies), 0) FROM books')->fetchColumn();
$totalMembers       = (int)$pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$activeMembers      = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE status = 'active'")->fetchColumn();
$suspendedMembers   = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE status = 'suspended'")->fetchColumn();
$activeLoans        = (int)$pdo->query("SELECT COUNT(*) FROM loans WHERE date_returned IS NULL")->fetchColumn();
$todayStr           = date('Y-m-d');
$nextWeekStr        = date('Y-m-d', strtotime('+7 days'));
$stmtDue            = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE date_returned IS NULL AND due_date BETWEEN ? AND ?");
$stmtDue->execute([$todayStr, $nextWeekStr]);
$dueThisWeek        = (int)$stmtDue->fetchColumn();
$overdueLoans       = (int)$pdo->query("SELECT COUNT(*) FROM loans WHERE date_returned IS NULL AND due_date < CURDATE()")->fetchColumn();
$oldestOverdueDays  = (int)$pdo->query("SELECT COALESCE(MAX(DATEDIFF(CURDATE(), due_date)), 0) FROM loans WHERE date_returned IS NULL AND due_date < CURDATE()")->fetchColumn();

// Needs Attention: Overdue List (limit 5, using updated ERD keys)
$overdueList = $pdo->query(
    "SELECT l.loan_id AS id, b.title, sc.category_id, m.full_name, m.phone, l.due_date, DATEDIFF(CURDATE(), l.due_date) AS days_overdue
     FROM loans l
     JOIN books b ON l.book_id = b.book_id
     LEFT JOIN sub_categories sc ON b.sub_category_id = sc.sub_category_id
     JOIN members m ON l.member_id = m.member_id
     WHERE l.date_returned IS NULL AND l.due_date < CURDATE()
     ORDER BY days_overdue DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// Recent Loans (limit 5, using updated ERD keys)
$recentLoans = $pdo->query(
    "SELECT l.loan_id AS id, b.title, sc.category_id, m.full_name, l.date_issued, l.due_date, l.date_returned
     FROM loans l
     JOIN books b ON l.book_id = b.book_id
     LEFT JOIN sub_categories sc ON b.sub_category_id = sc.sub_category_id
     JOIN members m ON l.member_id = m.member_id
     ORDER BY l.loan_id DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// Loans by Category (using updated sub_categories join from ERD)
$catStats = $pdo->query(
    "SELECT c.category_id AS id, c.name, COUNT(l.loan_id) AS loan_count
     FROM categories c
     LEFT JOIN sub_categories sc ON sc.category_id = c.category_id
     LEFT JOIN books b ON b.sub_category_id = sc.sub_category_id
     LEFT JOIN loans l ON l.book_id = b.book_id
     GROUP BY c.category_id, c.name
     ORDER BY loan_count DESC LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

$maxLoanCount = 1;
foreach ($catStats as $cs) {
    if ($cs['loan_count'] > $maxLoanCount) {
        $maxLoanCount = (int)$cs['loan_count'];
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Dashboard Header (Greeting + Date line + Quick Action) -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="lms-title mb-1"><?= e($greetingTime) ?>, <?= e($firstName) ?></h1>
        <p class="text-muted mb-0 small">
            <i class="bi bi-calendar3 me-1"></i><?= date('l, j F Y') ?>
        </p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/loans/issue.php" class="btn btn-primary">
            <i class="bi bi-box-arrow-up-right me-1"></i>Issue book
        </a>
    </div>
</div>

<!-- Four Stat Tiles with Elevated Metric Icons -->
<div class="lms-stat-grid">
    <!-- 1. Books -->
    <a href="<?= BASE_URL ?>/books/index.php" class="lms-stat-tile">
        <div class="lms-stat-tile__header">
            <span class="lms-stat-tile__label">Books</span>
            <div class="lms-stat-icon-wrap" style="background-color: var(--lms-primary-subtle); color: var(--lms-primary);">
                <i class="bi bi-book"></i>
            </div>
        </div>
        <span class="lms-stat-tile__num"><?= $totalBooks ?></span>
        <span class="lms-stat-tile__sub"><?= $totalCopiesOnShelf ?> copies on the shelf</span>
    </a>

    <!-- 2. Members -->
    <a href="<?= BASE_URL ?>/members/index.php" class="lms-stat-tile">
        <div class="lms-stat-tile__header">
            <span class="lms-stat-tile__label">Members</span>
            <div class="lms-stat-icon-wrap" style="background-color: var(--lms-success-bg); color: var(--lms-success);">
                <i class="bi bi-people"></i>
            </div>
        </div>
        <span class="lms-stat-tile__num"><?= $totalMembers ?></span>
        <span class="lms-stat-tile__sub"><?= $activeMembers ?> active, <?= $suspendedMembers ?> suspended</span>
    </a>

    <!-- 3. On Loan -->
    <a href="<?= BASE_URL ?>/loans/index.php?filter=active" class="lms-stat-tile">
        <div class="lms-stat-tile__header">
            <span class="lms-stat-tile__label">On loan</span>
            <div class="lms-stat-icon-wrap" style="background-color: var(--lms-warning-bg); color: var(--lms-warning);">
                <i class="bi bi-arrow-left-right"></i>
            </div>
        </div>
        <span class="lms-stat-tile__num"><?= $activeLoans ?></span>
        <span class="lms-stat-tile__sub"><?= $dueThisWeek ?> due this week</span>
    </a>

    <!-- 4. Overdue -->
    <a href="<?= BASE_URL ?>/loans/index.php?filter=overdue" class="lms-stat-tile <?= $overdueLoans > 0 ? 'lms-stat-tile--danger-active' : '' ?>">
        <div class="lms-stat-tile__header">
            <span class="lms-stat-tile__label">Overdue</span>
            <div class="lms-stat-icon-wrap" style="background-color: var(--lms-danger-bg); color: var(--lms-danger);">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
        </div>
        <span class="lms-stat-tile__num"><?= $overdueLoans ?></span>
        <span class="lms-stat-tile__sub">
            <?= $overdueLoans > 0 ? 'Oldest is ' . $oldestOverdueDays . ' days late' : 'All clear' ?>
        </span>
    </a>
</div>

<!-- Main Dashboard Grid -->
<div class="row g-4">
    <!-- Left Column: Needs Attention & Recent Loans (8 of 12) -->
    <div class="col-lg-8">
        <!-- Needs Attention -->
        <div class="card lms-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h2 class="lms-section-title mb-0">Needs attention</h2>
                    <?php if ($overdueLoans > 0): ?>
                        <span class="lms-pill lms-pill--danger"><?= $overdueLoans ?> overdue</span>
                    <?php endif; ?>
                </div>
                <a href="<?= BASE_URL ?>/loans/index.php?filter=overdue" class="text-decoration-none small">
                    See all overdue loans
                </a>
            </div>

            <div class="card-body p-0">
                <?php if (empty($overdueList)): ?>
                    <div class="lms-empty-state py-4">
                        <i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>
                        <h3 class="lms-empty-state__title h6">Nothing is overdue</h3>
                        <p class="lms-empty-state__help mb-0 small">Every book is back or still within its due date.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="lms-table lms-table-stacked align-middle">
                            <tbody>
                            <?php foreach ($overdueList as $item): ?>
                                <tr>
                                    <!-- Spine & Title -->
                                    <td class="lms-td-primary" data-label="Book">
                                        <div class="d-flex align-items-center gap-3">
                                            <?= spine_tile($item['title'], $item['category_id'] ?? 0, 'sm') ?>
                                            <div class="min-w-0">
                                                <div class="fw-semibold text-truncate"><?= e($item['title']) ?></div>
                                                <div class="text-muted small"><?= e($item['full_name']) ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Due date & Late info -->
                                    <td data-label="Due" class="text-muted small">
                                        <div>Due <?= format_date($item['due_date']) ?></div>
                                        <div class="text-danger fw-semibold"><?= (int)$item['days_overdue'] ?> days overdue</div>
                                    </td>

                                    <!-- Action Buttons -->
                                    <td data-label="Action" class="lms-td-actions text-end">
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <?php if (!empty($item['phone'])): ?>
                                                <a href="tel:<?= e($item['phone']) ?>" class="lms-btn-icon" title="Call member (<?= e($item['phone']) ?>)" aria-label="Call <?= e($item['full_name']) ?>">
                                                    <i class="bi bi-telephone"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= BASE_URL ?>/loans/return.php?id=<?= $item['id'] ?>"
                                               class="btn btn-sm btn-secondary"
                                               data-confirm="Return '<?= e($item['title']) ?>' for <?= e($item['full_name']) ?>?"
                                               data-confirm-title="Mark overdue loan as returned?"
                                               data-confirm-message="Return &quot;<?= e($item['title']) ?>&quot; from <?= e($item['full_name']) ?>. This book is <?= (int)$item['days_overdue'] ?> days overdue."
                                               data-confirm-button="Mark as returned"
                                               data-confirm-style="primary"
                                               onclick="return confirm('Return this book?');">
                                                Return
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Loans -->
        <div class="card lms-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="lms-section-title mb-0">Recent loans</h2>
                <a href="<?= BASE_URL ?>/loans/index.php" class="text-decoration-none small">See all loans</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="lms-table lms-table-stacked align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Book</th>
                                <th scope="col">Member</th>
                                <th scope="col">Issued</th>
                                <th scope="col">Due</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentLoans)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No circulation activity recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentLoans as $loan): ?>
                                <tr>
                                    <td class="lms-td-primary" data-label="Book">
                                        <div class="d-flex align-items-center gap-2">
                                            <?= spine_tile($loan['title'], $loan['category_id'] ?? 0, 'sm') ?>
                                            <span class="fw-medium text-truncate" style="max-width: 180px;" title="<?= e($loan['title']) ?>">
                                                <?= e($loan['title']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td data-label="Member"><?= e($loan['full_name']) ?></td>
                                    <td data-label="Issued" class="lms-tabular text-muted"><?= format_date($loan['date_issued']) ?></td>
                                    <td data-label="Due" class="lms-tabular text-muted"><?= format_date($loan['due_date']) ?></td>
                                    <td data-label="Status">
                                        <?php
                                        $s = loan_status($loan);
                                        if ($s === 'returned') echo status_pill('Returned', 'neutral');
                                        elseif ($s === 'overdue') echo status_pill('Overdue', 'danger');
                                        elseif ($s === 'due_soon') echo status_pill('Due soon', 'warning');
                                        else echo status_pill('On loan', 'info');
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Loans by Category & Quick Actions (4 of 12) -->
    <div class="col-lg-4">
        <!-- Loans by Category (CSS Bars) -->
        <div class="card lms-card mb-4">
            <div class="card-header">
                <h2 class="lms-section-title mb-0">Loans by category</h2>
            </div>
            <div class="card-body">
                <?php if (empty($catStats)): ?>
                    <p class="text-muted small mb-0">No category loan data available.</p>
                <?php else: ?>
                    <div class="lms-category-bar-list">
                        <?php
                        $palette = ['#3F5BA9', '#2E7D6B', '#8C4A6B', '#A85A17', '#5A6B3A', '#5E548E'];
                        foreach ($catStats as $cs):
                            $catColor = $palette[abs((int)$cs['id']) % 6];
                            $pct = ($maxLoanCount > 0) ? round(($cs['loan_count'] / $maxLoanCount) * 100) : 0;
                        ?>
                            <div class="lms-category-bar-item">
                                <div class="lms-category-bar-label">
                                    <span class="d-inline-flex align-items-center">
                                        <span class="lms-category-dot" style="background-color: <?= $catColor ?>;"></span>
                                        <span class="fw-medium"><?= e($cs['name']) ?></span>
                                    </span>
                                    <span class="lms-tabular text-muted fw-semibold"><?= (int)$cs['loan_count'] ?></span>
                                </div>
                                <div class="lms-category-bar-track">
                                    <div class="lms-category-bar-fill" style="width: <?= max(4, $pct) ?>%; background-color: <?= $catColor ?>;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions with Iconic Elevation -->
        <div class="card lms-card">
            <div class="card-header">
                <h2 class="lms-section-title mb-0">Quick actions</h2>
            </div>
            <div class="card-body d-flex flex-column gap-2 p-3">
                <a href="<?= BASE_URL ?>/loans/issue.php" class="lms-quick-action">
                    <span class="lms-quick-action__icon">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </span>
                    <span>Issue a book</span>
                </a>
                <a href="<?= BASE_URL ?>/books/create.php" class="lms-quick-action">
                    <span class="lms-quick-action__icon">
                        <i class="bi bi-book"></i>
                    </span>
                    <span>Add a book</span>
                </a>
                <a href="<?= BASE_URL ?>/members/create.php" class="lms-quick-action">
                    <span class="lms-quick-action__icon">
                        <i class="bi bi-person-plus"></i>
                    </span>
                    <span>Add a member</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
