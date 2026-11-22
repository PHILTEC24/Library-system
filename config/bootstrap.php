<?php
// config/bootstrap.php - Environment loader and BASE_URL dynamic auto-detection

// 1. Load environment variables from .env.local or .env if present
$envFiles = [__DIR__ . '/../.env.local', __DIR__ . '/../.env'];
foreach ($envFiles as $file) {
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (strpos($line, '=') !== false) {
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, " \t\n\r\0\x0B\"'");
                if (!array_key_exists($k, $_ENV) && getenv($k) === false) {
                    putenv("$k=$v");
                    $_ENV[$k] = $v;
                }
            }
        }
    }
}

// 2. Application timezone. Due dates, overdue counts and the dashboard
//    greeting all depend on "today", so don't rely on whatever php.ini says.
$appTimezone = getenv('APP_TIMEZONE') ?: ($_ENV['APP_TIMEZONE'] ?? 'Africa/Kampala');
if (!@date_default_timezone_set($appTimezone)) {
    date_default_timezone_set('Africa/Kampala');
}

// 3. Auto-detect BASE_URL
if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL');
    if ($envBase === false && isset($_ENV['BASE_URL'])) {
        $envBase = $_ENV['BASE_URL'];
    }

    if ($envBase !== false && $envBase !== null) {
        define('BASE_URL', rtrim($envBase, '/'));
    } else {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($script));
        if ($dir === '/' || $dir === '.' || $dir === '') {
            define('BASE_URL', '');
        } else {
            $subdirs = ['auth', 'books', 'members', 'loans', 'authors', 'categories', 'dashboard', 'includes', 'docs', 'database', 'config'];
            $parts = explode('/', trim($dir, '/'));
            if (!empty($parts) && in_array(end($parts), $subdirs)) {
                array_pop($parts);
            }
            define('BASE_URL', empty($parts) ? '' : '/' . implode('/', $parts));
        }
    }
}
