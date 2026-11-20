<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Search & filter parameters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';

// Build query
$params = [];
$types  = '';
$where  = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(m.full_name LIKE ? OR m.email LIKE ? OR m.member_no LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($status === 'active' || $status === 'suspended') {
    $where[] = 'm.status = ?';
    $params[] = $status;
    $types .= 's';
}

$sql = 'SELECT m.member_id AS id, m.member_id, m.member_no, m.member_no AS member_number, m.full_name, m.email, m.phone, m.status, m.date_joined
        FROM members m';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY m.full_name ASC';

$stmt = $pdo->prepare($sql);
if ($params) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-people-fill me-2"></i>Members</h2>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>/members/export.php" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
      </a>
      <a href="<?= BASE_URL ?>/members/create.php" class="btn btn-primary">
        <i class="bi bi-person-plus-fill me-1"></i>Add Member
      </a>
    </div>
  </div>

  <?= flashMessage() ?>

  <!-- Search & Filter -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <form method="GET" action="" class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Search</label>
          <input type="text" name="search" class="form-control"
                 placeholder="Name, email, or member number…"
                 value="<?= e($search) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option value="all"      <?= $status === 'all'       ? 'selected' : '' ?>>All</option>
            <option value="active"   <?= $status === 'active'    ? 'selected' : '' ?>>Active</option>
            <option value="suspended"<?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-outline-primary w-100">
            <i class="bi bi-search me-1"></i>Filter
          </button>
          <a href="<?= BASE_URL ?>/members/index.php" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Members Table -->
  <div class="card shadow-sm">
    <div class="card-header bg-white py-3">
      <span class="text-muted small"><?= count($members) ?> member(s) found</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Member No</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Date Joined</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($members)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>No members found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($members as $m): ?>
              <tr>
                <td><code><?= e($m['member_number']) ?></code></td>
                <td class="fw-semibold"><?= e($m['full_name']) ?></td>
                <td><?= e($m['email']) ?></td>
                <td><?= e($m['phone'] ?? '—') ?></td>
                <td>
                  <?php if ($m['status'] === 'active'): ?>
                    <span class="badge bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Suspended</span>
                  <?php endif; ?>
                </td>
                <td><?= e($m['date_joined']) ?></td>
                <td class="text-center">
                  <a href="<?= BASE_URL ?>/members/edit.php?id=<?= (int)$m['id'] ?>"
                     class="btn btn-sm btn-outline-primary me-1" title="Edit">
                    <i class="bi bi-pencil-fill"></i>
                  </a>
                  <a href="<?= BASE_URL ?>/members/delete.php?id=<?= (int)$m['id'] ?>"
                     class="btn btn-sm btn-outline-danger"
                     data-confirm="Delete member '<?= e($m['full_name']) ?>'? This cannot be undone."
                     title="Delete">
                    <i class="bi bi-trash-fill"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
