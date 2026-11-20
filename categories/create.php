<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Add Category';
$errors = [];
$name = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $errors[] = 'Category name is required.';
    } else {
        // Check uniqueness
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = ?');
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'A category with this name already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
        $stmt->execute([$name, $description ?: null]);

        setFlash('success', 'Category "' . e($name) . '" added successfully.');
        redirect(BASE_URL . '/categories/index.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="flex-fill py-4">
<div class="container" style="max-width: 600px;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0"><i class="bi bi-tag me-2"></i>Add Category</h2>
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
          <input type="text" name="name" id="name" class="form-control" value="<?= e($name) ?>" required autofocus placeholder="e.g. Science Fiction">
        </div>

        <div class="mb-4">
          <label for="description" class="form-label fw-semibold">Description</label>
          <textarea name="description" id="description" class="form-control" rows="3" placeholder="Brief summary of category..."><?= e($description) ?></textarea>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="<?= BASE_URL ?>/categories/index.php" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i>Save Category</button>
        </div>
      </form>
    </div>
  </div>
</div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
