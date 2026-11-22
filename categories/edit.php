<?php
require_once __DIR__ . '/../config/bootstrap.php';
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

$stmt = $pdo->prepare('SELECT category_id AS id, category_id, name, description FROM categories WHERE category_id = ?');
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
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = ? AND category_id != ?');
        $stmt->execute([$name, $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Another category with this name already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE categories SET name = ?, description = ? WHERE category_id = ?');
        $stmt->execute([$name, $description ?: null, $id]);

        setFlash('success', 'Category "' . $name . '" updated successfully.');
        redirect(BASE_URL . '/categories/index.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Header (Section 6.1 & 7.7) -->
<?= page_header(
    'Edit category',
    'Update category details and classification metadata',
    '<a href="' . BASE_URL . '/categories/index.php" class="btn btn-secondary">' .
        '<i class="bi bi-arrow-left me-1"></i>Back to Categories' .
    '</a>'
) ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-exclamation-octagon-fill fs-5 text-danger"></i>
            <h6 class="mb-0 fw-bold">Please correct the following errors:</h6>
        </div>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title fs-6 mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-pencil text-primary"></i> Edit Category
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold text-dark">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" id="name" class="form-control form-control-lg"
                               value="<?= e($name) ?>" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold text-dark">
                            Description <span class="text-muted small fw-normal">(optional)</span>
                        </label>
                        <textarea name="description" id="description" class="form-control" rows="3"><?= e($description) ?></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/categories/index.php" class="btn btn-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i>Save changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
