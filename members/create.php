<?php
define('BASE_URL', '/library-system');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$old    = [];

// Auto-generate a unique member number using shared helper
$defaultMemberNumber = generateMemberNumber($pdo);
$defaultDateJoined   = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'full_name'     => trim($_POST['full_name']     ?? ''),
        'email'         => trim($_POST['email']          ?? ''),
        'phone'         => trim($_POST['phone']          ?? ''),
        'status'        => $_POST['status']              ?? 'active',
        'member_number' => trim($_POST['member_number']  ?? ''),
        'date_joined'   => trim($_POST['date_joined']    ?? ''),
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

        setFlash('success', 'Member added successfully.');
        header('Location: ' . BASE_URL . '/members/index.php');
        exit;
    }
} else {
    $old = [
        'full_name'     => '',
        'email'         => '',
        'phone'         => '',
        'status'        => 'active',
        'member_number' => $defaultMemberNumber,
        'date_joined'   => $defaultDateJoined,
    ];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4" style="max-width:720px;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Add Member</h2>
    <a href="<?= BASE_URL ?>/members/index.php" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <strong>Please fix the following errors:</strong>
      <ul class="mb-0 mt-1">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body p-4">
      <form method="POST" action="" novalidate>

        <!-- Full Name -->
        <div class="mb-3">
          <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
          <input type="text" id="full_name" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                 value="<?= e($old['full_name']) ?>" required>
          <?php if (isset($errors['full_name'])): ?>
            <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Email -->
        <div class="mb-3">
          <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
          <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                 value="<?= e($old['email']) ?>" required>
          <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Phone -->
        <div class="mb-3">
          <label for="phone" class="form-label fw-semibold">Phone <span class="text-muted small">(optional)</span></label>
          <input type="text" id="phone" name="phone" class="form-control"
                 value="<?= e($old['phone']) ?>">
        </div>

        <!-- Status -->
        <div class="mb-3">
          <label for="status" class="form-label fw-semibold">Status</label>
          <select id="status" name="status" class="form-select">
            <option value="active"    <?= $old['status'] === 'active'    ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= $old['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
          </select>
        </div>

        <!-- Member Number -->
        <div class="mb-3">
          <label for="member_number" class="form-label fw-semibold">
            Member Number <span class="text-danger">*</span>
            <span class="text-muted small ms-1">(auto-generated, editable)</span>
          </label>
          <input type="text" id="member_number" name="member_number"
                 class="form-control <?= isset($errors['member_number']) ? 'is-invalid' : '' ?>"
                 value="<?= e($old['member_number']) ?>">
          <?php if (isset($errors['member_number'])): ?>
            <div class="invalid-feedback"><?= e($errors['member_number']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Date Joined -->
        <div class="mb-4">
          <label for="date_joined" class="form-label fw-semibold">Date Joined <span class="text-danger">*</span></label>
          <input type="date" id="date_joined" name="date_joined"
                 class="form-control <?= isset($errors['date_joined']) ? 'is-invalid' : '' ?>"
                 value="<?= e($old['date_joined']) ?>">
          <?php if (isset($errors['date_joined'])): ?>
            <div class="invalid-feedback"><?= e($errors['date_joined']) ?></div>
          <?php endif; ?>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Save Member
          </button>
          <a href="<?= BASE_URL ?>/members/index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
