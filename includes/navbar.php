<?php
$currentPage = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/dashboard/index.php">
      <i class="bi bi-book-half me-2"></i>LibraryMS
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= $currentPage==='dashboard'?'active':'' ?>" href="<?= BASE_URL ?>/dashboard/index.php">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage==='books'?'active':'' ?>" href="<?= BASE_URL ?>/books/index.php">
            <i class="bi bi-book"></i> Books
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage==='members'?'active':'' ?>" href="<?= BASE_URL ?>/members/index.php">
            <i class="bi bi-people"></i> Members
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage==='loans'?'active':'' ?>" href="<?= BASE_URL ?>/loans/index.php">
            <i class="bi bi-arrow-left-right"></i> Loans
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage==='authors'?'active':'' ?>" href="<?= BASE_URL ?>/authors/index.php">
            <i class="bi bi-person-badge"></i> Authors
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage==='categories'?'active':'' ?>" href="<?= BASE_URL ?>/categories/index.php">
            <i class="bi bi-tags"></i> Categories
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> <?= e($_SESSION['user_name'] ?? 'User') ?>
            <?php if (isAdmin()): ?>
              <span class="badge bg-warning text-dark ms-1">Admin</span>
            <?php endif; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text text-muted small"><?= e($_SESSION['user_role'] ?? '') ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
