# Library Management System (LibraryMS)
> **Database 2 (DB II) CAT 2 Database Tools Project**  
> GitHub Repository: [https://github.com/PHILTEC24/Library-system](https://github.com/PHILTEC24/Library-system)

---

## 1. Project Overview
The **Library Management System (LibraryMS)** is a web-based database application designed for library staff. It provides full Create, Read, Update, and Delete (CRUD) operations across all library entities, strictly enforces relational database integrity, and adheres to Third Normal Form (3NF). Built specifically for the **XAMPP** environment (Apache, MySQL/MariaDB, PHP), it features secure session-based authentication, parameterized queries (PDO) for SQL injection prevention, automated transaction management, responsive Bootstrap 5 styling, and automated late fine calculation.

---

## 2. Test Credentials

The database comes pre-seeded with two staff accounts:

| Username | Password | Full Name | Role | Access Scope |
|---|---|---|---|---|
| `admin` | `password` | System Administrator | Admin | Full CRUD, Loans, Reports, Management |
| `librarian` | `password` | Head Librarian | Librarian | Standard Operations, Loans, Inquiries |

> **Note on Security:** All passwords in the database are hashed using PHP's native `password_hash($password, PASSWORD_BCRYPT)`. Plaintext passwords are never stored.

---

## 3. Technology Stack & Tools

| Component | Technology | Version / Specification |
|---|---|---|
| **Local Server** | XAMPP | Apache 2.4+, PHP 8.1+, MariaDB 10.4+ / MySQL 8.0+ |
| **Database** | MySQL / MariaDB | InnoDB engine, `utf8mb4_general_ci` collation |
| **Backend** | PHP (PDO) | Prepared statements, parameterized queries, transactions |
| **Frontend** | HTML5, CSS3, JavaScript | Bootstrap 5.3.2, Bootstrap Icons 1.11.3 |
| **Version Control**| Git / GitHub | Feature branching and tracked commits |

---

## 4. Setup and Installation on Fresh XAMPP

Follow these step-by-step instructions to run the application on a clean XAMPP installation:

### Step 1: Install and Launch XAMPP
1. Download XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org) (PHP 8.1 or higher).
2. Install XAMPP into its default location: `C:\xampp`.
3. Open the **XAMPP Control Panel**.
4. Click **Start** for both **Apache** and **MySQL**. Verify that both service labels turn green.
   *(If Apache fails to start, ensure port 80 and 443 are not occupied by Skype, IIS, or VMware, or reassign Apache ports in `httpd.conf`).*

### Step 2: Copy the Project Folder
1. Copy the entire `library-system` folder into XAMPP's web directory:
   ```
   C:\xampp\htdocs\library-system
   ```
2. Verify that `C:\xampp\htdocs\library-system\index.php` exists.

### Step 3: Create Database & Import Schema
1. Open your web browser and go to:
   ```
   http://localhost/phpmyadmin
   ```
2. Click **Databases** on the top menu.
3. In **Create database**, enter `library_db` and select collation **`utf8mb4_general_ci`**, then click **Create**.
4. Select `library_db` from the left sidebar.
5. Click the **Import** tab on the top navigation bar.
6. Click **Choose File** (Browse) and select:
   ```
   C:\xampp\htdocs\library-system\database\library_db.sql
   ```
7. Click **Import** at the bottom of the page. You will see a green success banner indicating that all tables and sample records have been created.

### Step 4: Verify Database Connection Config
Open `config/database.php` in a text editor. The default XAMPP settings match:
```php
$host = 'localhost';
$db   = 'library_db';
$user = 'root';
$pass = '';
```
No edits are required unless you have customized your MySQL root password.

### Step 5: Access the Application
1. In your web browser, navigate to:
   ```
   http://localhost/library-system
   ```
2. The index router will automatically direct you to the login screen:
   ```
   http://localhost/library-system/auth/login.php
   ```
