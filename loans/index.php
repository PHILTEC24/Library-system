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

$params = [];

$sql = "SELECT l.id,
               b.title AS book_title,
               b.id AS book_id,
               m.full_name AS member_name,
               m.member_number,
               l.date_issued,
               l.due_date,
               l.date_returned,
               CASE
                   WHEN l.date_returned IS NOT NULL THEN 'returned'
                   WHEN l.due_date < CURDATE() THEN 'overdue'
                   ELSE 'active'
               END AS computed_status
        FROM loans l
        JOIN books b ON l.book_id = b.id
        JOIN members m ON l.member_id = m.id
        WHERE 1=1";

if ($search !== '') {
    $sql .= " AND (m.full_name LIKE ? OR b.title LIKE ?)";
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

$sql .= " ORDER BY l.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$loans = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="flex-fill py-4">
<div class="container-fluid px-4">
  <?php displayFlash(); ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><i class="bi bi-arrow-left-right me-2"></i>Loans</h1>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>/loans/export.php" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
      </a>
      <a href="<?= BASE_URL ?>/loans/issue.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Issue Book
      </a>
    </div>
  </div>

  <!-- Search & Filter -->
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label small">Search</label>
          <input type="text" name="search" class="form-control"
                 placeholder="Member name or book title..."
                 value="<?= e($search) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small">Status Filter</label>
          <select name="filter" class="form-select">
            <option value="" <?= $filter === '' ? 'selected' : '' ?>>All Loans</option>
            <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="overdue" <?= $filter === 'overdue' ? 'selected' : '' ?>>Overdue</option>
            <option value="returned" <?= $filter === 'returned' ? 'selected' : '' ?>>Returned</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">
            <i class="bi bi-search"></i> Search
          </button>
          <a href="<?= BASE_URL ?>/loans/index.php" class="btn btn-secondary">
            <i class="bi bi-x"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Book Title</th>
              <th>Member</th>
              <th>Date Issued</th>
              <th>Due Date</th>
              <th>Date Returned</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($loans as $loan): ?>
            <?php
              $status = $loan['computed_status'];
              $badgeClass = match($status) {
                  'active'   => 'bg-success',
                  'overdue'  => 'bg-danger',
                  'returned' => 'bg-secondary',
                  default    => 'bg-secondary',
              };
            ?>
            <tr>
              <td><?= e($loan['id']) ?></td>
              <td><strong><?= e($loan['book_title']) ?></strong></td>
              <td>
                <?= e($loan['member_name']) ?>
                <br><small class="text-muted"><?= e($loan['member_number']) ?></small>
              </td>
              <td><?= e($loan['date_issued']) ?></td>
              <td><?= e($loan['due_date']) ?></td>
              <td><?= $loan['date_returned'] ? e($loan['date_returned']) : '<span class="text-muted">—</span>' ?></td>
              <td>
                <span class="badge <?= $badgeClass ?>">
                  <?= ucfirst($status) ?>
                </span>
              </td>
              <td>
                <?php if ($status === 'active' || $status === 'overdue'): ?>
                  <a href="<?= BASE_URL ?>/loans/return.php?id=<?= $loan['id'] ?>"
                     class="btn btn-sm btn-outline-success"
                     title="Return Book"
                     data-confirm="Return '<?= e($loan['book_title']) ?>' for <?= e($loan['member_name']) ?>?">
                    <i class="bi bi-check-circle"></i> Return
                  </a>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($loans)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No loans found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer text-muted small"><?= count($loans) ?> loan(s) found</div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
