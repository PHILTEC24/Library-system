-- ============================================================
-- Library Management System - Database Schema & Sample Data
-- ============================================================
-- TEST CREDENTIALS: admin / password,  librarian / password
-- The password_hash values below were generated with PHP password_hash()
-- Both accounts use the same demo password: 'password'
-- ============================================================

DROP DATABASE IF EXISTS library_db;
CREATE DATABASE library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE library_db;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE categories (
    id          INT          NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: authors
-- ============================================================
CREATE TABLE authors (
    id      INT          NOT NULL AUTO_INCREMENT,
    name    VARCHAR(150) NOT NULL,
    country VARCHAR(100),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: books
-- ============================================================
CREATE TABLE books (
    id               INT          NOT NULL AUTO_INCREMENT,
    isbn             VARCHAR(20)  NOT NULL UNIQUE,
    title            VARCHAR(255) NOT NULL,
    author_id        INT          DEFAULT NULL,
    category_id      INT          DEFAULT NULL,
    publisher        VARCHAR(150),
    year_published   YEAR,
    total_copies     INT          NOT NULL DEFAULT 1,
    available_copies INT          NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    CONSTRAINT fk_books_author   FOREIGN KEY (author_id)   REFERENCES authors(id)   ON DELETE SET NULL,
    CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: members
-- ============================================================
CREATE TABLE members (
    id            INT          NOT NULL AUTO_INCREMENT,
    member_number VARCHAR(20)  NOT NULL UNIQUE,
    full_name     VARCHAR(150) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(20),
    status        ENUM('active','suspended') NOT NULL DEFAULT 'active',
    date_joined   DATE         NOT NULL DEFAULT (CURRENT_DATE),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE users (
    id            INT          NOT NULL AUTO_INCREMENT,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(150) NOT NULL,
    role          ENUM('admin','librarian') NOT NULL DEFAULT 'librarian',
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: loans
-- ============================================================
CREATE TABLE loans (
    id            INT  NOT NULL AUTO_INCREMENT,
    member_id     INT  DEFAULT NULL,
    book_id       INT  DEFAULT NULL,
    date_issued   DATE NOT NULL,
    due_date      DATE NOT NULL,
    date_returned DATE NULL DEFAULT NULL,
    status        ENUM('active','returned','overdue') NOT NULL DEFAULT 'active',
    PRIMARY KEY (id),
    CONSTRAINT fk_loans_member FOREIGN KEY (member_id) REFERENCES members(id),
    CONSTRAINT fk_loans_book   FOREIGN KEY (book_id)   REFERENCES books(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SAMPLE DATA: categories
-- ============================================================
INSERT INTO categories (name, description) VALUES
('Fiction',      'Novels, short stories, and other imaginative works'),
('Non-Fiction',  'Factual books including biographies, essays, and journalism'),
('Science',      'Books covering natural sciences, physics, biology, and chemistry'),
('History',      'Historical accounts, chronicles, and historical analysis'),
('Technology',   'Computing, engineering, software, and digital culture');

-- ============================================================
-- SAMPLE DATA: authors
-- ============================================================
INSERT INTO authors (name, country) VALUES
('George Orwell',       'United Kingdom'),
('Yuval Noah Harari',   'Israel'),
('Stephen Hawking',     'United Kingdom'),
('Yuval Sapiens',       'Israel'),
('Robert C. Martin',    'United States');

-- ============================================================
-- SAMPLE DATA: books
-- (10 books; total_copies=3 for all; available_copies adjusted for loans)
-- Loan plan:
--   Returned (3):  book_id 1, 2, 3  -> available_copies unchanged (3)
--   Active   (4):  book_id 4,5,6,7  -> available_copies = 3-1 = 2
--   Overdue  (3):  book_id 8,9,10   -> available_copies = 3-1 = 2
-- ============================================================
INSERT INTO books (isbn, title, author_id, category_id, publisher, year_published, total_copies, available_copies) VALUES
('978-0451524935', 'Nineteen Eighty-Four',          1, 1, 'Secker & Warburg',      1949, 3, 3),
('978-0451526342', 'Animal Farm',                   1, 1, 'Secker & Warburg',      1945, 3, 3),
('978-0062316097', 'Sapiens: A Brief History',       2, 2, 'Harper',                2015, 3, 3),
('978-0062316110', 'Homo Deus',                     2, 2, 'Harper',                2016, 3, 2),
('978-0062316103', '21 Lessons for the 21st Century',2, 2, 'Spiegel & Grau',      2018, 3, 2),
('978-0553380163', 'A Brief History of Time',        3, 3, 'Bantam Books',          1988, 3, 2),
('978-0553896336', 'The Grand Design',               3, 3, 'Bantam Books',          2010, 3, 2),
('978-0062301239', 'The Universe in a Nutshell',     3, 3, 'Bantam Books',          2001, 3, 2),
('978-0307741549', 'Guns, Germs, and Steel',         4, 4, 'W. W. Norton',          1997, 3, 2),
('978-0132350884', 'Clean Code',                     5, 5, 'Prentice Hall',         2008, 3, 2);

-- ============================================================
-- SAMPLE DATA: members
-- ============================================================
INSERT INTO members (member_number, full_name, email, phone, status, date_joined) VALUES
('MEM001', 'Alice Johnson',   'alice.johnson@email.com',   '555-0101', 'active',    '2023-01-15'),
('MEM002', 'Bob Smith',       'bob.smith@email.com',       '555-0102', 'active',    '2023-02-20'),
('MEM003', 'Carol Williams',  'carol.williams@email.com',  '555-0103', 'active',    '2023-03-10'),
('MEM004', 'David Brown',     'david.brown@email.com',     '555-0104', 'active',    '2023-04-05'),
('MEM005', 'Eva Martinez',    'eva.martinez@email.com',    '555-0105', 'active',    '2023-05-18'),
('MEM006', 'Frank Lee',       'frank.lee@email.com',       '555-0106', 'active',    '2023-06-22'),
('MEM007', 'Grace Kim',       'grace.kim@email.com',       '555-0107', 'suspended', '2023-07-30'),
('MEM008', 'Henry Davis',     'henry.davis@email.com',     '555-0108', 'active',    '2023-08-14'),
('MEM009', 'Irene Wilson',    'irene.wilson@email.com',    '555-0109', 'active',    '2023-09-01'),
('MEM010', 'James Taylor',    'james.taylor@email.com',    '555-0110', 'active',    '2023-10-11');

-- ============================================================
-- SAMPLE DATA: users
-- (password for both accounts is 'password')
-- ============================================================
INSERT INTO users (username, password_hash, full_name, role) VALUES
('admin',     '$2y$12$osiNZysNIbf09/Bt701vKuchMhUoqYEZxPsQF7uuDjNR0f7u/oekS', 'System Administrator', 'admin'),
('librarian', '$2y$12$osiNZysNIbf09/Bt701vKuchMhUoqYEZxPsQF7uuDjNR0f7u/oekS', 'Head Librarian',       'librarian');

-- ============================================================
-- SAMPLE DATA: loans
-- 3 returned  (status='returned', date_returned set)
-- 4 active    (status='active',   due_date in future)
-- 3 overdue   (status='overdue',  due_date in past, no return)
-- ============================================================
INSERT INTO loans (member_id, book_id, date_issued, due_date, date_returned, status) VALUES
-- Returned loans (books 1, 2, 3)
(1,  1, '2026-09-01', '2026-09-15', '2026-09-14', 'returned'),
(2,  2, '2026-09-05', '2026-09-19', '2026-09-20', 'returned'),
(3,  3, '2026-09-10', '2026-09-24', '2026-09-22', 'returned'),
-- Active loans (books 4, 5, 6, 7) — due dates in the future
(4,  4, '2026-11-01', '2026-11-29', NULL, 'active'),
(5,  5, '2026-11-03', '2026-12-01', NULL, 'active'),
(6,  6, '2026-11-05', '2026-12-03', NULL, 'active'),
(7,  7, '2026-11-07', '2026-12-05', NULL, 'active'),
-- Overdue loans (books 8, 9, 10) — due dates in the past
(8,  8, '2026-10-01', '2026-10-15', NULL, 'overdue'),
(9,  9, '2026-10-05', '2026-10-19', NULL, 'overdue'),
(10, 10,'2026-10-10', '2026-10-24', NULL, 'overdue');
