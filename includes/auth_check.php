<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

require_once __DIR__ . '/functions.php';

// Every authenticated POST must carry a valid CSRF token.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_is_valid()) {
    $message = empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
        ? 'The upload was larger than the server allows. Please choose a smaller file.'
        : 'Your session expired or the request was invalid. Please try again.';
    setFlash('danger', $message);
    $target = strtok($_SERVER['REQUEST_URI'] ?? '', '#');
    // Never bounce back to a state-changing endpoint; go to its section index.
    $path = (string)parse_url($target, PHP_URL_PATH);
    if (preg_match('#/(delete|return)\.php$#', $path)) {
        $target = rtrim(str_replace('\\', '/', dirname($path)), '/') . '/index.php';
    }
    redirect($target ?: BASE_URL . '/dashboard/index.php');
}
