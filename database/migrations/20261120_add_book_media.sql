-- Run once on an existing library_db database to enable book covers and e-books.
ALTER TABLE books
    ADD COLUMN cover_image VARCHAR(100) NULL,
    ADD COLUMN ebook_file VARCHAR(100) NULL;
