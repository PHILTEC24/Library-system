<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Dashboard';

// Stats
$totalBooks    = $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$totalMembers  = $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$activeLoans   = $pdo->query("SELECT COUNT(*) FROM loans WHERE date_returned IS NULL")->fetchColumn();
$overdueLoans  = $pdo->query("SELECT COUNT(*) FROM loans WHERE date_returned IS NULL AND due_date < CURDATE()")->fetchColumn();

// Recent loans
$recentLoans = $pdo->query(
    "SELECT l.id, b.title, m.full_name, l.date_issued, l.due_date, l.date_returned,
            CASE WHEN l.date_returned IS NOT NULL THEN 'returned'
                 WHEN l.due_date < CURDATE() THEN 'overdue'
                 ELSE 'active' END AS loan_status
     FROM loans l JOIN books b ON l.book_id=b.id JOIN members m ON l.member_id=m.id
     ORDER BY l.id DESC LIMIT 10"
)->fetchAll();

// Overdue list
$overdueList = $pdo->query(
    "SELECT b.title, m.full_name, m.phone, l.due_date, DATEDIFF(CURDATE(), l.due_date) AS days_overdue
     FROM loans l JOIN books b ON l.book_id=b.id JOIN members m ON l.member_id=m.id
     WHERE l.date_returned IS NULL AND l.due_date < CURDATE()
     ORDER BY days_overdue DESC LIMIT 5"
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="flex-fill py-4">
<div class="container-fluid px-4">
  <?php displayFlash(); ?>
  <h1 class="h3 mb-4"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>

  <!-- Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="card border-primary shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="fs-1 text-primary me-3"><i class="bi bi-book"></i></div>
          <div>
            <div class="fs-2 fw-bold"><?= $totalBooks ?></div>
            <div class="text-muted">Total Books</div>
          </div>
        </div>
        <div class="card-footer"><a href="<?= BASE_URL ?>/books/index.php" class="text-decoration-none small">View all &rarr;</a></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card border-success shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="fs-1 text-success me-3"><i class="bi bi-people"></i></div>
          <div>
            <div class="fs-2 fw-bold"><?= $totalMembers ?></div>
            <div class="text-muted">Members</div>
          </div>
        </div>
        <div class="card-footer"><a href="<?= BASE_URL ?>/members/index.php" class="text-decoration-none small">View all &rarr;</a></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card border-warning shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="fs-1 text-warning me-3"><i class="bi bi-arrow-left-right"></i></div>
          <div>
            <div class="fs-2 fw-bold"><?= $activeLoans ?></div>
            <div class="text-muted">Active Loans</div>
          </div>
        </div>
        <div class="card-footer"><a href="<?= BASE_URL ?>/loans/index.php" class="text-decoration-none small">View all &rarr;</a></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card border-danger shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="fs-1 text-danger me-3"><i class="bi bi-exclamation-triangle"></i></div>
          <div>
            <div class="fs-2 fw-bold"><?= $overdueLoans ?></div>
            <div class="text-muted">Overdue</div>
          </div>
        </div>
        <div class="card-footer"><a href="<?= BASE_URL ?>/loans/index.php?filter=overdue" class="text-decoration-none small">View overdue &rarr;</a></div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Recent Loans -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong><i class="bi bi-clock-history me-1"></i> Recent Loans</strong>
          <a href="<?= BASE_URL ?>/loans/index.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light"><tr>
                <th>Book</th><th>Member</th><th>Issued</th><th>Due</th><th>Status</th>
              </tr></thead>
              <tbody>
              <?php foreach ($recentLoans as $loan): ?>
                <tr>
                  <td><?= e($loan['title']) ?></td>
                  <td><?= e($loan['full_name']) ?></td>
                  <td><?= e($loan['date_issued']) ?></td>
                  <td><?= e($loan['due_date']) ?></td>
                  <td>
                    <?php if ($loan['loan_status'] === 'returned'): ?>
                      <span class="badge bg-secondary">Returned</span>
                    <?php elseif ($loan['loan_status'] === 'overdue'): ?>
                      <span class="badge bg-danger">Overdue</span>
                    <?php else: ?>
                      <span class="badge bg-success">Active</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($recentLoans)): ?><tr><td colspan="5" class="text-center text-muted py-3">No loans yet</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Overdue Alert -->
    <div class="col-lg-4">
      <div class="card shadow-sm border-danger">
        <div class="card-header bg-danger text-white">
          <strong><i class="bi bi-exclamation-triangle me-1"></i> Overdue Books</strong>
        </div>
        <div class="card-body p-0">
          <?php if (empty($overdueList)): ?>
            <p class="text-center text-muted py-3">No overdue books!</p>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($overdueList as $item): ?>
                <li class="list-group-item">
                  <strong><?= e($item['full_name']) ?></strong>
                  <span class="badge bg-danger float-end"><?= $item['days_overdue'] ?>d overdue</span>
                  <div class="small text-muted"><?= e($item['title']) ?></div>
                  <div class="small text-muted"><?= e($item['phone'] ?? 'No phone') ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
