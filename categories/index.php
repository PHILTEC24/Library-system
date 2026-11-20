<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Categories';
$search = trim($_GET['search'] ?? '');

$totalCategories = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

$sql = "SELECT c.category_id AS id, c.category_id, c.name, c.description,
               COUNT(DISTINCT sc.sub_category_id) AS subcat_count,
               COUNT(b.book_id) AS book_count
        FROM categories c
        LEFT JOIN sub_categories sc ON sc.category_id = c.category_id
        LEFT JOIN books b ON b.sub_category_id = sc.sub_category_id";

$params = [];
if ($search !== '') {
    $sql .= " WHERE c.name LIKE ? OR c.description LIKE ?";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " GROUP BY c.category_id, c.name, c.description ORDER BY c.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isFiltered = ($search !== '');

// Spine palette colors for category dots
$palette = ['#3F5BA9', '#2E7D6B', '#8C4A6B', '#A85A17', '#5A6B3A', '#5E548E'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Header (Section 6.1 & 7.7) -->
<?= page_header(
    'Categories',
    $totalCategories . ' subject classifications in catalogue',
    '<a href="' . BASE_URL . '/categories/create.php" class="btn btn-primary">' .
        '<i class="bi bi-plus-lg me-1"></i>Add category' .
    '</a>'
) ?>

<?php displayFlash(); ?>

<!-- Table & Filter Card -->
<div class="lms-table-card mb-4">
    <!-- Filter Bar (Section 6.3 & 7.7) -->
    <div class="lms-filter-bar">
        <form method="GET" action="<?= BASE_URL ?>/categories/index.php" class="lms-auto-filter">
            <div class="lms-filter-bar__row">
                <div class="lms-filter-bar__inputs">
                    <div class="lms-filter-input-wrap">
                        <i class="bi bi-search"></i>
                        <input type="search" name="search" class="form-control"
                               placeholder="Search category name..."
                               value="<?= e($search) ?>" aria-label="Search categories">
                    </div>

                    <noscript>
                        <button type="submit" class="btn btn-secondary">Search</button>
                    </noscript>
                </div>

                <div class="text-muted small">
                    <?php if ($isFiltered): ?>
                        Showing <?= count($categories) ?> of <?= $totalCategories ?> categories
                    <?php else: ?>
                        <?= pluralize($totalCategories, 'category', 'categories') ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isFiltered): ?>
                <div class="lms-filter-chips mt-2 pt-2 border-top border-light-subtle">
                    <span class="text-muted">Active filters:</span>
                    <span class="lms-chip">
                        <span>"<?= e($search) ?>"</span>
                        <a href="<?= BASE_URL ?>/categories/index.php" class="lms-chip__remove" aria-label="Remove search filter">&times;</a>
                    </span>
                    <a href="<?= BASE_URL ?>/categories/index.php" class="text-decoration-none small ms-2 text-danger">Clear filters</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Data Table (Section 6.4 & 7.7) -->
    <div class="table-responsive">
        <table class="lms-table lms-table-stacked align-middle mb-0">
            <caption class="visually-hidden">Classification categories with descriptions and total books</caption>
            <thead>
                <tr>
                    <th scope="col" style="min-width: 240px;">Category</th>
                    <th scope="col" style="min-width: 260px;">Description</th>
                    <th scope="col" style="width: 160px;">Subcategories</th>
                    <th scope="col" style="width: 150px;">Total Books</th>
                    <th scope="col" class="text-end" style="width: 110px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="5" class="p-0 border-0">
                        <div class="lms-empty-state">
                            <div class="lms-empty-state__icon">
                                <i class="bi bi-tags fs-1 text-muted"></i>
                            </div>
                            <?php if ($isFiltered): ?>
                                <h2 class="lms-empty-state__title">No categories match "<?= e($search) ?>"</h2>
                                <p class="lms-empty-state__description">Try a different search term or clear the filter.</p>
                                <a href="<?= BASE_URL ?>/categories/index.php" class="btn btn-secondary">Clear filter</a>
                            <?php else: ?>
                                <h2 class="lms-empty-state__title">No categories created yet</h2>
                                <p class="lms-empty-state__description">Create subject categories to organize books and spine colors.</p>
                                <a href="<?= BASE_URL ?>/categories/create.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i>Add first category
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <?php
                        $catIdx = abs((int)$cat['id']) % count($palette);
                        $dotColor = $palette[$catIdx];
                    ?>
                    <tr>
                        <td data-label="Category">
                            <div class="d-flex align-items-center">
                                <span class="lms-category-dot" style="background-color: <?= $dotColor ?>;"></span>
                                <span class="fw-semibold text-dark fs-6"><?= e($cat['name']) ?></span>
                            </div>
                        </td>
                        <td data-label="Description" class="text-secondary">
                            <?= !empty($cat['description']) ? e($cat['description']) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td data-label="Subcategories">
                            <span class="text-secondary small">
                                <?= (int)$cat['subcat_count'] > 0 ? (int)$cat['subcat_count'] . ' subcategories' : '—' ?>
                            </span>
                        </td>
                        <td data-label="Total Books">
                            <span class="badge rounded-pill bg-light text-secondary border px-3 py-1">
                                <?= pluralize((int)$cat['book_count'], 'book', 'books') ?>
                            </span>
                        </td>
                        <td data-label="Actions" class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="<?= BASE_URL ?>/categories/edit.php?id=<?= (int)$cat['id'] ?>"
                                   class="lms-btn-icon" title="Edit category" aria-label="Edit category <?= e($cat['name']) ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/categories/delete.php?id=<?= (int)$cat['id'] ?>"
                                   class="lms-btn-icon lms-btn-icon--danger"
                                   data-confirm
                                   data-confirm-title="Delete Category?"
                                   data-confirm-message="Category '<?= e($cat['name']) ?>' will be deleted. Books in this category will need reclassifying."
                                   data-confirm-button="Delete category"
                                   data-confirm-style="danger"
                                   title="Delete category" aria-label="Delete category <?= e($cat['name']) ?>">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer -->
    <div class="card-footer d-flex justify-content-between align-items-center py-3">
        <span class="text-muted small">
            <?php if ($isFiltered): ?>
                Showing <?= count($categories) ?> of <?= $totalCategories ?> categories
            <?php else: ?>
                <?= pluralize($totalCategories, 'category', 'categories') ?>
            <?php endif; ?>
        </span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
