<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Edit Category';
$errors = [];

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Invalid category ID.');
    redirect(BASE_URL . '/categories/index.php');
}

$stmt = $pdo->prepare('SELECT id, name, description FROM categories WHERE id = ?');
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    setFlash('danger', 'Category not found.');
    redirect(BASE_URL . '/categories/index.php');
}

$name = $category['name'];
$description = $category['description'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $errors[] = 'Category name is required.';
    } else {
        // Check uniqueness excluding current record
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?');
        $stmt->execute([$name, $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Another category with this name already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?');
        $stmt->execute([$name, $description ?: null, $id]);

        setFlash('success', 'Category updated successfully.');
        redirect(BASE_URL . '/categories/index.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="flex-fill py-4">
<div class="container" style="max-width: 600px;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Category</h2>
    <a href="<?= BASE_URL ?>/categories/index.php" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body p-4">
      <form method="POST" action="">
        <div class="mb-3">
          <label for="name" class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
          <input type="text" name="name" id="name" class="form-control" value="<?= e($name) ?>" required>
        </div>

        <div class="mb-4">
          <label for="description" class="form-label fw-semibold">Description</label>
          <textarea name="description" id="description" class="form-control" rows="3"><?= e($description) ?></textarea>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="<?= BASE_URL ?>/categories/index.php" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i>Update Category</button>
        </div>
      </form>
    </div>
  </div>
</div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
