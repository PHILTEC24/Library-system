<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$search = trim($_GET['search'] ?? '');

$sql = "SELECT a.author_id AS id, a.author_id, a.name, a.country, COUNT(ba.book_id) AS book_count
        FROM authors a
        LEFT JOIN book_authors ba ON ba.author_id = a.author_id";

$params = [];
if ($search !== '') {
    $sql .= " WHERE a.name LIKE ?";
    $params[] = '%' . $search . '%';
}

$sql .= " GROUP BY a.author_id, a.name, a.country ORDER BY a.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Authors';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Authors</h2>
        <a href="<?= BASE_URL ?>/authors/create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Author
        </a>
    </div>

    <?php displayFlash(); ?>

    <form method="GET" action="" class="mb-3">
        <div class="input-group" style="max-width:400px;">
            <input type="text" name="search" class="form-control"
                   placeholder="Search by author name..."
                   value="<?= e($search) ?>">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
            <?php if ($search !== ''): ?>
                <a href="<?= BASE_URL ?>/authors/index.php" class="btn btn-outline-danger">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($authors)): ?>
        <div class="alert alert-info">No authors found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Country</th>
                        <th>Books</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($authors as $i => $author): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($author['name']) ?></td>
                            <td><?= e($author['country'] ?? '—') ?></td>
                            <td><?= (int)$author['book_count'] ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/authors/edit.php?id=<?= (int)$author['id'] ?>"
                                   class="btn btn-sm btn-warning">Edit</a>
                                <a href="<?= BASE_URL ?>/authors/delete.php?id=<?= (int)$author['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this author?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
