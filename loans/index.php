<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Loans';
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? '';

// Whitelist filter values
$validFilters = ['active', 'returned', 'overdue', ''];
if (!in_array($filter, $validFilters)) {
    $filter = '';
}

// Fetch tab counts
$countAll      = (int)$pdo->query("SELECT COUNT(*) FROM loans")->fetchColumn();
$countActive   = (int)$pdo->query("SELECT COUNT(*) FROM loans WHERE date_returned IS NULL AND due_date >= CURDATE()")->fetchColumn();
$countOverdue  = (int)$pdo->query("SELECT COUNT(*) FROM loans WHERE date_returned IS NULL AND due_date < CURDATE()")->fetchColumn();
$countReturned = (int)$pdo->query("SELECT COUNT(*) FROM loans WHERE date_returned IS NOT NULL")->fetchColumn();
$totalOnLoan   = $countActive + $countOverdue;

$params = [];

$sql = "SELECT l.loan_id AS id,
               b.title AS book_title,
               b.book_id,
               b.sub_category_id,
               m.full_name AS member_name,
               m.member_no AS member_number,
               l.date_issued,
               l.due_date,
               l.date_returned,
               CASE
                   WHEN l.date_returned IS NOT NULL THEN 'returned'
                   WHEN l.due_date < CURDATE() THEN 'overdue'
                   ELSE 'active'
               END AS computed_status
        FROM loans l
        JOIN books b ON l.book_id = b.book_id
        JOIN members m ON l.member_id = m.member_id
        WHERE 1=1";

if ($search !== '') {
    $sql .= " AND (m.full_name LIKE ? OR b.title LIKE ? OR m.member_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter === 'returned') {
    $sql .= " AND l.date_returned IS NOT NULL";
} elseif ($filter === 'active') {
    $sql .= " AND l.date_returned IS NULL AND l.due_date >= CURDATE()";
} elseif ($filter === 'overdue') {
    $sql .= " AND l.date_returned IS NULL AND l.due_date < CURDATE()";
}

$sql .= " ORDER BY l.loan_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$loans = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Header (Section 6.1 & 7.5) -->
<?= page_header(
    'Loans',
    $totalOnLoan . ' on loan, ' . $countOverdue . ' overdue',
    '<a href="' . BASE_URL . '/loans/export.php" class="btn btn-secondary">' .
        '<i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV' .
    '</a>' .
    '<a href="' . BASE_URL . '/loans/issue.php" class="btn btn-primary">' .
        '<i class="bi bi-box-arrow-up-right me-1"></i>Issue book' .
    '</a>'
) ?>

<?php displayFlash(); ?>

