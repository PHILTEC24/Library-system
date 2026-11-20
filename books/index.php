<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Books';
$search = trim($_GET['search'] ?? '');
$catFilter = intval($_GET['category'] ?? 0);

$params = [];
$sql = "SELECT b.id, b.isbn, b.title, b.publisher, b.year_published,
               b.total_copies, b.available_copies,
               a.name AS author_name, c.name AS category_name
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE 1=1";

if ($search !== '') {
    $sql .= " AND (b.title LIKE ? OR b.isbn LIKE ? OR a.name LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($catFilter > 0) {
    $sql .= " AND b.category_id = ?";
    $params[] = $catFilter;
}
$sql .= " ORDER BY b.title ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="flex-fill py-4">
<div class="container-fluid px-4">
  <?php displayFlash(); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><i class="bi bi-book me-2"></i>Books</h1>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>/books/export.php" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
      </a>
      <a href="<?= BASE_URL ?>/books/create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Book
      </a>
    </div>
  </div>

  <!-- Search & Filter -->
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label small">Search</label>
          <input type="text" name="search" class="form-control" placeholder="Title, ISBN, author..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small">Category</label>
          <select name="category" class="form-select">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Search</button>
          <a href="<?= BASE_URL ?>/books/index.php" class="btn btn-secondary"><i class="bi bi-x"></i></a>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-dark"><tr>
            <th>ISBN</th><th>Title</th><th>Author</th><th>Category</th>
            <th>Year</th><th>Copies</th><th>Available</th><th>Actions</th>
          </tr></thead>
          <tbody>
          <?php foreach ($books as $book): ?>
            <tr>
              <td><small><?= e($book['isbn']) ?></small></td>
              <td><strong><?= e($book['title']) ?></strong></td>
              <td><?= e($book['author_name'] ?? '—') ?></td>
              <td><?= e($book['category_name'] ?? '—') ?></td>
              <td><?= e($book['year_published'] ?? '—') ?></td>
              <td><?= e($book['total_copies']) ?></td>
              <td>
                <?php if ($book['available_copies'] == 0): ?>
                  <span class="badge bg-danger"><?= $book['available_copies'] ?></span>
                <?php elseif ($book['available_copies'] < 2): ?>
                  <span class="badge bg-warning text-dark"><?= $book['available_copies'] ?></span>
                <?php else: ?>
                  <span class="badge bg-success"><?= $book['available_copies'] ?></span>
                <?php endif; ?>
              </td>
              <td>
                <a href="<?= BASE_URL ?>/books/edit.php?id=<?= $book['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="<?= BASE_URL ?>/books/delete.php?id=<?= $book['id'] ?>" class="btn btn-sm btn-outline-danger"
                   data-confirm="Delete '<?= e($book['title']) ?>'? This cannot be undone." title="Delete">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($books)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No books found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer text-muted small"><?= count($books) ?> book(s) found</div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
