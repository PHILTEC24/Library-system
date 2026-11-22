-- ============================================================
-- Library Management System - Database Schema & Sample Data
-- (rebuilt to match the ER diagram)
-- ============================================================
-- TEST CREDENTIALS: admin / password,  librarian / password
-- The password_hash values below were generated with PHP password_hash()
-- Both accounts use the same demo password: 'password'
-- ============================================================
-- WARNING: this drops and recreates library_db (all data is replaced)
-- ============================================================

DROP DATABASE IF EXISTS library_db;
CREATE DATABASE library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE library_db;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE categories (
    category_id INT          NOT NULL AUTO_INCREMENT,
    name        VARCHAR(60)  NOT NULL,
    description VARCHAR(255) NULL,
    PRIMARY KEY (category_id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: sub_categories   (categories 1 --- * sub_categories)
-- ============================================================
CREATE TABLE sub_categories (
    sub_category_id INT          NOT NULL AUTO_INCREMENT,
    category_id     INT          NOT NULL,
    name            VARCHAR(80)  NOT NULL,
    description     VARCHAR(255) NULL,
    PRIMARY KEY (sub_category_id),
    UNIQUE KEY uq_sub_categories_name (name),
    CONSTRAINT fk_subcat_category FOREIGN KEY (category_id)
        REFERENCES categories (category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: authors
-- ============================================================
CREATE TABLE authors (
    author_id INT          NOT NULL AUTO_INCREMENT,
    name      VARCHAR(100) NOT NULL,
    country   VARCHAR(60)  NULL,
    PRIMARY KEY (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: books   (sub_categories 1 --- * books)
-- ============================================================
CREATE TABLE books (
    book_id          INT          NOT NULL AUTO_INCREMENT,
    isbn             VARCHAR(17)  NOT NULL,
    title            VARCHAR(200) NOT NULL,
    sub_category_id  INT          NOT NULL,
    publisher        VARCHAR(100) NULL,
    year_published   YEAR         NULL,
    total_copies     INT          NOT NULL DEFAULT 1,
    available_copies INT          NOT NULL DEFAULT 1,
    cover_image      VARCHAR(100) NULL,
    ebook_file       VARCHAR(100) NULL,
    PRIMARY KEY (book_id),
    UNIQUE KEY uq_books_isbn (isbn),
    CONSTRAINT fk_books_subcat FOREIGN KEY (sub_category_id)
        REFERENCES sub_categories (sub_category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: book_authors (Co-Authors)
-- authors 1 --- * book_authors * --- 1 books
-- ============================================================
CREATE TABLE book_authors (
    book_author_id INT NOT NULL AUTO_INCREMENT,
    book_id        INT NOT NULL,
    author_id      INT NOT NULL,
    author_role    ENUM('primary','co-author','editor','translator') NOT NULL DEFAULT 'co-author',
    PRIMARY KEY (book_author_id),
    UNIQUE KEY uq_book_author (book_id, author_id),
    CONSTRAINT fk_ba_book FOREIGN KEY (book_id)
        REFERENCES books (book_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ba_author FOREIGN KEY (author_id)
        REFERENCES authors (author_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: members
-- ============================================================
CREATE TABLE members (
    member_id   INT          NOT NULL AUTO_INCREMENT,
    member_no   VARCHAR(20)  NOT NULL,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL,
    phone       VARCHAR(20)  NULL,
    status      ENUM('active','suspended') NOT NULL DEFAULT 'active',
    date_joined DATE         NOT NULL DEFAULT (CURRENT_DATE),
    PRIMARY KEY (member_id),
    UNIQUE KEY uq_members_member_no (member_no),
    UNIQUE KEY uq_members_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE users (
    user_id       INT          NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    role          ENUM('admin','librarian') NOT NULL DEFAULT 'librarian',
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: loans
-- members 1 --- * loans, books 1 --- * loans, users 1 --- * loans
-- (no status column: a loan is "returned" when date_returned is set,
--  "overdue" when date_returned IS NULL and due_date < CURDATE())
-- ============================================================
CREATE TABLE loans (
    loan_id       INT  NOT NULL AUTO_INCREMENT,
    member_id     INT  NOT NULL,
    book_id       INT  NOT NULL,
    issued_by     INT  NOT NULL,
    date_issued   DATE NOT NULL,
    due_date      DATE NOT NULL,
    date_returned DATE NULL DEFAULT NULL,
    PRIMARY KEY (loan_id),
    CONSTRAINT fk_loans_member FOREIGN KEY (member_id)
        REFERENCES members (member_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_loans_book FOREIGN KEY (book_id)
        REFERENCES books (book_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_loans_user FOREIGN KEY (issued_by)
        REFERENCES users (user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
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
-- SAMPLE DATA: sub_categories
-- ============================================================
INSERT INTO sub_categories (category_id, name, description) VALUES
(1, 'Dystopian & Political Fiction', 'Fiction exploring oppressive societies and political themes'),
(1, 'Classic Novels',                'Enduring works of literary fiction'),
(2, 'Humanity & Society',            'Big-picture works on human history, culture, and the future'),
(2, 'Biography & Memoir',            'Life stories and personal accounts'),
(3, 'Physics & Cosmology',           'The universe, space, time, and fundamental physics'),
(3, 'Biology & Life Sciences',       'Living organisms, evolution, and ecology'),
(4, 'World History',                 'Global historical analysis and chronicles'),
(4, 'Ancient History',               'Civilizations and events of the ancient world'),
(5, 'Software Engineering',          'Building, testing, and maintaining software'),
(5, 'Computer Science',              'Algorithms, theory, and computing fundamentals');

-- ============================================================
-- SAMPLE DATA: authors
-- ============================================================
INSERT INTO authors (name, country) VALUES
('George Orwell',       'United Kingdom'),
('Yuval Noah Harari',   'Israel'),
('Stephen Hawking',     'United Kingdom'),
('Jared Diamond',       'United States'),
('Robert C. Martin',    'United States'),
('Leonard Mlodinow',    'United States');

-- ============================================================
-- SAMPLE DATA: books
-- (10 books; total_copies=3 for all; available_copies adjusted for loans)
-- Loan plan:
--   Returned (3):  book_id 1, 2, 3  -> available_copies unchanged (3)
--   Active   (4):  book_id 4,5,6,7  -> available_copies = 3-1 = 2
--   Overdue  (3):  book_id 8,9,10   -> available_copies = 3-1 = 2
-- ============================================================
INSERT INTO books (isbn, title, sub_category_id, publisher, year_published, total_copies, available_copies) VALUES
('978-0451524935', 'Nineteen Eighty-Four',            1, 'Secker & Warburg', 1949, 3, 3),
('978-0451526342', 'Animal Farm',                     1, 'Secker & Warburg', 1945, 3, 3),
('978-0062316097', 'Sapiens: A Brief History',        3, 'Harper',           2015, 3, 3),
('978-0062316110', 'Homo Deus',                       3, 'Harper',           2016, 3, 2),
('978-0062316103', '21 Lessons for the 21st Century', 3, 'Spiegel & Grau',   2018, 3, 2),
('978-0553380163', 'A Brief History of Time',         5, 'Bantam Books',     1988, 3, 2),
('978-0553896336', 'The Grand Design',                5, 'Bantam Books',     2010, 3, 2),
('978-0062301239', 'The Universe in a Nutshell',      5, 'Bantam Books',     2001, 3, 2),
('978-0307741549', 'Guns, Germs, and Steel',          7, 'W. W. Norton',     1997, 3, 2),
('978-0132350884', 'Clean Code',                      9, 'Prentice Hall',    2008, 3, 2);

-- ============================================================
-- SAMPLE DATA: book_authors
-- (book_id, author_id, author_role)
-- The Grand Design (7) has a co-author: Leonard Mlodinow (6)
-- ============================================================
INSERT INTO book_authors (book_id, author_id, author_role) VALUES
(1,  1, 'primary'),
(2,  1, 'primary'),
(3,  2, 'primary'),
(4,  2, 'primary'),
(5,  2, 'primary'),
(6,  3, 'primary'),
(7,  3, 'primary'),
(7,  6, 'co-author'),
(8,  3, 'primary'),
(9,  4, 'primary'),
(10, 5, 'primary');

-- ============================================================
-- SAMPLE DATA: members
-- ============================================================
INSERT INTO members (member_no, full_name, email, phone, status, date_joined) VALUES
('MEM001', 'Alice Johnson',   'alice.johnson@email.com',   '+256 772 101001', 'active',    '2023-01-15'),
('MEM002', 'Bob Smith',       'bob.smith@email.com',       '+256 701 202002', 'active',    '2023-02-20'),
('MEM003', 'Carol Williams',  'carol.williams@email.com',  '+256 782 303003', 'active',    '2023-03-10'),
('MEM004', 'David Brown',     'david.brown@email.com',     '+256 752 404004', 'active',    '2023-04-05'),
('MEM005', 'Eva Martinez',    'eva.martinez@email.com',    '+256 774 505005', 'active',    '2023-05-18'),
('MEM006', 'Frank Lee',       'frank.lee@email.com',       '+256 703 606006', 'active',    '2023-06-22'),
('MEM007', 'Grace Kim',       'grace.kim@email.com',       '+256 788 707007', 'suspended', '2023-07-30'),
('MEM008', 'Henry Davis',     'henry.davis@email.com',     '+256 756 808008', 'active',    '2023-08-14'),
('MEM009', 'Irene Wilson',    'irene.wilson@email.com',    '+256 776 909009', 'active',    '2023-09-01'),
('MEM010', 'James Taylor',    'james.taylor@email.com',    '+256 705 010010', 'active',    '2023-10-11');

-- ============================================================
-- SAMPLE DATA: users
-- (password for both accounts is 'password')
-- ============================================================
INSERT INTO users (username, password_hash, full_name, role) VALUES
('admin',     '$2y$12$osiNZysNIbf09/Bt701vKuchMhUoqYEZxPsQF7uuDjNR0f7u/oekS', 'System Administrator', 'admin'),
('librarian', '$2y$12$osiNZysNIbf09/Bt701vKuchMhUoqYEZxPsQF7uuDjNR0f7u/oekS', 'Head Librarian',       'librarian');

-- ============================================================
-- SAMPLE DATA: loans
-- (member_id, book_id, issued_by, date_issued, due_date, date_returned)
-- 3 returned  (date_returned set)
-- 4 active    (due_date in future, not returned)
-- 3 overdue   (due_date in past, not returned)
-- ============================================================
INSERT INTO loans (member_id, book_id, issued_by, date_issued, due_date, date_returned) VALUES
-- Returned loans (books 1, 2, 3)
(1,  1,  1, '2026-09-01', '2026-09-15', '2026-09-14'),
(2,  2,  2, '2026-09-05', '2026-09-19', '2026-09-20'),
(3,  3,  1, '2026-09-10', '2026-09-24', '2026-09-22'),
-- Active loans (books 4, 5, 6, 7) - due dates in the future
(4,  4,  2, '2026-11-01', '2026-11-29', NULL),
(5,  5,  1, '2026-11-03', '2026-12-01', NULL),
(6,  6,  2, '2026-11-05', '2026-12-03', NULL),
(7,  7,  1, '2026-11-07', '2026-12-05', NULL),
-- Overdue loans (books 8, 9, 10) - due dates in the past
(8,  8,  2, '2026-10-01', '2026-10-15', NULL),
(9,  9,  1, '2026-10-05', '2026-10-19', NULL),
(10, 10, 2, '2026-10-10', '2026-10-24', NULL);

-- ============================================================
-- HANDY QUERY: loan status (replaces the old loans.status column)
-- ============================================================
-- SELECT loan_id,
--        CASE
--          WHEN date_returned IS NOT NULL THEN 'returned'
--          WHEN due_date < CURDATE()      THEN 'overdue'
--          ELSE 'active'
--        END AS status
-- FROM loans;
