-- ============================================================
-- Library Management System - Useful Queries (Aligned with ERD)
-- ============================================================

-- Query 1: Books currently on loan (all active/overdue loans)
SELECT
    b.title,
    b.isbn,
    m.full_name     AS member,
    m.member_no     AS member_number,
    l.date_issued,
    l.due_date
FROM loans l
JOIN books   b ON l.book_id   = b.book_id
JOIN members m ON l.member_id = m.member_id
WHERE l.date_returned IS NULL
ORDER BY l.due_date;

-- Query 2: Overdue loans with days overdue
SELECT
    b.title,
    m.full_name,
    m.phone,
    l.due_date,
    DATEDIFF(CURDATE(), l.due_date) AS days_overdue
FROM loans l
JOIN books   b ON l.book_id   = b.book_id
JOIN members m ON l.member_id = m.member_id
WHERE l.date_returned IS NULL
  AND l.due_date < CURDATE()
ORDER BY days_overdue DESC;

-- Query 3: Number of loans per category
SELECT
    c.name        AS category,
    COUNT(l.loan_id) AS total_loans
FROM categories c
LEFT JOIN sub_categories sc ON sc.category_id = c.category_id
LEFT JOIN books b ON b.sub_category_id = sc.sub_category_id
LEFT JOIN loans l ON l.book_id = b.book_id
GROUP BY c.category_id, c.name
ORDER BY total_loans DESC;

-- Query 4: Available vs on-loan copies per category
SELECT
    c.name,
    SUM(b.total_copies)                     AS total_copies,
    SUM(b.available_copies)                 AS available,
    SUM(b.total_copies - b.available_copies) AS on_loan
FROM categories c
LEFT JOIN sub_categories sc ON sc.category_id = c.category_id
JOIN books b ON b.sub_category_id = sc.sub_category_id
GROUP BY c.category_id, c.name;

-- Query 5: Members with most loans
SELECT
    m.full_name,
    m.member_no AS member_number,
    COUNT(l.loan_id) AS loan_count
FROM members m
LEFT JOIN loans l ON l.member_id = m.member_id
GROUP BY m.member_id, m.full_name, m.member_no
ORDER BY loan_count DESC;

-- Query 6: Books never borrowed
SELECT
    b.title,
    b.isbn,
    a.name AS author
FROM books b
JOIN book_authors ba ON b.book_id = ba.book_id AND ba.author_role = 'primary'
JOIN authors a ON ba.author_id = a.author_id
LEFT JOIN loans l ON l.book_id = b.book_id
WHERE l.loan_id IS NULL;
