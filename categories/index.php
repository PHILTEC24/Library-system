<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Categories';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT c.id, c.name, c.description, COUNT(b.id) AS book_count
        FROM categories c
        LEFT JOIN books b ON b.category_id = c.id";

$params = [];
if ($search !== '') {
    $sql .= " WHERE c.name LIKE ?";
    $params[] = '%' . $search . '%';
}

$sql .= " GROUP BY c.id, c.name, c.description ORDER BY c.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="flex-fill py-4">
<div class="container-fluid px-4">
  <?php displayFlash(); ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><i class="bi bi-tags me-2"></i>Categories</h1>
    <a href="<?= BASE_URL ?>/categories/create.php" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> Add Category
    </a>
  </div>

  <!-- Search -->
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
          <input type="text" name="search" class="form-control"
                 placeholder="Search category name..."
                 value="<?= e($search) ?>">
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
          <?php if ($search !== ''): ?>
            <a href="<?= BASE_URL ?>/categories/index.php" class="btn btn-secondary"><i class="bi bi-x"></i> Clear</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th style="width: 80px;">#</th>
              <th>Name</th>
              <th>Description</th>
              <th style="width: 140px;">Total Books</th>
              <th style="width: 120px;" class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td><?= e($cat['id']) ?></td>
              <td><strong><?= e($cat['name']) ?></strong></td>
              <td><?= e($cat['description'] ?? '—') ?></td>
              <td>
                <span class="badge bg-info text-dark"><?= $cat['book_count'] ?> books</span>
              </td>
              <td class="text-center">
                <a href="<?= BASE_URL ?>/categories/edit.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="<?= BASE_URL ?>/categories/delete.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger"
                   data-confirm="Delete category '<?= e($cat['name']) ?>'? This cannot be undone." title="Delete">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($categories)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No categories found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer text-muted small"><?= count($categories) ?> category(ies) found</div>
  </div>
</div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
