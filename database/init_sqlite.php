<?php
// database/init_sqlite.php - Initialize SQLite database with schema and sample records
function init_sqlite_database(PDO $pdo) {
    $hasTables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
    if ($hasTables) {
        return;
    }

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
        category_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        description TEXT
    );

    CREATE TABLE IF NOT EXISTS sub_categories (
        sub_category_id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER NOT NULL REFERENCES categories(category_id),
        name TEXT NOT NULL UNIQUE,
        description TEXT
    );

    CREATE TABLE IF NOT EXISTS authors (
        author_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        country TEXT
    );

    CREATE TABLE IF NOT EXISTS books (
        book_id INTEGER PRIMARY KEY AUTOINCREMENT,
        isbn TEXT NOT NULL UNIQUE,
        title TEXT NOT NULL,
        sub_category_id INTEGER NOT NULL REFERENCES sub_categories(sub_category_id),
        publisher TEXT,
        year_published INTEGER,
        total_copies INTEGER NOT NULL DEFAULT 1,
        available_copies INTEGER NOT NULL DEFAULT 1,
        cover_image TEXT NULL,
        ebook_file TEXT NULL
    );

    CREATE TABLE IF NOT EXISTS book_authors (
        book_author_id INTEGER PRIMARY KEY AUTOINCREMENT,
        book_id INTEGER NOT NULL REFERENCES books(book_id) ON DELETE CASCADE,
        author_id INTEGER NOT NULL REFERENCES authors(author_id) ON DELETE CASCADE,
        author_role TEXT NOT NULL DEFAULT 'co-author',
        UNIQUE(book_id, author_id)
    );

    CREATE TABLE IF NOT EXISTS members (
        member_id INTEGER PRIMARY KEY AUTOINCREMENT,
        member_no TEXT NOT NULL UNIQUE,
        full_name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        phone TEXT,
        status TEXT NOT NULL DEFAULT 'active',
        date_joined TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        full_name TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'librarian'
    );

    CREATE TABLE IF NOT EXISTS loans (
        loan_id INTEGER PRIMARY KEY AUTOINCREMENT,
        member_id INTEGER NOT NULL REFERENCES members(member_id),
        book_id INTEGER NOT NULL REFERENCES books(book_id),
        issued_by INTEGER NOT NULL REFERENCES users(user_id),
        date_issued TEXT NOT NULL,
        due_date TEXT NOT NULL,
        date_returned TEXT DEFAULT NULL
    );
    ");

    // Seed Categories
    $pdo->exec("
    INSERT OR IGNORE INTO categories (category_id, name, description) VALUES
    (1, 'Fiction', 'Novels, short stories, and other imaginative works'),
    (2, 'Non-Fiction', 'Factual books including biographies, essays, and journalism'),
    (3, 'Science', 'Natural sciences, physics, astronomy, and biology'),
    (4, 'History', 'World history, civilizations, and historical analysis'),
    (5, 'Technology', 'Computer science, programming, and software engineering');
    ");

    // Seed Subcategories
    $pdo->exec("
    INSERT OR IGNORE INTO sub_categories (sub_category_id, category_id, name, description) VALUES
    (1, 1, 'Classic Literature', 'Timeless and canonical literary works'),
    (2, 1, 'Dystopian Fiction', 'Speculative fiction exploring oppressive societal controls'),
    (3, 2, 'Anthropology', 'Studies of human societies, cultures, and evolution'),
    (4, 2, 'Philosophy', 'Fundamental investigations of existence and ethics'),
    (5, 3, 'Theoretical Physics', 'Theoretical astrophysics and cosmology'),
    (6, 3, 'Cosmology', 'Origins and evolution of the universe'),
    (7, 4, 'World History', 'Global historical events and civilizations'),
    (8, 4, 'Evolutionary History', 'Deep-time history of societies and technology'),
    (9, 5, 'Software Engineering', 'Practices and architectures in programming'),
    (10, 5, 'Web Development', 'Internet architectures and web systems');
    ");

    // Seed Authors
    $pdo->exec("
    INSERT OR IGNORE INTO authors (author_id, name, country) VALUES
    (1, 'George Orwell', 'United Kingdom'),
    (2, 'Yuval Noah Harari', 'Israel'),
    (3, 'Stephen Hawking', 'United Kingdom'),
    (4, 'Jared Diamond', 'United States'),
    (5, 'Robert C. Martin', 'United States'),
    (6, 'Leonard Mlodinow', 'United States');
    ");

    // Seed Books
    $pdo->exec("
    INSERT OR IGNORE INTO books (book_id, isbn, title, sub_category_id, publisher, year_published, total_copies, available_copies) VALUES
    (1, '978-0451524935', 'Nineteen Eighty-Four', 1, 'Secker & Warburg', 1949, 3, 3),
    (2, '978-0451526342', 'Animal Farm', 1, 'Secker & Warburg', 1945, 3, 3),
    (3, '978-0062316097', 'Sapiens: A Brief History', 3, 'Harper', 2015, 3, 3),
    (4, '978-0062316110', 'Homo Deus', 3, 'Harper', 2016, 3, 2),
    (5, '978-0062316103', '21 Lessons for the 21st Century', 3, 'Spiegel & Grau', 2018, 3, 2),
    (6, '978-0553380163', 'A Brief History of Time', 5, 'Bantam Books', 1988, 3, 2),
    (7, '978-0553896336', 'The Grand Design', 5, 'Bantam Books', 2010, 3, 2),
    (8, '978-0062301239', 'The Universe in a Nutshell', 5, 'Bantam Books', 2001, 3, 2),
    (9, '978-0307741549', 'Guns, Germs, and Steel', 7, 'W. W. Norton', 1997, 3, 2),
    (10, '978-0132350884', 'Clean Code', 9, 'Prentice Hall', 2008, 3, 2);
    ");

    // Seed Book Authors
    $pdo->exec("
    INSERT OR IGNORE INTO book_authors (book_id, author_id, author_role) VALUES
    (1, 1, 'primary'),
    (2, 1, 'primary'),
    (3, 2, 'primary'),
    (4, 2, 'primary'),
    (5, 2, 'primary'),
    (6, 3, 'primary'),
    (7, 3, 'primary'),
    (7, 6, 'co-author'),
    (8, 3, 'primary'),
    (9, 4, 'primary'),
    (10, 5, 'primary');
    ");

    // Seed Members
    $pdo->exec("
    INSERT OR IGNORE INTO members (member_id, member_no, full_name, email, phone, status, date_joined) VALUES
    (1, 'MEM001', 'Alice Johnson', 'alice.johnson@email.com', '555-0101', 'active', '2023-01-15'),
    (2, 'MEM002', 'Bob Smith', 'bob.smith@email.com', '555-0102', 'active', '2023-02-20'),
    (3, 'MEM003', 'Carol Williams', 'carol.williams@email.com', '555-0103', 'active', '2023-03-10'),
    (4, 'MEM004', 'David Brown', 'david.brown@email.com', '555-0104', 'active', '2023-04-05'),
    (5, 'MEM005', 'Emma Jones', 'emma.jones@email.com', '555-0105', 'active', '2023-05-12'),
    (6, 'MEM006', 'Frank Miller', 'frank.miller@email.com', '555-0106', 'active', '2023-06-18'),
    (7, 'MEM007', 'Grace Wilson', 'grace.wilson@email.com', '555-0107', 'active', '2023-07-22'),
    (8, 'MEM008', 'Henry Moore', 'henry.moore@email.com', '555-0108', 'active', '2023-08-30'),
    (9, 'MEM009', 'Ivy Taylor', 'ivy.taylor@email.com', '555-0109', 'suspended', '2023-09-14'),
    (10, 'MEM010', 'Jack Anderson', 'jack.anderson@email.com', '555-0110', 'active', '2023-10-01');
    ");

    // Seed Users (password: 'password')
    $hash = password_hash('password', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (user_id, username, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([1, 'admin', $hash, 'System Administrator', 'admin']);
    $stmt->execute([2, 'librarian', $hash, 'Head Librarian', 'librarian']);

    // Seed Loans
    $pdo->exec("
    INSERT OR IGNORE INTO loans (loan_id, member_id, book_id, issued_by, date_issued, due_date, date_returned) VALUES
    (1, 1, 1, 1, '2026-09-01', '2026-09-15', '2026-09-14'),
    (2, 2, 2, 2, '2026-09-05', '2026-09-19', '2026-09-20'),
    (3, 3, 3, 1, '2026-09-10', '2026-09-24', '2026-09-22'),
    (4, 4, 4, 2, '2026-11-01', '2026-11-29', NULL),
    (5, 5, 5, 1, '2026-11-03', '2026-12-01', NULL),
    (6, 6, 6, 2, '2026-11-05', '2026-12-03', NULL),
    (7, 7, 7, 1, '2026-11-07', '2026-12-05', NULL),
    (8, 8, 8, 2, '2026-10-01', '2026-10-15', NULL),
    (9, 9, 9, 1, '2026-10-05', '2026-10-19', NULL),
    (10, 10, 10, 2, '2026-10-10', '2026-10-24', NULL);
    ");
}
