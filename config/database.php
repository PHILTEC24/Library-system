<?php
require_once __DIR__ . '/bootstrap.php';

// Helper to look up environment variable from multiple possible names
function _get_db_env(array $names, $default = null) {
    foreach ($names as $name) {
        $val = getenv($name);
        if ($val !== false && $val !== '') {
            return $val;
        }
        if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
            return $_ENV[$name];
        }
    }
    return $default;
}

$dbConnection = strtolower(trim(_get_db_env(['DB_CONNECTION'], '')));
$databaseUrl  = _get_db_env(['MYSQL_URL', 'MYSQL_PUBLIC_URL', 'DATABASE_URL', 'DATABASE_URL_UNPOOLED', 'JAWSDB_URL', 'CLEARDB_DATABASE_URL']);

$pdo = null;
$errors = [];
$activeDriver = 'none';

// Helper: Connect to MySQL via host/port/credentials
function _connect_mysql_creds() {
    $host = _get_db_env(['MYSQLHOST', 'DB_HOST', 'MYSQL_HOST'], 'localhost');
    $port = _get_db_env(['MYSQLPORT', 'DB_PORT', 'MYSQL_PORT'], 3306);
    $db   = _get_db_env(['MYSQLDATABASE', 'DB_DATABASE', 'MYSQL_DATABASE'], 'library_db');
    $user = _get_db_env(['MYSQLUSER', 'DB_USERNAME', 'MYSQL_USER', 'DB_USER'], 'root');
    $pass = _get_db_env(['MYSQLPASSWORD', 'DB_PASSWORD', 'MYSQL_PASSWORD'], '');

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ]);

    _init_mysql_schema_if_needed($conn);
    return $conn;
}

// Helper: Connect to MySQL via URL (e.g. mysql://user:pass@host:port/db)
function _connect_mysql_url_dsn($urlStr) {
    $url = parse_url($urlStr);
    $host = $url['host'] ?? 'localhost';
    $port = $url['port'] ?? 3306;
    $user = isset($url['user']) ? urldecode($url['user']) : 'root';
    $pass = isset($url['pass']) ? urldecode($url['pass']) : '';
    $db   = isset($url['path']) ? ltrim($url['path'], '/') : 'library_db';

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ]);

    _init_mysql_schema_if_needed($conn);
    return $conn;
}

// Helper: Auto-initialize MySQL tables from library_db.sql if fresh database
function _init_mysql_schema_if_needed(PDO $conn) {
    try {
        $hasTables = $conn->query("SHOW TABLES LIKE 'users'")->fetchColumn();
        if (!$hasTables) {
            $sqlFile = __DIR__ . '/../database/library_db.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                // Strip database-level commands so statements run in current connected DB
                $sql = preg_replace('/^\s*DROP\s+DATABASE\s+[^;]+;/mi', '', $sql);
                $sql = preg_replace('/^\s*CREATE\s+DATABASE\s+[^;]+;/mi', '', $sql);
                $sql = preg_replace('/^\s*USE\s+[^;]+;/mi', '', $sql);
                $conn->exec($sql);
            }
        }
    } catch (Exception $e) {
        error_log("Notice: MySQL schema initialization check: " . $e->getMessage());
    }
}

