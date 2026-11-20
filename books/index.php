<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Books';
$search = trim($_GET['search'] ?? '');
$catFilter = intval($_GET['category'] ?? 0);
$subFilter = intval($_GET['subcategory'] ?? 0);

// Global counts
$totalTitles = (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$totalCopies = (int)$pdo->query('SELECT COALESCE(SUM(total_copies), 0) FROM books')->fetchColumn();

$isPgsql = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql');
$authorAgg = $isPgsql
    ? "COALESCE((SELECT STRING_AGG(a2.name, ', ' ORDER BY (ba2.author_role='primary') DESC, a2.name) FROM book_authors ba2 JOIN authors a2 ON ba2.author_id = a2.author_id WHERE ba2.book_id = b.book_id), '') AS author_name,"
    : "GROUP_CONCAT(DISTINCT a.name ORDER BY (ba.author_role='primary') DESC, a.name SEPARATOR ', ') AS author_name,";

$params = [];
$sql = "SELECT b.book_id AS id, b.isbn, b.title, b.publisher, b.year_published,
               b.total_copies, b.available_copies, b.cover_image, b.ebook_file,
               $authorAgg
               c.category_id,
               c.name AS category_name,
               s.name AS subcategory_name
        FROM books b
        LEFT JOIN book_authors ba ON b.book_id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.author_id
        LEFT JOIN sub_categories s ON b.sub_category_id = s.sub_category_id
        LEFT JOIN categories c ON s.category_id = c.category_id
        WHERE 1=1";

if ($search !== '') {
    $sql .= " AND (b.title LIKE ? OR b.isbn LIKE ? OR a.name LIKE ? OR c.name LIKE ? OR s.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catFilter > 0) {
    $sql .= " AND s.category_id = ?";
    $params[] = $catFilter;
}
if ($subFilter > 0) {
    $sql .= " AND b.sub_category_id = ?";
    $params[] = $subFilter;
}
$sql .= " GROUP BY b.book_id, b.isbn, b.title, b.publisher, b.year_published, b.total_copies, b.available_copies, b.cover_image, b.ebook_file, c.category_id, c.name, s.name ORDER BY b.title ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query('SELECT category_id AS id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $pdo->query('SELECT s.sub_category_id AS id, s.name, s.category_id, c.name AS category_name FROM sub_categories s JOIN categories c ON s.category_id = c.category_id ORDER BY c.name, s.name')->fetchAll(PDO::FETCH_ASSOC);

$isFiltered = ($search !== '' || $catFilter > 0 || $subFilter > 0);

$selectedCatName = '';
if ($catFilter > 0) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $catFilter) {
            $selectedCatName = $cat['name'];
            break;
        }
    }
}

