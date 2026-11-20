<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (str_starts_with($uri, '/library-system')) {
    $uri = substr($uri, strlen('/library-system'));
    if ($uri === '' || $uri === '/') {
        $uri = '/index.php';
    }
}

$path = __DIR__ . $uri;

if (is_file($path)) {
    if (str_ends_with($path, '.php')) {
        require $path;
        return true;
    }
    return false; // Serve static file as-is
}

if (is_dir($path) && is_file(rtrim($path, '/') . '/index.php')) {
    require rtrim($path, '/') . '/index.php';
    return true;
}

require __DIR__ . '/index.php';
