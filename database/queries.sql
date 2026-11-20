-- ============================================================
-- Library Management System - Useful Queries
-- ============================================================

-- Query 1: Books currently on loan (all active/overdue loans)
SELECT
    b.title,
    b.isbn,
    m.full_name     AS member,
    m.member_number,
    l.date_issued,
    l.due_date
FROM loans l
JOIN books   b ON l.book_id   = b.id
JOIN members m ON l.member_id = m.id
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
JOIN books   b ON l.book_id   = b.id
JOIN members m ON l.member_id = m.id
WHERE l.date_returned IS NULL
  AND l.due_date < CURDATE()
ORDER BY days_overdue DESC;

-- Query 3: Number of loans per category
SELECT
    c.name        AS category,
    COUNT(l.id)   AS total_loans
FROM categories c
LEFT JOIN books b ON b.category_id = c.id
LEFT JOIN loans l ON l.book_id     = b.id
GROUP BY c.id, c.name
ORDER BY total_loans DESC;

-- Query 4: Available vs on-loan copies per category
SELECT
    c.name,
    SUM(b.total_copies)                     AS total_copies,
    SUM(b.available_copies)                 AS available,
    SUM(b.total_copies - b.available_copies) AS on_loan
FROM categories c
JOIN books b ON b.category_id = c.id
GROUP BY c.id, c.name;

-- Query 5: Members with most loans
SELECT
    m.full_name,
    m.member_number,
    COUNT(l.id) AS loan_count
FROM members m
LEFT JOIN loans l ON l.member_id = m.id
GROUP BY m.id, m.full_name, m.member_number
ORDER BY loan_count DESC;

-- Query 6: Books never borrowed
SELECT
    b.title,
    b.isbn,
    a.name AS author
FROM books b
JOIN authors a ON b.author_id = a.id
LEFT JOIN loans l ON l.book_id = b.id
WHERE l.id IS NULL;
