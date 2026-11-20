<?php
if (!defined('BASE_URL')) define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Members';
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';

// Total counts for subtitle
$totalMembers     = (int)$pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$activeMembers    = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE status = 'active'")->fetchColumn();
$suspendedMembers = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE status = 'suspended'")->fetchColumn();

// Build query
$params = [];
$where  = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(m.full_name LIKE ? OR m.email LIKE ? OR m.member_no LIKE ? OR m.phone LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status === 'active' || $status === 'suspended') {
    $where[] = 'm.status = ?';
    $params[] = $status;
}

$sql = 'SELECT m.member_id AS id, m.member_id, m.member_no, m.member_no AS member_number, m.full_name, m.email, m.phone, m.status, m.date_joined
        FROM members m';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY m.full_name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isFiltered = ($search !== '' || ($status !== 'all' && $status !== ''));

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Header (Section 6.1 & 7.4) -->
<?= page_header(
    'Members',
    $totalMembers . ' members, ' . $activeMembers . ' active, ' . $suspendedMembers . ' suspended',
    '<a href="' . BASE_URL . '/members/export.php" class="btn btn-secondary">' .
        '<i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV' .
    '</a>' .
    '<a href="' . BASE_URL . '/members/create.php" class="btn btn-primary">' .
        '<i class="bi bi-person-plus-fill me-1"></i>Add member' .
    '</a>'
) ?>

<?php displayFlash(); ?>

<!-- Table & Filter Card -->
<div class="lms-table-card mb-4">
    <!-- Filter Bar (Section 6.3 & 7.4) -->
    <div class="lms-filter-bar">
        <form method="GET" action="<?= BASE_URL ?>/members/index.php" class="lms-auto-filter">
            <div class="lms-filter-bar__row">
                <div class="lms-filter-bar__inputs">
                    <div class="lms-filter-input-wrap">
                        <i class="bi bi-search"></i>
                        <input type="search" name="search" class="form-control"
                               placeholder="Search name, email, member number..."
                               value="<?= e($search) ?>" aria-label="Search members">
                    </div>

                    <select name="status" class="form-select lms-filter-select" onchange="this.form.submit()" aria-label="Filter by status">
                        <option value="all" <?= $status === 'all' || $status === '' ? 'selected' : '' ?>>All statuses</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active only</option>
                        <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended only</option>
                    </select>

                    <noscript>
                        <button type="submit" class="btn btn-secondary">Filter</button>
                    </noscript>
                </div>

                <div class="text-muted small">
                    <?php if ($isFiltered): ?>
                        Showing <?= count($members) ?> of <?= $totalMembers ?> members
                    <?php else: ?>
                        <?= pluralize($totalMembers, 'member', 'members') ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isFiltered): ?>
                <div class="lms-filter-chips mt-2 pt-2 border-top border-light-subtle">
                    <span class="text-muted">Active filters:</span>
                    <?php if ($search !== ''): ?>
                        <span class="lms-chip">
                            <span>"<?= e($search) ?>"</span>
                            <a href="<?= BASE_URL ?>/members/index.php?status=<?= urlencode($status) ?>" class="lms-chip__remove" aria-label="Remove search filter">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($status === 'active' || $status === 'suspended'): ?>
                        <span class="lms-chip">
                            <span>Status: <?= ucfirst($status) ?></span>
                            <a href="<?= BASE_URL ?>/members/index.php?search=<?= urlencode($search) ?>&status=all" class="lms-chip__remove" aria-label="Remove status filter">&times;</a>
                        </span>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/members/index.php" class="text-decoration-none small ms-2 text-danger">Clear filters</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Data Table (Section 6.4 & 7.4) -->
    <div class="table-responsive">
        <table class="lms-table lms-table-stacked align-middle mb-0">
            <caption class="visually-hidden">Registered library members list with contact info and borrowing status</caption>
            <thead>
                <tr>
                    <th scope="col" style="min-width: 260px;">Member</th>
                    <th scope="col" style="min-width: 220px;">Contact</th>
                    <th scope="col" style="width: 130px;">Status</th>
                    <th scope="col" class="d-none d-lg-table-cell" style="width: 140px;">Joined</th>
                    <th scope="col" class="text-end" style="width: 110px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($members)): ?>
                <tr>
                    <td colspan="5" class="p-0 border-0">
                        <div class="lms-empty-state">
                            <div class="lms-empty-state__icon">
                                <i class="bi bi-people fs-1 text-muted"></i>
                            </div>
                            <?php if ($isFiltered): ?>
                                <h2 class="lms-empty-state__title">No members match your search</h2>
                                <p class="lms-empty-state__description">Try a different name, email, or reset your filters.</p>
                                <a href="<?= BASE_URL ?>/members/index.php" class="btn btn-secondary">Clear filters</a>
                            <?php else: ?>
                                <h2 class="lms-empty-state__title">No members registered yet</h2>
                                <p class="lms-empty-state__description">Add your first library member to start issuing books.</p>
                                <a href="<?= BASE_URL ?>/members/create.php" class="btn btn-primary">
                                    <i class="bi bi-person-plus-fill me-1"></i>Add first member
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($members as $m): ?>
                    <?php
                        $isActive = ($m['status'] === 'active');
                        $pillClass = $isActive ? 'lms-pill--success' : 'lms-pill--danger';
                    ?>
                    <tr>
                        <td data-label="Member">
                            <div class="d-flex align-items-center gap-3">
                                <?= avatar($m['full_name'], 38) ?>
                                <div>
                                    <div class="fw-semibold text-dark"><?= e($m['full_name']) ?></div>
                                    <div class="text-muted small"><?= e($m['member_number']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Contact">
                            <div class="text-dark"><?= e($m['email']) ?></div>
                            <?php if (!empty($m['phone'])): ?>
                                <div class="text-muted small">
                                    <i class="bi bi-telephone me-1"></i><?= e($m['phone']) ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small">—</div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Status">
                            <span class="lms-pill <?= $pillClass ?>">
                                <?= ucfirst($m['status']) ?>
                            </span>
                        </td>
                        <td data-label="Joined" class="d-none d-lg-table-cell text-secondary">
                            <?= format_date($m['date_joined']) ?>
                        </td>
                        <td data-label="Actions" class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="<?= BASE_URL ?>/members/edit.php?id=<?= (int)$m['id'] ?>"
                                   class="lms-btn-icon" title="Edit member" aria-label="Edit member <?= e($m['full_name']) ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/members/delete.php?id=<?= (int)$m['id'] ?>"
                                   class="lms-btn-icon lms-btn-icon--danger"
                                   data-confirm
                                   data-confirm-title="Delete Member?"
                                   data-confirm-message="Member '<?= e($m['full_name']) ?>' (<?= e($m['member_number']) ?>) will be removed. This cannot be undone."
                                   data-confirm-button="Delete member"
                                   data-confirm-style="danger"
                                   title="Delete member" aria-label="Delete member <?= e($m['full_name']) ?>">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer -->
    <div class="card-footer d-flex justify-content-between align-items-center py-3">
        <span class="text-muted small">
            <?php if ($isFiltered): ?>
                Showing <?= count($members) ?> of <?= $totalMembers ?> members
            <?php else: ?>
                <?= pluralize($totalMembers, 'member', 'members') ?>
            <?php endif; ?>
        </span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
