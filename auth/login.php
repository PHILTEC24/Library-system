<?php
define('BASE_URL', '/library-system');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Wrong username or password.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id AS id, username, password_hash, full_name, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username']  = $user['username'];
            setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
            header('Location: ' . BASE_URL . '/dashboard/index.php');
            exit;
        } else {
            // Section 7.1: "Wrong username or password." (never say which one)
            $error = 'Wrong username or password.';
        }
    }
}

$pageTitle = 'Sign in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in | LibraryMS</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">

    <!-- Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.2 & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="lms-login-wrapper">
    <!-- Left Hero: Iconic Brand Presentation -->
    <aside class="lms-login-hero">
        <div class="lms-login-hero__brand" aria-label="LibraryMS">
            <span class="lms-sidebar__brand-icon"><i class="bi bi-journal-bookmark-fill"></i></span>
            Library<span style="color:#10B981">MS</span>
        </div>

        <div class="lms-login-hero__content">
            <h1 class="lms-login-hero__title">Your Library.<br>Fully Modernised.</h1>
            <p class="lms-login-hero__desc">
                The circulation and catalogue management system built for academic and public collections.
            </p>

            <!-- Feature Points with Clean Icons (Zero generated images) -->
            <div class="lms-login-features">
                <div class="lms-login-feature-item">
                    <i class="bi bi-book-fill"></i>
                    <span>Live catalogue with spine tiles and availability meters</span>
                </div>
                <div class="lms-login-feature-item">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>2-click circulation — issue, track, and return books</span>
                </div>
                <div class="lms-login-feature-item">
                    <i class="bi bi-shield-check"></i>
                    <span>Automated overdue alerts and suspension safeguards</span>
                </div>
                <div class="lms-login-feature-item">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Category loan analytics and member activity reports</span>
                </div>
            </div>
        </div>

        <div class="text-muted small" style="color: rgba(255,255,255,0.4) !important;">
            &copy; <?= date('Y') ?> LibraryMS &mdash; Staff Portal
        </div>
    </aside>

    <!-- Right Panel: Centered Sign-in Form -->
    <main class="lms-login-panel">
        <div class="lms-login-form-container">
            <div class="mb-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="lms-sidebar__brand-icon" style="width:48px;height:48px;font-size:1.5rem;"><i class="bi bi-journal-bookmark-fill"></i></span>
                    <span style="font-family:var(--lms-font-display);font-size:1.5rem;font-weight:800;color:var(--lms-text);">Library<span style="color:var(--lms-primary)">MS</span></span>
                </div>
                <h2 class="lms-login-form-title mb-1">Welcome back</h2>
                <p class="text-muted small mb-0">Sign in to your librarian account to continue.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text" id="username" name="username" class="form-control border-start-0 ps-0"
                               value="<?= e($_POST['username'] ?? '') ?>"
                               required autofocus autocomplete="username"
                               placeholder="e.g. admin">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" class="form-control border-start-0 border-end-0 px-0"
                               required autocomplete="current-password"
                               placeholder="••••••••">
                        <button class="btn btn-outline-secondary border-start-0 bg-white text-muted" type="button"
                                id="lms-toggle-password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
                </button>
            </form>

            <div class="mt-4 pt-3 border-top border-light-subtle text-center text-muted small">
                <span class="badge bg-light text-secondary border px-3 py-2 fw-medium">
                    <i class="bi bi-key me-1 text-warning"></i> Demo credentials: <strong>admin</strong> / <strong>password</strong>
                </span>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
