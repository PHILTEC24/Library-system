<?php
$currentPage = basename(dirname($_SERVER['PHP_SELF']));
$currentFile = basename($_SERVER['PHP_SELF']);

global $pdo;
$overdueNavCount = 0;
if (isset($pdo)) {
    try {
        $overdueNavCount = (int)$pdo->query("SELECT COUNT(*) FROM loans WHERE date_returned IS NULL AND due_date < CURDATE()")->fetchColumn();
    } catch (Exception $e) {}
}

$userName = $_SESSION['user_name'] ?? 'Librarian';
$userRole = $_SESSION['user_role'] ?? 'librarian';
$userIsAdmin = is_admin();

// Sidebar Navigation Content Helper
$renderNavItems = function() use ($currentPage, $overdueNavCount) {
?>
    <p class="lms-sidebar__group-label">Overview</p>
    <ul class="lms-sidebar__nav-list">
        <li>
            <a class="lms-nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>/dashboard/index.php" <?= $currentPage === 'dashboard' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
    </ul>

    <p class="lms-sidebar__group-label">Catalogue</p>
    <ul class="lms-sidebar__nav-list">
        <li>
            <a class="lms-nav-link <?= $currentPage === 'books' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>/books/index.php" <?= $currentPage === 'books' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-book"></i>
                <span>Books</span>
            </a>
        </li>
        <li>
            <a class="lms-nav-link <?= $currentPage === 'authors' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>/authors/index.php" <?= $currentPage === 'authors' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-person-badge"></i>
                <span>Authors</span>
            </a>
        </li>
        <li>
            <a class="lms-nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>/categories/index.php" <?= $currentPage === 'categories' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-tags"></i>
                <span>Categories</span>
            </a>
        </li>
    </ul>

    <p class="lms-sidebar__group-label">Circulation</p>
    <ul class="lms-sidebar__nav-list">
        <li>
            <a class="lms-nav-link <?= $currentPage === 'loans' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>/loans/index.php" <?= $currentPage === 'loans' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-arrow-left-right"></i>
                <span class="flex-grow-1">Loans</span>
                <?php if ($overdueNavCount > 0): ?>
                    <span class="badge bg-danger rounded-pill" title="<?= $overdueNavCount ?> overdue loan(s)"><?= $overdueNavCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a class="lms-nav-link <?= $currentPage === 'members' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>/members/index.php" <?= $currentPage === 'members' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-people"></i>
                <span>Members</span>
            </a>
        </li>
    </ul>
<?php
};
?>

<!-- Desktop Sidebar -->
<aside class="lms-sidebar" aria-label="Main Navigation">
    <a href="<?= BASE_URL ?>/dashboard/index.php" class="lms-sidebar__brand">
        <span class="lms-sidebar__brand-icon">
            <i class="bi bi-journal-bookmark-fill"></i>
        </span>
        <span class="lms-sidebar__brand-name">Library<span>MS</span></span>
    </a>

    <nav class="lms-sidebar__nav">
        <?php $renderNavItems(); ?>
    </nav>

    <div class="lms-sidebar__footer">
        <div class="lms-user-card">
            <?= avatar($userName, 36) ?>
            <div class="lms-user-card__info">
                <div class="lms-user-card__name" title="<?= e($userName) ?>"><?= e($userName) ?></div>
                <span class="lms-user-card__role <?= $userIsAdmin ? 'lms-user-card__role--admin' : 'lms-user-card__role--librarian' ?>">
                    <?= $userIsAdmin ? 'Admin' : 'Librarian' ?>
                </span>
            </div>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="lms-sidebar__logout" title="Sign out" aria-label="Sign out">
                <i class="bi bi-box-arrow-right fs-6"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start lms-offcanvas-sidebar d-lg-none" tabindex="-1" id="lmsMobileSidebar" aria-labelledby="lmsMobileSidebarLabel">
    <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-2">
            <span class="lms-sidebar__brand-icon">
                <i class="bi bi-journal-bookmark-fill"></i>
            </span>
            <h5 class="offcanvas-title lms-sidebar__brand-name mb-0" id="lmsMobileSidebarLabel">Library<span>MS</span></h5>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column">
        <nav class="lms-sidebar__nav flex-grow-1">
            <?php $renderNavItems(); ?>
        </nav>
        <div class="lms-sidebar__footer">
            <div class="lms-user-card">
                <?= avatar($userName, 36) ?>
                <div class="lms-user-card__info">
                    <div class="lms-user-card__name"><?= e($userName) ?></div>
                    <span class="lms-user-card__role <?= $userIsAdmin ? 'lms-user-card__role--admin' : 'lms-user-card__role--librarian' ?>">
                        <?= $userIsAdmin ? 'Admin' : 'Librarian' ?>
                    </span>
                </div>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="lms-sidebar__logout" title="Sign out" aria-label="Sign out">
                    <i class="bi bi-box-arrow-right fs-6"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main App Wrapper -->
<div class="lms-main-wrapper">
    <!-- Slim Top Bar (64px) -->
    <header class="lms-topbar">
        <div class="d-flex align-items-center gap-2">
            <!-- Mobile Offcanvas Hamburger -->
            <button class="btn btn-outline-secondary d-lg-none lms-btn-icon" type="button" data-bs-toggle="offcanvas" data-bs-target="#lmsMobileSidebar" aria-label="Open sidebar navigation">
                <i class="bi bi-list fs-5"></i>
            </button>

            <!-- Breadcrumbs with Home Icon -->
            <nav class="lms-breadcrumb" aria-label="Breadcrumb">
                <a href="<?= BASE_URL ?>/dashboard/index.php" class="d-flex align-items-center text-decoration-none">
                    <i class="bi bi-house-door me-1"></i>
                </a>
                <span class="lms-breadcrumb__separator">/</span>
                <?php
                if ($currentPage === 'dashboard') {
                    echo '<span class="lms-breadcrumb__current">Dashboard</span>';
                } else {
                    $sectionLabel = ucfirst($currentPage);
                    $indexUrl = BASE_URL . '/' . $currentPage . '/index.php';

                    if ($currentFile === 'index.php') {
                        echo '<span class="lms-breadcrumb__current">' . e($sectionLabel) . '</span>';
                    } else {
                        echo '<a href="' . $indexUrl . '">' . e($sectionLabel) . '</a>';
                        echo '<span class="lms-breadcrumb__separator">/</span>';
                        $subLabel = isset($pageTitle) ? $pageTitle : ucfirst(pathinfo($currentFile, PATHINFO_FILENAME));
                        echo '<span class="lms-breadcrumb__current">' . e($subLabel) . '</span>';
                    }
                }
                ?>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Global Search Input (submits to books/index.php) -->
            <form action="<?= BASE_URL ?>/books/index.php" method="GET" class="lms-topbar__search d-none d-md-block" role="search">
                <i class="bi bi-search lms-topbar__search-icon"></i>
                <input type="search" name="search" class="form-control lms-topbar__search-input" placeholder="Search books by title, author..." aria-label="Search books">
            </form>

            <!-- Quick Action: Issue Book -->
            <a href="<?= BASE_URL ?>/loans/issue.php" class="btn btn-primary btn-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i>
                <span>Issue book</span>
            </a>
        </div>
    </header>

    <!-- Main Content Landmark -->
    <main id="main" class="lms-content-container">