3. Sign in using:
   - **Username:** `admin`
   - **Password:** `password`
4. You will be redirected to the **Dashboard** displaying live statistics, quick links, recent loans, and overdue alerts.

---

## 5. Folder Structure

The project strictly follows the required architecture:

```
library-system/
├── index.php                 # Root entry router (redirects to login or dashboard)
├── README.md                 # Project guide, setup instructions & documentation
├── config/
│   └── database.php          # Singleton PDO database connection with error handling
├── database/
│   ├── library_db.sql        # Database schema, table definitions, constraints, sample data
│   ├── queries.sql           # Required JOIN and aggregate analytical queries
│   └── erd.png               # High-resolution Entity Relationship Diagram
├── includes/
│   ├── header.php            # Shared HTML head, CSS stylesheets & meta tags
│   ├── navbar.php            # Responsive top navigation with active-route highlights
│   ├── footer.php            # Shared footer, script bundles & main.js loader
│   ├── auth_check.php        # Session access guard blocking unauthorized visitors
│   └── functions.php         # Global helpers (e() escape, flash messages, member number)
├── auth/
│   ├── login.php             # Secure login form with password_verify & session regeneration
│   └── logout.php            # Session destruction and redirection
├── dashboard/
│   └── index.php             # Statistics summary, key metrics, recent loans & overdue alerts
├── books/
│   ├── index.php             # Search, category filter & table listing
│   ├── create.php            # Add new book with unique ISBN & stock validation
│   ├── edit.php              # Edit book metadata & copy counts
│   ├── delete.php            # Guarded deletion preventing removal of books with active loans
│   └── export.php            # Bonus: Export books catalog to CSV
├── members/
│   ├── index.php             # Search by name/number, filter by status (active/suspended)
│   ├── create.php            # Register new member with auto-generated member number
│   ├── edit.php              # Update member contact information and status
│   ├── delete.php            # Guarded deletion preventing removal of members with active loans
│   └── export.php            # Bonus: Export members roster to CSV
├── loans/
│   ├── index.php             # Complete loans log with search & computed status filters
│   ├── issue.php             # Issue book with transaction, stock decrement, 14-day due date
│   ├── return.php            # Return book with transaction, stock increment, fine calculator
│   └── export.php            # Bonus: Export loans report to CSV
├── authors/
│   ├── index.php             # List authors with book counts & search
│   ├── create.php            # Add author with uniqueness validation
│   ├── edit.php              # Edit author profile
│   └── delete.php            # Guarded deletion preventing removal of authors with books
├── categories/
│   ├── index.php             # List categories with book counts & search
│   ├── create.php            # Add category
│   ├── edit.php              # Edit category
│   └── delete.php            # Guarded deletion preventing removal of categories with books
├── assets/
│   ├── css/
│   │   └── style.css         # Custom responsive styles, badges, stat cards & layout
│   └── js/
│       └── main.js           # Modal confirmations, live search filter, alert auto-dismiss
└── docs/
    └── report.md             # Comprehensive Academic Report (3NF, ERD, Rules, Code Design)
```

---

## 6. Business Rules & Enforcement

All business rules outlined in the project brief are enforced in the PHP backend logic:

1. **Copy Availability Rule:**
   - A book with zero available copies (`available_copies = 0`) cannot be issued.
   - Enforced in `loans/issue.php`: the dropdown only displays books with `available_copies > 0`, and the backend query re-verifies availability using row-level locking (`SELECT ... FOR UPDATE`).

2. **Atomic Inventory Transaction Rule:**
   - Issuing a book decrements `available_copies` by 1 and creates the loan record.
   - Returning a book increments `available_copies` by 1 and updates `date_returned` and status.
   - Both operations use **Database Transactions** (`$pdo->beginTransaction()`, `$pdo->commit()`, `$pdo->rollBack()`). Either both changes succeed or neither does.