<!-- Table & Filter Card -->
<div class="lms-table-card mb-4">
    <!-- Status Tabs & Filter Bar (Section 7.5) -->
    <div class="lms-filter-bar border-bottom">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 w-100">
            <!-- Status Tabs -->
            <ul class="nav nav-pills lms-status-tabs gap-1" role="tablist">
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/loans/index.php?search=<?= urlencode($search) ?>"
                       class="nav-link <?= $filter === '' ? 'active' : '' ?>">
                        All <span class="badge rounded-pill bg-light text-dark ms-1"><?= $countAll ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/loans/index.php?filter=active&search=<?= urlencode($search) ?>"
                       class="nav-link <?= $filter === 'active' ? 'active' : '' ?>">
                        On loan <span class="badge rounded-pill bg-info text-white ms-1"><?= $countActive ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/loans/index.php?filter=overdue&search=<?= urlencode($search) ?>"
                       class="nav-link <?= $filter === 'overdue' ? 'active' : '' ?>">
                        Overdue <span class="badge rounded-pill bg-danger text-white ms-1"><?= $countOverdue ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/loans/index.php?filter=returned&search=<?= urlencode($search) ?>"
                       class="nav-link <?= $filter === 'returned' ? 'active' : '' ?>">
                        Returned <span class="badge rounded-pill bg-secondary text-white ms-1"><?= $countReturned ?></span>
                    </a>
                </li>
            </ul>

            <!-- Search Form -->
            <form method="GET" action="<?= BASE_URL ?>/loans/index.php" class="d-flex align-items-center gap-2">
                <input type="hidden" name="filter" value="<?= e($filter) ?>">
                <div class="lms-filter-input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="search" name="search" class="form-control"
                           placeholder="Search member, book..."
                           value="<?= e($search) ?>" aria-label="Search loans">
                </div>
                <noscript>
                    <button type="submit" class="btn btn-secondary btn-sm">Search</button>
                </noscript>
                <?php if ($search !== '' || $filter !== ''): ?>
                    <a href="<?= BASE_URL ?>/loans/index.php" class="btn btn-outline-secondary btn-sm" title="Clear search">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Data Table (Section 6.4 & 7.5) -->
    <div class="table-responsive">
        <table class="lms-table lms-table-stacked align-middle mb-0">
            <caption class="visually-hidden">List of active, overdue, and returned library loans</caption>
            <thead>
                <tr>
                    <th scope="col" style="min-width: 250px;">Book</th>
                    <th scope="col" style="min-width: 200px;">Member</th>
                    <th scope="col" style="width: 140px;">Issued</th>
                    <th scope="col" style="width: 180px;">Due</th>
                    <th scope="col" style="width: 140px;">Returned</th>
                    <th scope="col" style="width: 130px;">Status</th>
                    <th scope="col" class="text-end" style="width: 130px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($loans)): ?>
                <tr>
                    <td colspan="7" class="p-0 border-0">
                        <div class="lms-empty-state">
                            <div class="lms-empty-state__icon">
                                <i class="bi bi-arrow-left-right fs-1 text-muted"></i>
                            </div>
                            <?php if ($search !== '' || $filter !== ''): ?>
                                <h2 class="lms-empty-state__title">No loans match your search</h2>
                                <p class="lms-empty-state__description">Try clearing the search or status filter to see other loan records.</p>
                                <a href="<?= BASE_URL ?>/loans/index.php" class="btn btn-secondary">Clear filters</a>
                            <?php else: ?>
                                <h2 class="lms-empty-state__title">No loans recorded yet</h2>
                                <p class="lms-empty-state__description">Issue books to library members to track circulation and due dates.</p>
                                <a href="<?= BASE_URL ?>/loans/issue.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i>Issue first book
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($loans as $loan): ?>
                    <?php
                        $status = $loan['computed_status'];
                        $isOverdue = ($status === 'overdue');
                        $isReturned = ($status === 'returned');

                        // Pill style
                        if ($isReturned) {
                            $pillClass = 'lms-pill--neutral';
                            $statusLabel = 'Returned';
                        } elseif ($isOverdue) {
                            $pillClass = 'lms-pill--danger';
                            $statusLabel = 'Overdue';
                        } else {
                            $daysLeft = (int)(new DateTime('today'))->diff(new DateTime($loan['due_date']))->format('%r%a');
                            if ($daysLeft <= 3) {
                                $pillClass = 'lms-pill--warning';
                                $statusLabel = 'Due soon';
                            } else {
                                $pillClass = 'lms-pill--info';
                                $statusLabel = 'On loan';
                            }
                        }
                    ?>
                    <tr class="<?= $isOverdue ? 'lms-row-overdue' : '' ?>">
                        <td data-label="Book">
                            <div class="d-flex align-items-center gap-3">
                                <?= spine_tile($loan['book_title'], $loan['sub_category_id'] ?? 1, 'sm') ?>
                                <span class="fw-semibold text-dark"><?= e($loan['book_title']) ?></span>
                            </div>
                        </td>
                        <td data-label="Member">
                            <div class="fw-semibold text-dark"><?= e($loan['member_name']) ?></div>
                            <div class="text-muted small"><?= e($loan['member_number']) ?></div>
                        </td>
                        <td data-label="Issued" class="text-secondary">
                            <?= format_date($loan['date_issued']) ?>
                        </td>
                        <td data-label="Due">
                            <div class="fw-medium text-dark"><?= format_date($loan['due_date']) ?></div>
                            <div class="small"><?= relative_due($loan['due_date'], $loan['date_returned']) ?></div>
                        </td>
                        <td data-label="Returned" class="text-secondary">
                            <?= $loan['date_returned'] ? format_date($loan['date_returned']) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td data-label="Status">
                            <span class="lms-pill <?= $pillClass ?>">
                                <?= $statusLabel ?>
                            </span>
                        </td>
                        <td data-label="Action" class="text-end">
                            <?php if (!$isReturned): ?>
                                <a href="<?= BASE_URL ?>/loans/return.php?id=<?= (int)$loan['id'] ?>"
                                   class="btn btn-sm btn-outline-success lms-btn-action"
                                   data-confirm
                                   data-confirm-title="Mark Book as Returned"
                                   data-confirm-message="Return '<?= e($loan['book_title']) ?>' borrowed by <?= e($loan['member_name']) ?>?<?= $isOverdue ? ' (Note: This loan is currently overdue).' : '' ?>"
                                   data-confirm-button="Mark as returned"
                                   data-confirm-style="primary"
                                   title="Mark book as returned">
                                    <i class="bi bi-check-circle me-1"></i>Return
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer (Section 8 pluralization) -->
    <div class="card-footer d-flex justify-content-between align-items-center py-3">
        <span class="text-muted small">
            <?php if ($search !== '' || $filter !== ''): ?>
                Showing <?= count($loans) ?> of <?= $countAll ?> loans
            <?php else: ?>
                <?= pluralize(count($loans), 'loan', 'loans') ?>
            <?php endif; ?>
        </span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
