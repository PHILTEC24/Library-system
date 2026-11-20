<?php
// Migration script for Neon PostgreSQL
$envFile = __DIR__ . '/../.env.local';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/../.env';
}

$env = parse_ini_file($envFile);
$dbUrl = $env['DATABASE_URL_UNPOOLED'] ?? $env['DATABASE_URL'] ?? null;
if (!$dbUrl) {
    die("No DATABASE_URL or DATABASE_URL_UNPOOLED found.\n");
}

$url = parse_url($dbUrl);
$dsn = sprintf(
    "pgsql:host=%s;port=%d;dbname=%s;sslmode=require",
    $url['host'],
    $url['port'] ?? 5432,
    ltrim($url['path'], '/')
);

echo "Connecting to Neon for schema migration...\n";
$pdo = new PDO($dsn, $url['user'], $url['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "Connected successfully.\n";

// 1. Create Compatibility Functions
$pdo->exec("
CREATE OR REPLACE FUNCTION CURDATE() RETURNS date AS $$
    SELECT CURRENT_DATE;
$$ LANGUAGE SQL STABLE;

CREATE OR REPLACE FUNCTION DATEDIFF(date1 date, date2 date) RETURNS integer AS $$
    SELECT (date1 - date2)::integer;
$$ LANGUAGE SQL IMMUTABLE;

CREATE OR REPLACE FUNCTION DATE_ADD(d date, i interval) RETURNS date AS $$
    SELECT (d + i)::date;
$$ LANGUAGE SQL IMMUTABLE;
");
echo "Compatibility functions created.\n";

// 2. Create Tables
$pdo->exec("
CREATE TABLE IF NOT EXISTS categories (
    category_id SERIAL PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS sub_categories (
    sub_category_id SERIAL PRIMARY KEY,
    category_id INT NOT NULL REFERENCES categories(category_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS authors (
    author_id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country VARCHAR(60) NULL
);

CREATE TABLE IF NOT EXISTS books (
    book_id SERIAL PRIMARY KEY,
    isbn VARCHAR(17) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    sub_category_id INT NOT NULL REFERENCES sub_categories(sub_category_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    publisher VARCHAR(100) NULL,
    year_published SMALLINT NULL,
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    cover_image VARCHAR(100) NULL,
    ebook_file VARCHAR(100) NULL
);

ALTER TABLE books
    ADD COLUMN IF NOT EXISTS cover_image VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS ebook_file VARCHAR(100) NULL;

CREATE TABLE IF NOT EXISTS book_authors (
    book_author_id SERIAL PRIMARY KEY,
    book_id INT NOT NULL REFERENCES books(book_id) ON DELETE CASCADE ON UPDATE CASCADE,
    author_id INT NOT NULL REFERENCES authors(author_id) ON DELETE CASCADE ON UPDATE CASCADE,
    author_role VARCHAR(20) NOT NULL DEFAULT 'co-author',
    CONSTRAINT uq_book_author UNIQUE (book_id, author_id)
);

CREATE TABLE IF NOT EXISTS members (
    member_id SERIAL PRIMARY KEY,
    member_no VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    date_joined DATE NOT NULL DEFAULT CURRENT_DATE
);

CREATE TABLE IF NOT EXISTS users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'librarian'
);

CREATE TABLE IF NOT EXISTS loans (
    loan_id SERIAL PRIMARY KEY,
    member_id INT NOT NULL REFERENCES members(member_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    book_id INT NOT NULL REFERENCES books(book_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    issued_by INT NOT NULL REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    date_issued DATE NOT NULL,
    due_date DATE NOT NULL,
    date_returned DATE NULL DEFAULT NULL
);
");
echo "Tables verified/created.\n";

// 3. Seed initial data if tables are empty
$usersCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($usersCount === 0) {
    echo "Seeding initial data...\n";

    // Categories
    $pdo->exec("
    INSERT INTO categories (category_id, name, description) VALUES
    (1, 'Fiction',      'Novels, short stories, and other imaginative works'),
    (2, 'Non-Fiction',  'Factual books including biographies, essays, and journalism'),
    (3, 'Science',      'Books covering natural sciences, physics, biology, and chemistry'),
    (4, 'History',      'Historical accounts, chronicles, and historical analysis'),
    (5, 'Technology',   'Computing, engineering, software, and digital culture');

    INSERT INTO sub_categories (sub_category_id, category_id, name, description) VALUES
    (1,  1, 'Dystopian & Political Fiction', 'Fiction exploring oppressive societies and political themes'),
    (2,  1, 'Classic Novels',                'Enduring works of literary fiction'),
    (3,  2, 'Humanity & Society',            'Big-picture works on human history, culture, and the future'),
    (4,  2, 'Biography & Memoir',            'Life stories and personal accounts'),
    (5,  3, 'Physics & Cosmology',           'The universe, space, time, and fundamental physics'),
    (6,  3, 'Biology & Life Sciences',       'Living organisms, evolution, and ecology'),
    (7,  4, 'World History',                 'Global historical analysis and chronicles'),
    (8,  4, 'Ancient History',               'Civilizations and events of the ancient world'),
    (9,  5, 'Software Engineering',          'Building, testing, and maintaining software'),
    (10, 5, 'Computer Science',              'Algorithms, theory, and computing fundamentals');

    INSERT INTO authors (author_id, name, country) VALUES
    (1, 'George Orwell',       'United Kingdom'),
    (2, 'Yuval Noah Harari',   'Israel'),
    (3, 'Stephen Hawking',     'United Kingdom'),
    (4, 'Jared Diamond',       'United States'),
    (5, 'Robert C. Martin',    'United States'),
    (6, 'Leonard Mlodinow',    'United States');

    INSERT INTO books (book_id, isbn, title, sub_category_id, publisher, year_published, total_copies, available_copies) VALUES
    (1,  '978-0451524935', 'Nineteen Eighty-Four',            1, 'Secker & Warburg', 1949, 3, 3),
    (2,  '978-0451526342', 'Animal Farm',                     1, 'Secker & Warburg', 1945, 3, 3),
    (3,  '978-0062316097', 'Sapiens: A Brief History',        3, 'Harper',           2015, 3, 3),
    (4,  '978-0062316110', 'Homo Deus',                       3, 'Harper',           2016, 3, 2),
    (5,  '978-0062316103', '21 Lessons for the 21st Century', 3, 'Spiegel & Grau',   2018, 3, 2),
    (6,  '978-0553896336', 'The Grand Design',                5, 'Bantam Books',     2010, 3, 2),
    (7,  '978-0553380163', 'A Brief History of Time',         5, 'Bantam Books',     1988, 3, 2),
    (8,  '978-0062301239', 'The Universe in a Nutshell',      5, 'Bantam Books',     2001, 3, 2),
    (9,  '978-0307741549', 'Guns, Germs, and Steel',          7, 'W. W. Norton',     1997, 3, 2),
    (10, '978-0132350884', 'Clean Code',                      9, 'Prentice Hall',    2008, 3, 2);

    INSERT INTO book_authors (book_author_id, book_id, author_id, author_role) VALUES
    (1,  1,  1, 'primary'),
    (2,  2,  1, 'primary'),
    (3,  3,  2, 'primary'),
    (4,  4,  2, 'primary'),
    (5,  5,  2, 'primary'),
    (6,  6,  3, 'primary'),
    (7,  7,  3, 'primary'),
    (8,  7,  6, 'co-author'),
    (9,  8,  3, 'primary'),
    (10, 9,  4, 'primary'),
    (11, 10, 5, 'primary');

    INSERT INTO members (member_id, member_no, full_name, email, phone, status, date_joined) VALUES
    (1,  'MEM001', 'Alice Johnson',   'alice.johnson@email.com',   '555-0101', 'active',    '2023-01-15'),
    (2,  'MEM002', 'Bob Smith',       'bob.smith@email.com',       '555-0102', 'active',    '2023-02-20'),
    (3,  'MEM003', 'Carol Williams',  'carol.williams@email.com',  '555-0103', 'active',    '2023-03-10'),
    (4,  'MEM004', 'David Brown',     'david.brown@email.com',     '555-0104', 'active',    '2023-04-05'),
    (5,  'MEM005', 'Eva Martinez',    'eva.martinez@email.com',    '555-0105', 'active',    '2023-05-18'),
    (6,  'MEM006', 'Frank Lee',       'frank.lee@email.com',       '555-0106', 'active',    '2023-06-22'),
    (7,  'MEM007', 'Grace Kim',       'grace.kim@email.com',       '555-0107', 'suspended', '2023-07-30'),
    (8,  'MEM008', 'Henry Davis',     'henry.davis@email.com',     '555-0108', 'active',    '2023-08-14'),
    (9,  'MEM009', 'Irene Wilson',    'irene.wilson@email.com',    '555-0109', 'active',    '2023-09-01'),
    (10, 'MEM010', 'James Taylor',    'james.taylor@email.com',    '555-0110', 'active',    '2023-10-11');

    INSERT INTO users (user_id, username, password_hash, full_name, role) VALUES
    (1, 'admin',     '\$2y\$12\$osiNZysNIbf09/Bt701vKuchMhUoqYEZxPsQF7uuDjNR0f7u/oekS', 'System Administrator', 'admin'),
    (2, 'librarian', '\$2y\$12\$osiNZysNIbf09/Bt701vKuchMhUoqYEZxPsQF7uuDjNR0f7u/oekS', 'Head Librarian',       'librarian');

    INSERT INTO loans (loan_id, member_id, book_id, issued_by, date_issued, due_date, date_returned) VALUES
    (1,  1,  1,  1, '2026-09-01', '2026-09-15', '2026-09-14'),
    (2,  2,  2,  2, '2026-09-05', '2026-09-19', '2026-09-20'),
    (3,  3,  3,  1, '2026-09-10', '2026-09-24', '2026-09-22'),
    (4,  4,  4,  2, '2026-11-01', '2026-11-29', NULL),
    (5,  5,  5,  1, '2026-11-03', '2026-12-01', NULL),
    (6,  6,  6,  2, '2026-11-05', '2026-12-03', NULL),
    (7,  7,  7,  1, '2026-11-07', '2026-12-05', NULL),
    (8,  8,  8,  2, '2026-10-01', '2026-10-15', NULL),
    (9,  9,  9,  1, '2026-10-05', '2026-10-19', NULL),
    (10, 10, 10, 2, '2026-10-10', '2026-10-24', NULL);
    ");

    // Reset sequences to MAX(id) + 1
    $tables = [
        'categories' => 'category_id',
        'sub_categories' => 'sub_category_id',
        'authors' => 'author_id',
        'books' => 'book_id',
        'book_authors' => 'book_author_id',
        'members' => 'member_id',
        'users' => 'user_id',
        'loans' => 'loan_id'
    ];
    foreach ($tables as $tbl => $col) {
        $pdo->exec("SELECT setval(pg_get_serial_sequence('$tbl', '$col'), COALESCE(MAX($col), 0) + 1, false) FROM $tbl");
    }
    echo "Data seeded successfully and sequences updated.\n";
} else {
    echo "Data already present, skipping seed.\n";
}

echo "Migration finished successfully.\n";
