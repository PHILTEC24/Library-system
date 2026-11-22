<?php
require_once __DIR__ . '/../config/bootstrap.php';
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
    $password = (string)($_POST['password'] ?? '');

    if (!csrf_is_valid()) {
        $error = 'Your session expired. Please sign in again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter both your username and password.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT user_id AS id, user_id, username, password_hash, full_name, role FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $stmt = $pdo->prepare('SELECT id, username, password_hash, full_name, role FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
        }

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

    <!-- Immediate theme check to prevent flash of unstyled content -->
    <script>
        (function() {
            try {
                const saved = localStorage.getItem('lms-theme');
                const theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.setAttribute('data-bs-theme', theme);
            } catch (e) {}
        })();
    </script>

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
    <!-- ===== Left Hero Panel ===== -->
    <aside class="lms-login-hero">
        <div class="lms-login-hero__brand">
            <span class="lms-sidebar__brand-icon" style="width:44px;height:44px;font-size:1.3rem;">
                <i class="bi bi-journal-bookmark-fill"></i>
            </span>
            <span>Library<span style="color:#10B981">MS</span></span>
        </div>

        <div class="lms-login-hero__content">
            <h1 class="lms-login-hero__title">Your Library.<br>Fully&nbsp;Modernised.</h1>
            <p class="lms-login-hero__desc">
                The circulation and catalogue management system built for academic and public collections.
            </p>

            <!-- Feature Points with Clean Icons (Zero generated images) -->
            <div class="lms-login-features d-flex flex-column gap-3">
                <div class="lms-login-feature-item">
                    <span class="lms-login-feature-icon"><i class="bi bi-book-fill"></i></span>
                    <div>
                        <div class="fw-semibold text-white">Realistic Spine Catalogue</div>
                        <div class="small opacity-75">Real-time shelf inventory, 3D book spines, and instant copy availability.</div>
                    </div>
                </div>
                <div class="lms-login-feature-item">
                    <span class="lms-login-feature-icon"><i class="bi bi-arrow-left-right"></i></span>
                    <div>
                        <div class="fw-semibold text-white">2-Click Fast Circulation</div>
                        <div class="small opacity-75">Streamlined issue desk with automatic 14-day calculation and one-click returns.</div>
                    </div>
                </div>
                <div class="lms-login-feature-item">
                    <span class="lms-login-feature-icon"><i class="bi bi-shield-check"></i></span>
                    <div>
                        <div class="fw-semibold text-white">Overdue & Policy Rules</div>
                        <div class="small opacity-75">Automated suspension safeguards and transactional inventory protection.</div>
                    </div>
                </div>
                <div class="lms-login-feature-item">
                    <span class="lms-login-feature-icon"><i class="bi bi-graph-up-arrow"></i></span>
                    <div>
                        <div class="fw-semibold text-white">Borrowing Analytics</div>
                        <div class="small opacity-75">Real-time category breakdown, active member metrics, and loan trends.</div>
                    </div>
                </div>
            </div>

            <!-- Hero Stats Row -->
            <div class="lms-login-stats">
                <div class="lms-login-stat">
                    <span class="lms-login-stat__num">100%</span>
                    <span class="lms-login-stat__lbl">Live Sync</span>
                </div>
                <div class="lms-login-stat">
                    <span class="lms-login-stat__num">14-Day</span>
                    <span class="lms-login-stat__lbl">Policy Engine</span>
                </div>
                <div class="lms-login-stat">
                    <span class="lms-login-stat__num">Zero</span>
                    <span class="lms-login-stat__lbl">Clutter</span>
                </div>
            </div>
        </div>

        <div class="text-white-50 small">
            &copy; <?= date('Y') ?> LibraryMS &mdash; Modern Library Management System
        </div>
    </aside>

    <!-- ===== Right Panel: Centered Sign-in Form ===== -->
    <main class="lms-login-panel">
        <div class="lms-login-form-container">
            <!-- Top bar with Theme Toggle -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-2 d-lg-none">
                    <span class="lms-sidebar__brand-icon" style="width:36px;height:36px;font-size:1.1rem;">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </span>
                    <span class="fw-bold fs-5">Library<span class="text-primary">MS</span></span>
                </div>
                <div class="ms-auto">
                    <button class="lms-theme-toggle" type="button" aria-label="Toggle dark / light mode">
                        <i class="bi bi-moon-stars icon-moon"></i>
                        <i class="bi bi-sun-fill icon-sun"></i>
                    </button>
                </div>
            </div>

            <div class="lms-login-form-card">
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="lms-sidebar__brand-icon" style="width:48px;height:48px;font-size:1.5rem;">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </span>
                        <div>
                            <h2 class="lms-title h4 mb-0">Sign in</h2>
                            <p class="text-muted small mb-0">Staff circulation & catalogue portal</p>
                        </div>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-3" role="alert">
                        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                        <span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate id="loginForm">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-muted">
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
                            <span class="input-group-text bg-transparent border-end-0 text-muted">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" id="password" name="password" class="form-control border-start-0 border-end-0 px-0"
                                   required autocomplete="current-password"
                                   placeholder="••••••••">
                            <button class="btn btn-outline-secondary border-start-0 bg-transparent text-muted" type="button"
                                    id="lms-toggle-password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
                    </button>
                </form>

                <!-- Click-to-fill demo credentials badge -->
                <div class="mt-4 pt-3 border-top text-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 lms-demo-fill-btn" id="lmsDemoFill" title="Click to autofill demo credentials">
                        <i class="bi bi-key-fill text-warning me-1"></i>
                        Demo: <strong class="text-primary">admin</strong> / <strong>password</strong>
                        <span class="badge bg-primary-subtle text-primary ms-1">auto-fill</span>
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
