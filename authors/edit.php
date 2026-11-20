<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Invalid author ID.');
    redirect(BASE_URL . '/authors/index.php');
}

// Load author
$stmt = $pdo->prepare("SELECT author_id AS id, author_id, name, country FROM authors WHERE author_id = ? LIMIT 1");
$stmt->execute([$id]);
$author = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$author) {
    setFlash('error', 'Author not found.');
    redirect(BASE_URL . '/authors/index.php');
}

$errors  = [];
$name    = $author['name'];
$country = $author['country'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $country = trim($_POST['country'] ?? '');

    if ($name === '') {
        $errors[] = 'Author name is required.';
    } else {
        // Check duplicate (exclude current)
        $dup = $pdo->prepare("SELECT author_id FROM authors WHERE name = ? AND author_id != ? LIMIT 1");
        $dup->execute([$name, $id]);
        if ($dup->fetch()) {
            $errors[] = 'Another author with this name already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE authors SET name = ?, country = ? WHERE author_id = ?");
        $stmt->execute([$name, $country !== '' ? $country : null, $id]);
        setFlash('success', 'Author updated successfully.');
        redirect(BASE_URL . '/authors/index.php');
    }
}

$pageTitle = 'Edit Author';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-4" style="max-width:600px;">
    <h2 class="mb-3">Edit Author</h2>

    <?php displayFlash(); ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" class="form-control"
                   value="<?= e($name) ?>" required>
        </div>
        <div class="mb-3">
            <label for="country" class="form-label">Country</label>
            <input type="text" id="country" name="country" class="form-control"
                   value="<?= e($country) ?>">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Update Author</button>
            <a href="<?= BASE_URL ?>/authors/index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