3. **Single Borrowing Rule:**
   - A member cannot borrow two copies of the same book at the same time.
   - Enforced in `loans/issue.php`:
     ```sql
     SELECT COUNT(*) FROM loans WHERE member_id = ? AND book_id = ? AND date_returned IS NULL
     ```
     If the count is greater than zero, issuing is blocked with a clear error message.

4. **Member Suspension Rule:**
   - Suspended members cannot borrow books.
   - Enforced in `loans/issue.php`: only members with `status = 'active'` appear in the selection menu, and the backend validates `status === 'active'`.

5. **Automated Due Date:**
   - When a book is issued, the due date is calculated automatically as **exactly 14 days** from the issue date (`date('Y-m-d', strtotime('+14 days'))`). Users cannot tamper with the date.

6. **Referential Integrity & Safe Deletion:**
   - Books with unreturned loans cannot be deleted (`books/delete.php`).
   - Members with unreturned books cannot be deleted (`members/delete.php`).
   - Authors or Categories currently assigned to existing books cannot be deleted (`authors/delete.php`, `categories/delete.php`).
   - Friendly user feedback is displayed; raw SQL foreign key exceptions are caught gracefully.

---

## 7. Bonus Features Implemented (Extra Marks)

1. **Automatic Late Fine Calculation:**
   - When returning a book after its due date, the system automatically calculates the number of overdue days and assesses a late fine at **$0.50 per day**.
   - The user is notified immediately via a detailed flash banner upon return.

2. **Interactive Dashboard Metrics:**
   - Real-time statistics cards: Total Books, Registered Members, Active Loans, and Overdue Count.
   - A dedicated **Overdue Books Alert Box** with direct contact information for quick librarian follow-up.
   - Live **Recent Loans Table** with color-coded status badges.

3. **One-Click CSV Data Export:**
   - Integrated export buttons on Books, Members, and Loans listings that generate RFC-compliant downloadable `.csv` spreadsheets for reporting and audits.

4. **Role-Based Badges & Navigation:**
   - Visual user indicators distinguishing Admin vs. Librarian roles.

5. **Client-Side Confirmation & Auto-Dismissing Alerts:**
   - Destructive operations prompt for user confirmation via JavaScript `data-confirm`.
   - Notification banners automatically fade out after 5 seconds to reduce UI clutter.

---

## 8. Sample Database Records
The database file `database/library_db.sql` automatically populates:
- **5 Categories:** Fiction, Non-Fiction, Science, History, Technology
- **5 Authors:** George Orwell, Yuval Noah Harari, Stephen Hawking, Yuval Sapiens, Robert C. Martin
- **10 Books:** Including *Clean Code*, *1984*, *A Brief History of Time*, *Sapiens*, *The Grand Design*
- **10 Members:** Alice Johnson, Bob Smith, Carol Williams, David Brown, Eva Martinez, Frank Lee, Grace Kim (Suspended), Henry Davis, Irene Wilson, James Taylor
- **2 Users:** `admin` (System Administrator), `librarian` (Head Librarian)
- **10 Loans:**
  - 3 Returned (complete historical loans)
  - 4 Active (currently out, within 14-day loan window)
  - 3 Overdue (out past due date, highlighting overdue alerts and fine calculation)

---

## 9. Security & Hardening Checklist
- **SQL Injection Prevention:** Every dynamic database interaction uses PDO prepared statements with bounded parameters (`$stmt->prepare()` + `$stmt->execute()`). Zero concatenated queries.
- **Cross-Site Scripting (XSS) Prevention:** All output rendered into HTML is sanitized using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` via the `e()` helper function.
- **Session Protection:** All administrative pages include `auth_check.php` before generating output. Unauthenticated requests are redirected to `auth/login.php`.
- **Session Hijacking Mitigation:** Upon successful login, `session_regenerate_id(true)` is executed.
- **Password Security:** Hashes are generated and verified via PHP's cryptographic functions `password_hash()` and `password_verify()`.