$selectedSubName = '';
if ($subFilter > 0) {
    foreach ($subcategories as $sub) {
        if ($sub['id'] == $subFilter) {
            $selectedSubName = $sub['name'];
            break;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Header (Section 6.1 & 7.3) -->
<?= page_header(
    'Books',
    $totalTitles . ' titles, ' . $totalCopies . ' copies in catalogue',
    '<a href="' . BASE_URL . '/books/export.php" class="btn btn-secondary">' .
        '<i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV' .
    '</a>' .
    '<a href="' . BASE_URL . '/books/create.php" class="btn btn-primary">' .
        '<i class="bi bi-plus-lg me-1"></i>Add book' .
    '</a>'
) ?>

<!-- Table & Filter Card -->
<div class="lms-table-card mb-4">
    <!-- Filter Bar (Section 6.3) -->
    <div class="lms-filter-bar">
        <form method="GET" action="<?= BASE_URL ?>/books/index.php" class="lms-auto-filter">
            <div class="lms-filter-bar__row">
                <div class="lms-filter-bar__inputs">
                    <div class="lms-filter-input-wrap">
                        <i class="bi bi-search"></i>
                        <input type="search" name="search" class="form-control"
                               placeholder="Search title, author, ISBN..."
                               value="<?= e($search) ?>" aria-label="Search books">
                    </div>

                    <select name="category" class="form-select lms-filter-select" onchange="this.form.submit()" aria-label="Filter by category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="subcategory" class="form-select lms-filter-select" onchange="this.form.submit()" aria-label="Filter by subcategory">
                        <option value="">All subcategories</option>
                        <?php foreach ($subcategories as $sub): ?>
                            <?php if ($catFilter > 0 && $sub['category_id'] != $catFilter) continue; ?>
                            <option value="<?= $sub['id'] ?>" <?= $subFilter == $sub['id'] ? 'selected' : '' ?>>
                                <?= $catFilter > 0 ? e($sub['name']) : e($sub['category_name'] . ' → ' . $sub['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <noscript>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </noscript>
                </div>

                <div class="text-muted small">
                    <?php if ($isFiltered): ?>
                        Showing <?= count($books) ?> of <?= $totalTitles ?> books
                    <?php else: ?>
                        <?= pluralize($totalTitles, 'book', 'books') ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isFiltered): ?>
                <div class="lms-filter-chips mt-2 pt-2 border-top border-light-subtle">
                    <span class="text-muted">Active filters:</span>
                    <?php if ($search !== ''): ?>
                        <span class="lms-chip">
                            <span>"<?= e($search) ?>"</span>
                            <a href="<?= BASE_URL ?>/books/index.php?category=<?= $catFilter ?>&subcategory=<?= $subFilter ?>" class="lms-chip__remove" aria-label="Remove search filter">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($catFilter > 0): ?>
                        <span class="lms-chip">
                            <span>Category: <?= e($selectedCatName) ?></span>
                            <a href="<?= BASE_URL ?>/books/index.php?search=<?= urlencode($search) ?>&subcategory=<?= $subFilter ?>" class="lms-chip__remove" aria-label="Remove category filter">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($subFilter > 0): ?>
                        <span class="lms-chip">
                            <span>Subcategory: <?= e($selectedSubName) ?></span>
                            <a href="<?= BASE_URL ?>/books/index.php?search=<?= urlencode($search) ?>&category=<?= $catFilter ?>" class="lms-chip__remove" aria-label="Remove subcategory filter">&times;</a>
                        </span>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/books/index.php" class="text-decoration-none small ms-2 text-danger">Clear filters</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Data Table (Section 6.4 & 7.3) -->
    <div class="table-responsive">
        <table class="lms-table lms-table-stacked align-middle">
            <caption class="visually-hidden">Catalogue of library books, availability and copies</caption>
            <thead>
                <tr>
                    <th scope="col" style="min-width: 260px;">Book</th>
                    <th scope="col" class="d-none d-lg-table-cell" style="width: 140px;">ISBN</th>
                    <th scope="col" style="width: 180px;">Category</th>
                    <th scope="col" class="d-none d-lg-table-cell" style="width: 90px;">Year</th>
                    <th scope="col" style="width: 180px;">Availability</th>
                    <th scope="col" class="text-end" style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($books)): ?>
                <tr>
                    <td colspan="6" class="p-0 border-0">
                        <div class="lms-empty-state">
                            <div class="lms-empty-state__icon">
                                <?= spine_tile('?', 0, 'lg') ?>
                            </div>
                            <?php if ($isFiltered): ?>
                                <h2 class="lms-empty-state__title">No books match your filter</h2>
                                <p class="lms-empty-state__help">Try adjusting your category, subcategory or search term.</p>
                                <a href="<?= BASE_URL ?>/books/index.php" class="btn btn-secondary">Clear filters</a>
                            <?php else: ?>
                                <h2 class="lms-empty-state__title">No books in catalogue yet</h2>
                                <p class="lms-empty-state__help">Add your first book to begin cataloguing and issuing loans.</p>
                                <a href="<?= BASE_URL ?>/books/create.php" class="btn btn-primary">Add book</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($books as $book): ?>
                    <?php
                    $catId = intval($book['category_id'] ?? 0);
                    $palette = ['#3F5BA9', '#2E7D6B', '#8C4A6B', '#A85A17', '#5A6B3A', '#5E548E'];
                    $dotColor = $palette[abs($catId) % 6];
                    ?>
                    <tr>
                        <!-- 1. Book (Spine + Title + Author) -->
                        <td class="lms-td-primary">
                            <div class="d-flex align-items-center gap-3">
                                <?php if (!empty($book['cover_image'])): ?>
                                    <img src="<?= BASE_URL ?>/books/media.php?id=<?= (int)$book['id'] ?>&amp;type=cover"
                                         alt="Cover of <?= e($book['title']) ?>"
                                         style="width: 48px; height: 64px; object-fit: cover; border-radius: .25rem;">
                                <?php else: ?>
                                    <?= spine_tile($book['title'], $catId, 'md') ?>
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate" title="<?= e($book['title']) ?>">
                                        <?= e($book['title']) ?>
                                    </div>
                                    <div class="text-muted small text-truncate">
                                        <?= e($book['author_name'] ?? 'Unknown author') ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- 2. ISBN -->
                        <td class="d-none d-lg-table-cell lms-tabular" data-label="ISBN">
                            <span class="text-muted"><?= e($book['isbn']) ?></span>
                        </td>

                        <!-- 3. Category & Subcategory -->
                        <td data-label="Category">
                            <div>
                                <span class="d-inline-flex align-items-center">
                                    <span class="lms-category-dot" style="background-color: <?= $dotColor ?>;"></span>
                                    <span class="fw-medium"><?= e($book['category_name'] ?? 'Uncategorized') ?></span>
                                </span>
                            </div>
                            <?php if (!empty($book['subcategory_name'])): ?>
                                <div class="text-muted small ps-3">
                                    &rdquor; <?= e($book['subcategory_name']) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- 4. Year -->
                        <td class="d-none d-lg-table-cell lms-tabular" data-label="Year">
                            <?= e($book['year_published'] ?? '—') ?>
                        </td>

                        <!-- 5. Availability (Meter from 6.7) -->
                        <td data-label="Availability">
                            <?= availability_meter($book['available_copies'], $book['total_copies']) ?>
                        </td>

                        <!-- 6. Actions -->
                        <td class="lms-td-actions text-end" data-label="Actions">
                            <div class="d-inline-flex align-items-center gap-1">
                                <?php if (!empty($book['ebook_file'])): ?>
                                    <a href="<?= BASE_URL ?>/books/media.php?id=<?= (int)$book['id'] ?>&amp;type=ebook"
                                       target="_blank" rel="noopener"
                                       class="lms-btn-icon" title="Open e-book" aria-label="Open e-book <?= e($book['title']) ?>">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/books/media.php?id=<?= (int)$book['id'] ?>&amp;type=ebook&amp;download=1"
                                       class="lms-btn-icon" title="Download e-book" aria-label="Download e-book <?= e($book['title']) ?>">
                                        <i class="bi bi-download"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>/books/edit.php?id=<?= $book['id'] ?>"
                                   class="lms-btn-icon"
                                   title="Edit book" aria-label="Edit <?= e($book['title']) ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <?php if (is_admin()): ?>
                                    <a href="<?= BASE_URL ?>/books/delete.php?id=<?= $book['id'] ?>"
                                       class="lms-btn-icon lms-btn-icon--danger"
                                       title="Delete book" aria-label="Delete <?= e($book['title']) ?>"
                                       data-confirm="Delete '<?= e($book['title']) ?>'? This cannot be undone."
                                       data-confirm-title="Delete this book?"
                                       data-confirm-message="&quot;<?= e($book['title']) ?>&quot; will be permanently deleted from the catalogue."
                                       data-confirm-button="Delete book"
                                       data-confirm-style="danger"
                                       onclick="return confirm('Delete this book?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer -->
    <div class="lms-table-footer">
        <span><?= pluralize(count($books), 'book', 'books') ?></span>
        <span class="text-muted small">Updated in real time</span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
