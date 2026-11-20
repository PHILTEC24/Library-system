<?php
// Load environment variables from .env.local or .env
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

// Configurable BASE_URL
if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL') ?: ($_ENV['BASE_URL'] ?? null);
    if ($envBase !== null) {
        define('BASE_URL', rtrim($envBase, '/'));
    } else {
        define('BASE_URL', '/library-system');
    }
}

$dbConnection = strtolower(trim(getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? '')));
$databaseUrl  = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null);

$pdo = null;
$errors = [];

// Helper: Connect to MySQL
function _connect_mysql() {
    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
    $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 3306);
    $db   = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? 'library_db');
    $user = getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? 'root');
    $pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ($_ENV['DB_PASSWORD'] ?? '');

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

// Helper: Connect to PostgreSQL (Neon)
function _connect_pgsql_url($urlStr) {
    if (!extension_loaded('pdo_pgsql')) {
        throw new PDOException("PHP extension 'pdo_pgsql' is not loaded in this environment. Enable it in php.ini to connect to Neon PostgreSQL.");
    }
    $url = parse_url($urlStr);
    $host = $url['host'] ?? 'localhost';
    $port = $url['port'] ?? 5432;
    $user = $url['user'] ?? '';
    $pass = $url['pass'] ?? '';
    $db   = ltrim($url['path'] ?? '', '/');

    $sslmode = 'require';
    if (!empty($url['query'])) {
        parse_str($url['query'], $query);
        if (!empty($query['sslmode'])) {
            $sslmode = $query['sslmode'];
        }
    }

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=$sslmode";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

// Helper: Connect to MySQL from URL
function _connect_mysql_url($urlStr) {
    $url = parse_url($urlStr);
    $host = $url['host'] ?? 'localhost';
    $port = $url['port'] ?? 3306;
    $user = $url['user'] ?? 'root';
    $pass = $url['pass'] ?? '';
    $db   = ltrim($url['path'] ?? '', '/');

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

// 1. Explicit MySQL connection requested
if ($dbConnection === 'mysql') {
    try {
        if ($databaseUrl && str_starts_with($databaseUrl, 'mysql://')) {
            $pdo = _connect_mysql_url($databaseUrl);
        } else {
            $pdo = _connect_mysql();
        }
    } catch (PDOException $e) {
        $errors[] = "MySQL: " . $e->getMessage();
    }
}
// 2. Explicit Postgres connection requested or DATABASE_URL provided
elseif ($databaseUrl) {
    if (str_starts_with($databaseUrl, 'postgres://') || str_starts_with($databaseUrl, 'postgresql://')) {
        try {
            $pdo = _connect_pgsql_url($databaseUrl);
        } catch (PDOException $e) {
            $errors[] = "PostgreSQL (Neon): " . $e->getMessage();
            // If running on local server/XAMPP and Neon fails (e.g., offline or missing pdo_pgsql), try local MySQL fallback
            try {
                $pdo = _connect_mysql();
            } catch (PDOException $e2) {
                $errors[] = "MySQL fallback: " . $e2->getMessage();
            }
        }
    } elseif (str_starts_with($databaseUrl, 'mysql://')) {
        try {
            $pdo = _connect_mysql_url($databaseUrl);
        } catch (PDOException $e) {
            $errors[] = "MySQL (URL): " . $e->getMessage();
        }
    }
}
// 3. Default to local MySQL (e.g. standard XAMPP environment)
else {
    try {
        $pdo = _connect_mysql();
    } catch (PDOException $e) {
        $errors[] = "MySQL: " . $e->getMessage();
    }
}

if (!$pdo) {
    error_log("Database connection failed. Errors: " . implode(" | ", $errors));
    $errorDetails = implode('<br>', array_map('htmlspecialchars', $errors));
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Database Connection Error - Library Management System</title>
        <link rel="stylesheet" href="' . BASE_URL . '/assets/css/style.css">
    </head>
    <body class="p-5 bg-light">
        <div class="container" style="max-width: 640px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <h2 style="color: #dc3545;">Database Connection Failed</h2>
            <p>Could not connect to the database. Please check your configuration:</p>
            <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 12px; border-radius: 6px; font-family: monospace; font-size: 13px; color: #333; margin-bottom: 20px;">
                ' . $errorDetails . '
            </div>
            <h5>Troubleshooting:</h5>
            <ul style="font-size: 14px; color: #555;">
                <li><strong>For Localhost / XAMPP:</strong> Ensure Apache and MySQL are started in the XAMPP Control Panel, and import <code>database/library_db.sql</code> into phpMyAdmin.</li>
                <li><strong>For Neon Cloud Database:</strong> Check your internet connection and ensure <code>DATABASE_URL</code> is set in <code>.env.local</code> or environment variables. Also make sure the <code>pdo_pgsql</code> extension is enabled in your PHP installation.</li>
            </ul>
        </div>
    </body>
    </html>
    ');
}