// Helper: Connect to PostgreSQL (e.g. Neon or Railway PostgreSQL)
function _connect_pgsql_url($urlStr) {
    if (!extension_loaded('pdo_pgsql')) {
        throw new PDOException("PHP extension 'pdo_pgsql' is not loaded in this environment.");
    }
    $url = parse_url($urlStr);
    $host = $url['host'] ?? 'localhost';
    $port = $url['port'] ?? 5432;
    $user = isset($url['user']) ? urldecode($url['user']) : '';
    $pass = isset($url['pass']) ? urldecode($url['pass']) : '';
    $db   = isset($url['path']) ? ltrim($url['path'], '/') : '';

    $sslmode = 'require';
    if (!empty($url['query'])) {
        parse_str($url['query'], $query);
        if (!empty($query['sslmode'])) {
            $sslmode = $query['sslmode'];
        }
    }

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=$sslmode";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Check if tables exist, otherwise run migration
    try {
        $check = $conn->query("SELECT to_regclass('public.users')")->fetchColumn();
        if (!$check) {
            $migrator = __DIR__ . '/../database/migrate_neon.php';
            if (file_exists($migrator)) {
                // Compatibility functions and schema
                $conn->exec("
                    CREATE OR REPLACE FUNCTION CURDATE() RETURNS date AS $$ SELECT CURRENT_DATE; $$ LANGUAGE SQL STABLE;
                    CREATE OR REPLACE FUNCTION DATEDIFF(date1 date, date2 date) RETURNS integer AS $$ SELECT (date1 - date2)::integer; $$ LANGUAGE SQL IMMUTABLE;
                    CREATE OR REPLACE FUNCTION LEAST(VARIADIC arr anyarray) RETURNS anyelement AS $$ SELECT min(x) FROM unnest(arr) g(x); $$ LANGUAGE SQL IMMUTABLE;
                ");
            }
        }
    } catch (Exception $e) {
        error_log("Notice: PgSQL schema check: " . $e->getMessage());
    }

    return $conn;
}

// Helper: Connect to SQLite fallback
function _connect_sqlite() {
    $dbDir = __DIR__ . '/../database';
    if (!is_dir($dbDir)) {
        @mkdir($dbDir, 0777, true);
    }
    $dbPath = $dbDir . '/library.sqlite';
    $conn = new PDO("sqlite:$dbPath", null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // MySQL compatibility functions
    $conn->sqliteCreateFunction('CURDATE', fn() => date('Y-m-d'));
    $conn->sqliteCreateFunction('DATEDIFF', function($d1, $d2) {
        $t1 = strtotime($d1);
        $t2 = strtotime($d2);
        if ($t1 === false || $t2 === false) return 0;
        return (int)round(($t1 - $t2) / 86400);
    });
    $conn->sqliteCreateFunction('LEAST', fn(...$args) => count($args) ? min($args) : 0);

    require_once __DIR__ . '/../database/init_sqlite.php';
    init_sqlite_database($conn);
    return $conn;
}

// 1. If explicit MySQL requested or Railway MySQL variables present
$hasRailwayMysql = _get_db_env(['MYSQLHOST', 'MYSQL_URL', 'MYSQL_PUBLIC_URL']) !== null;

if ($dbConnection === 'mysql' || $hasRailwayMysql) {
    try {
        if ($databaseUrl && str_starts_with($databaseUrl, 'mysql://')) {
            $pdo = _connect_mysql_url_dsn($databaseUrl);
            $activeDriver = 'mysql';
        } else {
            $pdo = _connect_mysql_creds();
            $activeDriver = 'mysql';
        }
    } catch (PDOException $e) {
        $errors[] = "MySQL: " . $e->getMessage();
    }
}

// 2. If PostgreSQL URL is provided
if (!$pdo && $databaseUrl && (str_starts_with($databaseUrl, 'postgres://') || str_starts_with($databaseUrl, 'postgresql://'))) {
    try {
        $pdo = _connect_pgsql_url($databaseUrl);
        $activeDriver = 'pgsql';
    } catch (PDOException $e) {
        $errors[] = "PostgreSQL: " . $e->getMessage();
    }
}

// 3. If MySQL URL is provided
if (!$pdo && $databaseUrl && str_starts_with($databaseUrl, 'mysql://')) {
    try {
        $pdo = _connect_mysql_url_dsn($databaseUrl);
        $activeDriver = 'mysql';
    } catch (PDOException $e) {
        $errors[] = "MySQL (URL): " . $e->getMessage();
    }
}

// 4. Default attempt: local MySQL (standard XAMPP environment)
if (!$pdo && empty($errors)) {
    try {
        $pdo = _connect_mysql_creds();
        $activeDriver = 'mysql';
    } catch (PDOException $e) {
        $errors[] = "Local MySQL: " . $e->getMessage();
    }
}

// 5. Automatic Fallback to SQLite (ensures deployment succeeds on Railway / zero-config environments)
if (!$pdo) {
    try {
        $pdo = _connect_sqlite();
        $activeDriver = 'sqlite';
    } catch (PDOException $e) {
        $errors[] = "SQLite fallback: " . $e->getMessage();
    }
}

$GLOBALS['DB_DRIVER'] = $activeDriver;

if ($pdo) {
    // Ensure book media columns exist regardless of database engine
    try {
        if ($activeDriver === 'sqlite') {
            $cols = $pdo->query("PRAGMA table_info(books)")->fetchAll(PDO::FETCH_ASSOC);
            $names = array_column($cols, 'name');
            if (!in_array('cover_image', $names)) {
                $pdo->exec("ALTER TABLE books ADD COLUMN cover_image TEXT NULL");
            }
            if (!in_array('ebook_file', $names)) {
                $pdo->exec("ALTER TABLE books ADD COLUMN ebook_file TEXT NULL");
            }
        } elseif ($activeDriver === 'pgsql') {
            $pdo->exec("
                ALTER TABLE books ADD COLUMN IF NOT EXISTS cover_image VARCHAR(100) NULL;
                ALTER TABLE books ADD COLUMN IF NOT EXISTS ebook_file VARCHAR(100) NULL;
            ");
        } else {
            // MySQL
            $cols = $pdo->query("SHOW COLUMNS FROM books LIKE 'cover_image'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE books ADD COLUMN cover_image VARCHAR(100) NULL");
            }
            $cols2 = $pdo->query("SHOW COLUMNS FROM books LIKE 'ebook_file'")->fetch();
            if (!$cols2) {
                $pdo->exec("ALTER TABLE books ADD COLUMN ebook_file VARCHAR(100) NULL");
            }
        }
    } catch (Exception $e) {
        error_log("Notice: book media column check: " . $e->getMessage());
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
            <p>Could not connect to the database. Error details:</p>
            <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 12px; border-radius: 6px; font-family: monospace; font-size: 13px; color: #333; margin-bottom: 20px;">
                ' . $errorDetails . '
            </div>
            <h5>How to fix on Railway:</h5>
            <ol style="font-size: 14px; color: #555;">
                <li>In your Railway Project, click <strong>+ New</strong> &rarr; <strong>Database</strong> &rarr; <strong>Add MySQL</strong>.</li>
                <li>Railway will automatically provision MySQL and link the connection variables.</li>
            </ol>
        </div>
    </body>
    </html>
    ');
}
