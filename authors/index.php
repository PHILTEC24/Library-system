<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Authors';
$search = trim($_GET['search'] ?? '');

$totalAuthors = (int)$pdo->query('SELECT COUNT(*) FROM authors')->fetchColumn();

$sql = "SELECT a.author_id AS id, a.author_id, a.name, a.country, COUNT(ba.book_id) AS book_count
        FROM authors a
        LEFT JOIN book_authors ba ON ba.author_id = a.author_id";

$params = [];
if ($search !== '') {
    $sql .= " WHERE a.name LIKE ? OR a.country LIKE ?";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " GROUP BY a.author_id, a.name, a.country ORDER BY a.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isFiltered = ($search !== '');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Header (Section 6.1 & 7.6) -->
<?= page_header(
    'Authors',
    $totalAuthors . ' registered authors in catalogue',
    '<a href="' . BASE_URL . '/authors/create.php" class="btn btn-primary">' .
        '<i class="bi bi-person-plus-fill me-1"></i>Add author' .
    '</a>'
) ?>

<?php displayFlash(); ?>

<!-- Table & Filter Card -->
<div class="lms-table-card mb-4">
    <!-- Filter Bar (Section 6.3 & 7.6) -->
    <div class="lms-filter-bar">
        <form method="GET" action="<?= BASE_URL ?>/authors/index.php" class="lms-auto-filter">
            <div class="lms-filter-bar__row">
                <div class="lms-filter-bar__inputs">
                    <div class="lms-filter-input-wrap">
                        <i class="bi bi-search"></i>
                        <input type="search" name="search" class="form-control"
                               placeholder="Search author name or country..."
                               value="<?= e($search) ?>" aria-label="Search authors">
                    </div>

                    <noscript>
                        <button type="submit" class="btn btn-secondary">Search</button>
                    </noscript>
                </div>

                <div class="text-muted small">
                    <?php if ($isFiltered): ?>
                        Showing <?= count($authors) ?> of <?= $totalAuthors ?> authors
                    <?php else: ?>
                        <?= pluralize($totalAuthors, 'author', 'authors') ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isFiltered): ?>
                <div class="lms-filter-chips mt-2 pt-2 border-top border-light-subtle">
                    <span class="text-muted">Active filters:</span>
                    <span class="lms-chip">
                        <span>"<?= e($search) ?>"</span>
                        <a href="<?= BASE_URL ?>/authors/index.php" class="lms-chip__remove" aria-label="Remove search filter">&times;</a>
                    </span>
                    <a href="<?= BASE_URL ?>/authors/index.php" class="text-decoration-none small ms-2 text-danger">Clear filters</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Data Table (Section 6.4 & 7.6) -->
    <div class="table-responsive">
        <table class="lms-table lms-table-stacked align-middle mb-0">
            <caption class="visually-hidden">Registered authors list with countries and associated book counts</caption>
            <thead>
                <tr>
                    <th scope="col" style="min-width: 280px;">Author</th>
                    <th scope="col" style="width: 220px;">Country</th>
                    <th scope="col" style="width: 160px;">Books in Catalogue</th>
                    <th scope="col" class="text-end" style="width: 110px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($authors)): ?>
                <tr>
                    <td colspan="4" class="p-0 border-0">
                        <div class="lms-empty-state">
                            <div class="lms-empty-state__icon">
                                <i class="bi bi-person-badge fs-1 text-muted"></i>
                            </div>
                            <?php if ($isFiltered): ?>
                                <h2 class="lms-empty-state__title">No authors match "<?= e($search) ?>"</h2>
                                <p class="lms-empty-state__description">Try searching with a different spelling or clear the filter.</p>
                                <a href="<?= BASE_URL ?>/authors/index.php" class="btn btn-secondary">Clear filter</a>
                            <?php else: ?>
                                <h2 class="lms-empty-state__title">No authors registered yet</h2>
                                <p class="lms-empty-state__description">Add authors to link them to books and co-authorships in the catalogue.</p>
                                <a href="<?= BASE_URL ?>/authors/create.php" class="btn btn-primary">
                                    <i class="bi bi-person-plus-fill me-1"></i>Add first author
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($authors as $author): ?>
                    <tr>
                        <td data-label="Author">
                            <div class="d-flex align-items-center gap-3">
                                <?= avatar($author['name'], 38) ?>
                                <span class="fw-semibold text-dark fs-6"><?= e($author['name']) ?></span>
                            </div>
                        </td>
                        <td data-label="Country">
                            <?php if (!empty($author['country'])): ?>
                                <span class="text-dark"><i class="bi bi-geo-alt me-1 text-muted"></i><?= e($author['country']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Books">
                            <span class="badge rounded-pill bg-light text-secondary border px-3 py-1">
                                <?= pluralize((int)$author['book_count'], 'book', 'books') ?>
                            </span>
                        </td>
                        <td data-label="Actions" class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="<?= BASE_URL ?>/authors/edit.php?id=<?= (int)$author['id'] ?>"
                                   class="lms-btn-icon" title="Edit author" aria-label="Edit author <?= e($author['name']) ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/authors/delete.php?id=<?= (int)$author['id'] ?>"
                                   class="lms-btn-icon lms-btn-icon--danger"
                                   data-confirm
                                   data-confirm-title="Delete Author?"
                                   data-confirm-message="Author '<?= e($author['name']) ?>' will be removed. Books linked to this author will remain in the catalogue."
                                   data-confirm-button="Delete author"
                                   data-confirm-style="danger"
                                   title="Delete author" aria-label="Delete author <?= e($author['name']) ?>">
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
                Showing <?= count($authors) ?> of <?= $totalAuthors ?> authors
            <?php else: ?>
                <?= pluralize($totalAuthors, 'author', 'authors') ?>
            <?php endif; ?>
        </span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
