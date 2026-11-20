<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Add Author';
$errors = [];
$name    = '';
$country = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $country = trim($_POST['country'] ?? '');

    // Validate name required
    if ($name === '') {
        $errors[] = 'Author name is required.';
    } else {
        // Check duplicate
        $dup = $pdo->prepare("SELECT author_id FROM authors WHERE name = ? LIMIT 1");
        $dup->execute([$name]);
        if ($dup->fetch()) {
            $errors[] = 'An author with this name already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO authors (name, country) VALUES (?, ?)");
        $stmt->execute([$name, $country !== '' ? $country : null]);
        setFlash('success', 'Author "' . e($name) . '" added successfully.');
        redirect(BASE_URL . '/authors/index.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Header (Section 6.1 & 7.6) -->
<?= page_header(
    'Add author',
    'Register a new author or contributor for cataloguing',
    '<a href="' . BASE_URL . '/authors/index.php" class="btn btn-secondary">' .
        '<i class="bi bi-arrow-left me-1"></i>Back to Authors' .
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
                    <i class="bi bi-person text-primary"></i> Author Profile
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold text-dark">
                            Author Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="name" name="name" class="form-control form-control-lg"
                               value="<?= e($name) ?>" required autofocus placeholder="e.g. Gabriel García Márquez">
                        <div class="form-text text-muted">Full professional or pen name as printed on books.</div>
                    </div>

                    <div class="mb-4">
                        <label for="country" class="form-label fw-semibold text-dark">
                            Country / Origin <span class="text-muted small fw-normal">(optional)</span>
                        </label>
                        <input type="text" id="country" name="country" class="form-control"
                               value="<?= e($country) ?>" placeholder="e.g. Colombia">
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/authors/index.php" class="btn btn-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i>Add author
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
