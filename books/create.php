<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Add Book';
$errors = [];
$old = [];

$authors       = $pdo->query('SELECT author_id AS id, name FROM authors ORDER BY name')->fetchAll();
$categories    = $pdo->query('SELECT category_id AS id, name FROM categories ORDER BY name')->fetchAll();
$subcategories = $pdo->query('SELECT s.sub_category_id AS id, s.name, s.category_id, c.name AS category_name FROM sub_categories s JOIN categories c ON s.category_id = c.category_id ORDER BY c.name, s.name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $isbn           = trim($_POST['isbn'] ?? '');
    $title          = trim($_POST['title'] ?? '');
    $author_id      = intval($_POST['author_id'] ?? 0);
    $category_id    = intval($_POST['category_id'] ?? 0);
    $subcategory_id = intval($_POST['subcategory_id'] ?? 0);
    $publisher      = trim($_POST['publisher'] ?? '');
    $year_published = trim($_POST['year_published'] ?? '');
    $total_copies   = intval($_POST['total_copies'] ?? 1);

    // Validation
    if ($isbn === '') {
        $errors['isbn'] = 'ISBN is required.';
    } else {
        $chk = $pdo->prepare('SELECT book_id FROM books WHERE isbn = ?');
        $chk->execute([$isbn]);
        if ($chk->fetch()) {
            $errors['isbn'] = 'This ISBN already exists.';
        }
    }

    if ($title === '') {
        $errors['title'] = 'Title is required.';
    }

    if ($author_id <= 0) {
        $errors['author_id'] = 'Please select an author.';
    }

    if ($category_id <= 0) {
        $errors['category_id'] = 'Please select a category.';
    }

    if ($subcategory_id <= 0 && $category_id > 0) {
        $firstSub = $pdo->prepare('SELECT sub_category_id FROM sub_categories WHERE category_id = ? LIMIT 1');
        $firstSub->execute([$category_id]);
        $subcategory_id = intval($firstSub->fetchColumn() ?: 0);
    }

    if ($subcategory_id <= 0) {
        $errors['subcategory_id'] = 'Please select a valid subcategory for this book.';
    }

    if ($year_published !== '') {
        $yr = intval($year_published);
        if ($yr < 1000 || $yr > intval(date('Y'))) {
            $errors['year_published'] = 'Year must be between 1000 and ' . date('Y') . '.';
        }
    }

    if ($total_copies < 1) {
        $errors['total_copies'] = 'Total copies must be at least 1.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "INSERT INTO books (isbn, title, sub_category_id, publisher, year_published, total_copies, available_copies)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $isbn,
                $title,
                $subcategory_id,
                $publisher !== '' ? $publisher : null,
                $year_published !== '' ? intval($year_published) : null,
                $total_copies,
                $total_copies,
            ]);
            $newBookId = $pdo->lastInsertId();
            if ($author_id > 0) {
                $baStmt = $pdo->prepare("INSERT INTO book_authors (book_id, author_id, author_role) VALUES (?, ?, 'primary')");
                $baStmt->execute([$newBookId, $author_id]);
            }
            $pdo->commit();
            setFlash('success', 'Book "' . htmlspecialchars($title) . '" added successfully.');
            header('Location: ' . BASE_URL . '/books/index.php');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors['general'] = 'Failed to add book: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="flex-fill py-4">
