<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Add Member';
$errors = [];
$old = [];

// Auto-generate unique member number
$defaultMemberNumber = generateMemberNumber($pdo);
$defaultDateJoined = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
        'member_number' => trim($_POST['member_number'] ?? ''),
        'date_joined' => trim($_POST['date_joined'] ?? ''),
    ];

    // Validation
    if ($old['full_name'] === '') {
        $errors['full_name'] = 'Full name is required.';
    }

    if ($old['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    } else {
        $stmt = $pdo->prepare('SELECT member_id FROM members WHERE email = ? LIMIT 1');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email is already registered.';
        }
    }

    if (!in_array($old['status'], ['active', 'suspended'], true)) {
        $old['status'] = 'active';
    }

    if ($old['member_number'] === '') {
        $errors['member_number'] = 'Member number is required.';
    } else {
        $stmt = $pdo->prepare('SELECT member_id FROM members WHERE member_no = ? LIMIT 1');
        $stmt->execute([$old['member_number']]);
        if ($stmt->fetch()) {
            $errors['member_number'] = 'Member number already in use. Please choose another.';
        }
    }

    if ($old['date_joined'] === '') {
        $errors['date_joined'] = 'Date joined is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO members (member_no, full_name, email, phone, status, date_joined)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $old['member_number'],
            $old['full_name'],
            $old['email'],
            $old['phone'] !== '' ? $old['phone'] : null,
            $old['status'],
            $old['date_joined'],
        ]);

        setFlash('success', 'Member "' . e($old['full_name']) . '" added successfully.');
        redirect(BASE_URL . '/members/index.php');
    }
} else {
    $old = [
        'full_name' => '',
        'email' => '',
        'phone' => '',
        'status' => 'active',
        'member_number' => $defaultMemberNumber,
        'date_joined' => $defaultDateJoined,
    ];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Header (Section 6.1 & 7.4) -->
<?= page_header(
    'Add member',
    'Register a new reader and assign library borrowing privileges',
    '<a href="' . BASE_URL . '/members/index.php" class="btn btn-secondary">' .
    '<i class="bi bi-arrow-left me-1"></i>Back to Members' .
    '</a>'
) ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-exclamation-octagon-fill fs-5 text-danger"></i>
            <h6 class="mb-0 fw-bold">Please correct the following errors:</h6>
        </div>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form method="POST" action="" novalidate>
    <div class="row g-4">
        <!-- Card 1: Personal Details -->
        <div class="col-lg-6">
            <div class="card shadow-sm border h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fs-6 mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-person text-primary"></i> Personal Details
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="full_name" class="form-label fw-semibold text-dark">
                            Full Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="full_name" name="full_name"
                            class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                            value="<?= e($old['full_name']) ?>" required autofocus placeholder="e.g. Eleanor Vance">
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold text-dark">
                            Email Address <span class="text-danger">*</span>
                        </label>
                        <input type="email" id="email" name="email"
                            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                            value="<?= e($old['email']) ?>" required placeholder="name@example.com">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold text-dark">
                            Phone Number <span class="text-muted small fw-normal">(optional)</span>
                        </label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?= e($old['phone']) ?>"
                            placeholder="e.g. +1 555-0199">
                        <div class="form-text text-muted">Used for overdue loan notifications.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Membership Particulars -->
        <div class="col-lg-6">
            <div class="card shadow-sm border h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fs-6 mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-card-heading text-primary"></i> Membership Account
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="member_number" class="form-label fw-semibold text-dark">
                            Member Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="member_number" name="member_number"
                            class="form-control <?= isset($errors['member_number']) ? 'is-invalid' : '' ?>"
                            value="<?= e($old['member_number']) ?>" required>
                        <div class="form-text text-muted">Auto-generated format (MEMxxxxx). Editable if needed.</div>
                        <?php if (isset($errors['member_number'])): ?>
                            <div class="invalid-feedback"><?= e($errors['member_number']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark d-block">Account Status</label>
                        <div class="btn-group w-100" role="group" aria-label="Membership status">
                            <input type="radio" class="btn-check" name="status" id="status_active" value="active"
                                <?= $old['status'] === 'active' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-success" for="status_active">
                                <i class="bi bi-check-circle me-1"></i>Active
                            </label>

                            <input type="radio" class="btn-check" name="status" id="status_suspended" value="suspended"
                                <?= $old['status'] === 'suspended' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-danger" for="status_suspended">
                                <i class="bi bi-pause-circle me-1"></i>Suspended
                            </label>
                        </div>
                        <div class="form-text text-muted mt-1">Suspended members cannot borrow books.</div>
                    </div>

                    <div class="mb-3">
                        <label for="date_joined" class="form-label fw-semibold text-dark">
                            Date Joined <span class="text-danger">*</span>
                        </label>
                        <input type="date" id="date_joined" name="date_joined"
                            class="form-control <?= isset($errors['date_joined']) ? 'is-invalid' : '' ?>"
                            value="<?= e($old['date_joined']) ?>" required>
                        <?php if (isset($errors['date_joined'])): ?>
                            <div class="invalid-feedback"><?= e($errors['date_joined']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Action Bar -->
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-end gap-2 p-3 bg-white border rounded shadow-sm">
                <a href="<?= BASE_URL ?>/members/index.php" class="btn btn-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i>Add member
                </button>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>