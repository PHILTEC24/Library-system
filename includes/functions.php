<?php
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = in_array($flash['type'], ['error', 'danger']) ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'warning');
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo e($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

function flashMessage() {
    displayFlash();
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function validateRequired($fields, $data) {
    foreach ($fields as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            return false;
        }
    }
    return true;
}

function generateMemberNumber($pdo = null) {
    do {
        $number = 'MEM' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        if (!$pdo) {
            return $number;
        }
        $stmt = $pdo->prepare('SELECT member_id FROM members WHERE member_no = ? LIMIT 1');
        $stmt->execute([$number]);
    } while ($stmt->fetch());
    return $number;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}