<div class="container px-4" style="max-width:720px;">
  <?php displayFlash(); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><i class="bi bi-plus-circle me-2"></i>Add Book</h1>
    <a href="<?= BASE_URL ?>/books/index.php" class="btn btn-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">Please fix the errors below.</div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="POST" novalidate>
        <!-- ISBN -->
        <div class="mb-3">
          <label for="isbn" class="form-label">ISBN <span class="text-danger">*</span></label>
          <input type="text" id="isbn" name="isbn" class="form-control <?= isset($errors['isbn']) ? 'is-invalid' : '' ?>"
                 value="<?= e($old['isbn'] ?? '') ?>" maxlength="20" required>
          <?php if (isset($errors['isbn'])): ?>
            <div class="invalid-feedback"><?= e($errors['isbn']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Title -->
        <div class="mb-3">
          <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
          <input type="text" id="title" name="title" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                 value="<?= e($old['title'] ?? '') ?>" maxlength="255" required>
          <?php if (isset($errors['title'])): ?>
            <div class="invalid-feedback"><?= e($errors['title']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Author -->
        <div class="mb-3">
          <label for="author_id" class="form-label">Author <span class="text-danger">*</span></label>
          <select id="author_id" name="author_id" class="form-select <?= isset($errors['author_id']) ? 'is-invalid' : '' ?>">
            <option value="">— Select Author —</option>
            <?php foreach ($authors as $author): ?>
              <option value="<?= $author['id'] ?>" <?= (isset($old['author_id']) && $old['author_id'] == $author['id']) ? 'selected' : '' ?>>
                <?= e($author['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['author_id'])): ?>
            <div class="invalid-feedback"><?= e($errors['author_id']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Category -->
        <div class="mb-3">
          <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
          <select id="category_id" name="category_id" class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" onchange="filterSubcategories()">
            <option value="">— Select Category —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= (isset($old['category_id']) && $old['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['category_id'])): ?>
            <div class="invalid-feedback"><?= e($errors['category_id']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Subcategory -->
        <div class="mb-3">
          <label for="subcategory_id" class="form-label">Subcategory <small class="text-muted">(Optional)</small></label>
          <select id="subcategory_id" name="subcategory_id" class="form-select">
            <option value="">— Select Subcategory —</option>
            <?php foreach ($subcategories as $sub): ?>
              <option value="<?= $sub['id'] ?>" data-cat="<?= $sub['category_id'] ?>" <?= (isset($old['subcategory_id']) && $old['subcategory_id'] == $sub['id']) ? 'selected' : '' ?>>
                <?= e($sub['category_name']) ?> &rarr; <?= e($sub['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text text-muted">e.g. Novels under Fiction, Software Engineering under Technology.</div>
        </div>

        <!-- Publisher -->
        <div class="mb-3">
          <label for="publisher" class="form-label">Publisher</label>
          <input type="text" id="publisher" name="publisher" class="form-control"
                 value="<?= e($old['publisher'] ?? '') ?>" maxlength="150">
        </div>

        <div class="row g-3 mb-3">
          <!-- Year Published -->
          <div class="col-md-6">
            <label for="year_published" class="form-label">Year Published</label>
            <input type="number" id="year_published" name="year_published"
                   class="form-control <?= isset($errors['year_published']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['year_published'] ?? '') ?>"
                   min="1000" max="<?= date('Y') ?>" placeholder="e.g. 2020">
            <?php if (isset($errors['year_published'])): ?>
              <div class="invalid-feedback"><?= e($errors['year_published']) ?></div>
            <?php endif; ?>
          </div>

          <!-- Total Copies -->
          <div class="col-md-6">
            <label for="total_copies" class="form-label">Total Copies <span class="text-danger">*</span></label>
            <input type="number" id="total_copies" name="total_copies"
                   class="form-control <?= isset($errors['total_copies']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['total_copies'] ?? '1') ?>" min="1" required>
            <?php if (isset($errors['total_copies'])): ?>
              <div class="invalid-feedback"><?= e($errors['total_copies']) ?></div>
            <?php endif; ?>
            <div class="form-text">Available copies will be set equal to total copies.</div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Book</button>
          <a href="<?= BASE_URL ?>/books/index.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
</main>
<script>
function filterSubcategories() {
  const catId = document.getElementById('category_id').value;
  const subSelect = document.getElementById('subcategory_id');
  const options = subSelect.querySelectorAll('option');

  options.forEach(opt => {
    if (!opt.value) return; // keep default placeholder
    const optCat = opt.getAttribute('data-cat');
    if (!catId || optCat === catId) {
      opt.style.display = '';
    } else {
      opt.style.display = 'none';
      if (opt.selected) subSelect.value = '';
    }
  });
}
document.addEventListener('DOMContentLoaded', filterSubcategories);
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
