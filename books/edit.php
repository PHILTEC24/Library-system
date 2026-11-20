<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Edit Book';
$errors = [];

// Load book by id (Using book_id from ERD)
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Invalid book ID.');
    header('Location: ' . BASE_URL . '/books/index.php');
    exit;
}

$bookStmt = $pdo->prepare("
    SELECT b.book_id AS id, b.*, sc.category_id,
           (SELECT author_id FROM book_authors WHERE book_id = b.book_id ORDER BY (author_role='primary') DESC LIMIT 1) AS author_id
    FROM books b
    LEFT JOIN sub_categories sc ON b.sub_category_id = sc.sub_category_id
    WHERE b.book_id = ?
");
$bookStmt->execute([$id]);
$book = $bookStmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    setFlash('danger', 'Book not found.');
    header('Location: ' . BASE_URL . '/books/index.php');
    exit;
}

$authors       = $pdo->query('SELECT author_id AS id, name FROM authors ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$categories    = $pdo->query('SELECT category_id AS id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $pdo->query('SELECT s.sub_category_id AS id, s.name, s.category_id, c.name AS category_name FROM sub_categories s JOIN categories c ON s.category_id = c.category_id ORDER BY c.name, s.name')->fetchAll(PDO::FETCH_ASSOC);

$old = $book;

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

    // Validation (Preserved exact ERD logic from 7cabb4a)
    if ($isbn === '') {
        $errors['isbn'] = 'ISBN is required.';
    } else {
        $chk = $pdo->prepare('SELECT book_id FROM books WHERE isbn = ? AND book_id != ?');
        $chk->execute([$isbn, $id]);
        if ($chk->fetch()) {
            $errors['isbn'] = 'This ISBN is already used by another book.';
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

            $copyDelta = $total_copies - intval($book['total_copies']);
            $newAvailable = max(0, intval($book['available_copies']) + $copyDelta);

            $stmt = $pdo->prepare(
                "UPDATE books SET isbn=?, title=?, sub_category_id=?, publisher=?,
                 year_published=?, total_copies=?, available_copies=?
                 WHERE book_id=?"
            );
            $stmt->execute([
                $isbn,
                $title,
                $subcategory_id,
                $publisher !== '' ? $publisher : null,
                $year_published !== '' ? intval($year_published) : null,
                $total_copies,
                $newAvailable,
                $id,
            ]);

            if ($author_id > 0) {
                $checkBa = $pdo->prepare('SELECT author_id FROM book_authors WHERE book_id = ? LIMIT 1');
                $checkBa->execute([$id]);
                if ($checkBa->fetch()) {
                    $updBa = $pdo->prepare('UPDATE book_authors SET author_id = ? WHERE book_id = ?');
                    $updBa->execute([$author_id, $id]);
                } else {
                    $insBa = $pdo->prepare("INSERT INTO book_authors (book_id, author_id, author_role) VALUES (?, ?, 'primary')");
                    $insBa->execute([$id, $author_id]);
                }
            }

            $pdo->commit();
            setFlash('success', 'Book "' . htmlspecialchars($title) . '" updated successfully.');
            header('Location: ' . BASE_URL . '/books/index.php');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors['general'] = 'Failed to update book: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Header (Section 6.1) -->
<?= page_header(
    'Edit book',
    'Update bibliographic details, subcategory, authorship, and inventory copies.',
    '<a href="' . BASE_URL . '/books/index.php" class="btn btn-secondary">' .
        '<i class="bi bi-arrow-left me-1"></i>Cancel' .
    '</a>'
) ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i> Please fix the errors below:</div>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="" class="lms-book-form" novalidate>
    <div class="row g-4">
        <!-- Left Column: Form Cards (8 of 12) -->
        <div class="col-lg-8">
            <!-- 1. Details Card -->
            <div class="card lms-card mb-4">
                <div class="card-header">
                    <h2 class="lms-section-title mb-0">Bibliographic details</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="isbn" class="form-label">ISBN <span class="text-danger">*</span></label>
                            <input type="text" id="isbn" name="isbn"
                                   class="form-control <?= isset($errors['isbn']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($old['isbn'] ?? '') ?>" maxlength="20" required>
                            <?php if (isset($errors['isbn'])): ?>
                                <div class="invalid-feedback"><?= e($errors['isbn']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title"
                                   class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($old['title'] ?? '') ?>" maxlength="255" required>
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-7">
                            <label for="publisher" class="form-label">Publisher</label>
                            <input type="text" id="publisher" name="publisher" class="form-control"
                                   value="<?= e($old['publisher'] ?? '') ?>" maxlength="150">
                        </div>

                        <div class="col-md-5">
                            <label for="year_published" class="form-label">Year published</label>
                            <input type="number" id="year_published" name="year_published"
                                   class="form-control <?= isset($errors['year_published']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($old['year_published'] ?? '') ?>"
                                   min="1000" max="<?= date('Y') ?>">
                            <?php if (isset($errors['year_published'])): ?>
                                <div class="invalid-feedback"><?= e($errors['year_published']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Classification & Authorship Card -->
            <div class="card lms-card mb-4">
                <div class="card-header">
                    <h2 class="lms-section-title mb-0">Classification & authorship</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select id="category_id" name="category_id"
                                    class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($old['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?>
                                <div class="invalid-feedback"><?= e($errors['category_id']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="subcategory_id" class="form-label">Subcategory <span class="text-danger">*</span></label>
                            <select id="subcategory_id" name="subcategory_id"
                                    class="form-select <?= isset($errors['subcategory_id']) ? 'is-invalid' : '' ?>">
                                <option value="">Select a subcategory</option>
                                <?php foreach ($subcategories as $sub): ?>
                                    <option value="<?= $sub['id'] ?>" data-cat="<?= $sub['category_id'] ?>" <?= ($old['sub_category_id'] == $sub['id']) ? 'selected' : '' ?>>
                                        <?= e($sub['category_name']) ?> &rarr; <?= e($sub['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['subcategory_id'])): ?>
                                <div class="invalid-feedback"><?= e($errors['subcategory_id']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="author_id" class="form-label">Author <span class="text-danger">*</span></label>
                            <select id="author_id" name="author_id"
                                    class="form-select <?= isset($errors['author_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">Select an author</option>
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?= $author['id'] ?>" <?= ($old['author_id'] == $author['id']) ? 'selected' : '' ?>>
                                        <?= e($author['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['author_id'])): ?>
                                <div class="invalid-feedback"><?= e($errors['author_id']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Inventory Copies Card -->
            <div class="card lms-card mb-4">
                <div class="card-header">
                    <h2 class="lms-section-title mb-0">Inventory copies</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="total_copies" class="form-label">Total copies <span class="text-danger">*</span></label>
                            <input type="number" id="total_copies" name="total_copies"
                                   class="form-control <?= isset($errors['total_copies']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($old['total_copies'] ?? '1') ?>" min="1" required>
                            <?php if (isset($errors['total_copies'])): ?>
                                <div class="invalid-feedback"><?= e($errors['total_copies']) ?></div>
                            <?php endif; ?>
                            <div class="form-text">
                                Currently <?= e($book['available_copies']) ?> available of <?= e($book['total_copies']) ?> total.
                                Available copies will adjust with the difference.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Available copies</label>
                            <input type="text" class="form-control bg-light" value="<?= e($book['available_copies']) ?>" readonly disabled>
                            <div class="form-text">Changes automatically when books are issued or returned.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="lms-form-sticky-bar">
                <a href="<?= BASE_URL ?>/books/index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save changes
                </button>
            </div>
        </div>

        <!-- Right Column: Live Spine Preview (4 of 12) -->
        <div class="col-lg-4">
            <div class="lms-book-preview-card">
                <p class="text-muted small fw-semibold text-uppercase tracking-wider mb-3">
                    <i class="bi bi-eye me-1 text-primary"></i> Live spine preview
                </p>
                <div class="lms-book-preview-card__spine-wrap">
                    <span id="previewSpine" class="lms-spine lms-spine--lg" style="--spine: #3F5BA9;" aria-hidden="true">
                        <?= e(mb_strtoupper(mb_substr($book['title'], 0, 1, 'UTF-8'), 'UTF-8')) ?>
                    </span>
                </div>
                <div id="previewTitle" class="lms-book-preview-card__title"><?= e($book['title']) ?></div>
                <div id="previewAuthor" class="lms-book-preview-card__author">
                    <?php
                    $initialAuthor = 'No author selected';
                    foreach ($authors as $a) {
                        if ($a['id'] == $book['author_id']) {
                            $initialAuthor = $a['name'];
                            break;
                        }
                    }
                    echo e($initialAuthor);
                    ?>
                </div>

                <div class="mt-3 pt-3 border-top border-light-subtle">
                    <div class="text-muted small mb-2">Available copies preview:</div>
                    <div id="previewPips" class="lms-pips justify-content-center">
                        <?php
                        $tot = min(max(intval($book['total_copies']), 1), 8);
                        $avail = intval($book['available_copies']);
                        for ($i = 0; $i < $tot; $i++) {
                            $fill = ($i < $avail) ? 'is-filled' : '';
                            echo '<span class="lms-pip ' . $fill . '"></span>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
