# Electronic Maintenance System (EMS) — System Design Document

**Stack:** PHP · HTML · CSS · JavaScript · IBM Informix  
**Intended audience:** Junior developer / first-year intern  
**Version:** 1.0

---

## Table of Contents

1. [System Architecture Overview](#1-system-architecture-overview)
2. [User Roles and Permissions](#2-user-roles-and-permissions)
3. [Application Workflow](#3-application-workflow)
4. [Page-by-Page Structure and Navigation](#4-page-by-page-structure-and-navigation)
5. [Database Schema](#5-database-schema)
6. [Authentication and Authorization](#6-authentication-and-authorization)
7. [Complaint Lifecycle](#7-complaint-lifecycle)
8. [Message/Discussion System](#8-messagediscussion-system)
9. [Folder and Project Structure](#9-folder-and-project-structure)
10. [Development Roadmap](#10-development-roadmap)
11. [Security Considerations](#11-security-considerations)
12. [Future Enhancements](#12-future-enhancements)
13. [Assumptions, Missing Requirements, and Design Notes](#13-assumptions-missing-requirements-and-design-notes)

---

## 1. System Architecture Overview

This is a traditional server-rendered PHP web application. There is no frontend framework — PHP generates HTML pages on the server, the browser renders them, and the user navigates from page to page.

```
Browser (HTML/CSS/JS)
        │  HTTP request
        ▼
   PHP Pages (.php files)
        │  SQL queries
        ▼
  IBM Informix Database
```

**Why this architecture?**  
It is the simplest possible structure for this kind of system. Each PHP page handles one job: it receives a request, talks to the database, and sends back an HTML page. There are no APIs, no JSON endpoints, no JavaScript frameworks to learn. A developer who knows basic PHP and SQL can read any file and immediately understand what it does.

**Sessions for login state:**  
PHP sessions are used to remember who is logged in. When a user logs in successfully, their `user_id` and `role` are stored in the PHP `$_SESSION`. Every protected page checks the session at the top before doing anything else.

---

## 2. User Roles and Permissions

| Permission | Admin | User | EM |
|---|---|---|---|
| Create/edit/delete users | ✅ | ❌ | ❌ |
| Import users via CSV | ✅ | ❌ | ❌ |
| View all users | ✅ | ❌ | ❌ |
| View complaint statistics | ✅ | ❌ | ❌ |
| Submit a new complaint | ❌ | ✅ | ❌ |
| View own complaints | ❌ | ✅ | ❌ |
| Track complaint status | ❌ | ✅ | ❌ |
| View all complaints | ❌ | ❌ | ✅ |
| Update complaint status | ❌ | ❌ | ✅ |
| Send messages on a complaint | ❌ | ✅ | ✅ |

**Design decision:** The Admin is purely a management role. They do not handle complaints directly. If the client later needs the Admin to interact with complaints, that is a straightforward addition (see Section 12).

---

## 3. Application Workflow

### Landing Page
The landing page (index.php) is the first page a user sees. It contains two buttons:
- **User Login** — for regular users to access the complaint portal
- **EM Login** — for Electronic Maintenance personnel

The Admin login is accessible via a separate URL (`/admin/login.php`) that is not prominently linked on the landing page. This is a common practice for internal admin panels — it reduces accidental access and does not expose admin functionality to general users.

### Full Workflow Narrative

```
1. User opens the application in a browser.
2. They see the landing page with two login options.
3. They choose their role and are taken to the login page.
4. They enter their credentials (login ID + password).
5. PHP checks the credentials against the database.
6. If valid, a session is created and they are redirected to their dashboard.
7. From the dashboard, they perform their role-specific actions.
8. When done, they click Logout, which destroys the session and returns them to the landing page.
```

---

## 4. Page-by-Page Structure and Navigation

### Public Pages (no login required)
| Page | File | Purpose |
|---|---|---|
| Landing Page | `index.php` | Entry point. Two login buttons. |
| User Login | `login.php?role=user` | Login form for Users |
| EM Login | `login.php?role=em` | Login form for EM personnel |
| Admin Login | `admin/login.php` | Separate admin login |

### Admin Pages (requires Admin session)
| Page | File | Purpose |
|---|---|---|
| Admin Dashboard | `admin/dashboard.php` | Total users, complaint stats |
| User List | `admin/users.php` | Table of all users with Edit/Delete actions |
| Create User | `admin/user_create.php` | Form to add a single user |
| Edit User | `admin/user_edit.php?id=X` | Form to edit an existing user |
| Delete User | `admin/user_delete.php?id=X` | POST-only action, no view |
| Import Users | `admin/import_users.php` | CSV upload and preview |

### User Pages (requires User session)
| Page | File | Purpose |
|---|---|---|
| User Dashboard | `user/dashboard.php` | List of own complaints with status badges |
| New Complaint | `user/complaint_new.php` | Form to submit a new complaint |
| View Complaint | `user/complaint_view.php?id=X` | Complaint details + message thread (if Pending) |

### EM Pages (requires EM session)
| Page | File | Purpose |
|---|---|---|
| EM Dashboard | `em/dashboard.php` | List of all complaints, filterable by status |
| View Complaint | `em/complaint_view.php?id=X` | Complaint details + status updater + message thread |

### Shared Actions
| File | Purpose |
|---|---|
| `logout.php` | Destroys session, redirects to index.php |
| `message_send.php` | POST-only handler for submitting a message |

---

## 5. Database Schema

IBM Informix uses `SERIAL` for auto-increment primary keys and `DATETIME YEAR TO SECOND` for timestamps. `LVARCHAR` is used for long text fields (up to 32,739 characters by default, sufficient for all needs here).

---

### Table: `users`

Stores all system users — Admins, regular Users, and EM personnel — in a single table, differentiated by a `role` column. This is simpler than separate tables and makes login logic identical for all roles.

```sql
CREATE TABLE users (
    user_id         SERIAL          PRIMARY KEY,
    login_id        VARCHAR(20)     NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    role            VARCHAR(10)     NOT NULL,  -- 'admin', 'user', 'em'
    full_name       VARCHAR(100)    NOT NULL,
    contact_number  VARCHAR(20),
    personal_number VARCHAR(50),
    address         LVARCHAR(300),
    created_at      DATETIME YEAR TO SECOND DEFAULT CURRENT YEAR TO SECOND,
    is_active       SMALLINT        DEFAULT 1
);
```

**Column notes:**
- `login_id`: Auto-generated at creation. Format: `USR-YYYY-NNNN` for Users, `EM-NNNN` for EM staff, `ADM-001` for the Admin. Examples: `USR-2024-0043`, `EM-0012`.
- `password_hash`: The output of PHP's `password_hash()` function. Raw passwords are never stored.
- `role`: Only three allowed values. Enforced at the application layer.
- `is_active`: `1` = active, `0` = deactivated. Soft delete — user records are never physically deleted, just deactivated. This preserves complaint history.
- `personal_number`: Assumed to be a government-issued ID or employee number. Stored as a string to accommodate different formats.

---

### Table: `complaints`

Stores every complaint submitted by a User.

```sql
CREATE TABLE complaints (
    complaint_id    SERIAL          PRIMARY KEY,
    user_id         INTEGER         NOT NULL REFERENCES users(user_id),
    description     LVARCHAR(500)   NOT NULL,
    status          VARCHAR(10)     NOT NULL DEFAULT 'open',  -- 'open', 'pending', 'closed'
    submitted_at    DATETIME YEAR TO SECOND DEFAULT CURRENT YEAR TO SECOND,
    updated_at      DATETIME YEAR TO SECOND DEFAULT CURRENT YEAR TO SECOND,
    closed_at       DATETIME YEAR TO SECOND
);
```

**Column notes:**
- `user_id`: Links the complaint to the user who submitted it.
- `status`: Starts as `'open'` automatically. EM personnel are the only ones who can change it.
- `submitted_at`: Set automatically at the time of INSERT. The PHP code does not need to supply this.
- `updated_at`: Updated every time the status changes.
- `closed_at`: Null until the complaint is marked `'closed'`. Records the exact resolution timestamp.

---

### Table: `messages`

Stores all messages in the discussion thread attached to a complaint. Messages are only meaningful when a complaint is in `'pending'` status, but the table design does not enforce this — the application layer controls when the message form is shown.

```sql
CREATE TABLE messages (
    message_id      SERIAL          PRIMARY KEY,
    complaint_id    INTEGER         NOT NULL REFERENCES complaints(complaint_id),
    sender_id       INTEGER         NOT NULL REFERENCES users(user_id),
    message_text    LVARCHAR(1000)  NOT NULL,
    sent_at         DATETIME YEAR TO SECOND DEFAULT CURRENT YEAR TO SECOND
);
```

**Column notes:**
- `complaint_id`: Ties every message to exactly one complaint.
- `sender_id`: Can be a User or an EM — both can send messages on the same thread.
- `message_text`: 1000 characters is generous for a maintenance discussion thread.

---

### Entity-Relationship Summary

```
users ──────────────< complaints
  │                       │
  │                       └───────< messages
  │                                    │
  └────────────────────────────────────┘
        (sender_id also references users)
```

- One User can have many Complaints (one-to-many).
- One Complaint can have many Messages (one-to-many).
- Each Message has one Sender (many-to-one back to users).

---

### Informix-Specific Setup Notes

PHP connects to Informix using PDO with the `pdo_informix` extension, or via ODBC. Confirm which driver is installed on the server before writing any database code. A basic connection in `config/db.php` will look like:

```php
// Using PDO + ODBC DSN (most common setup for Informix on PHP)
$dsn = "odbc:DRIVER={IBM INFORMIX ODBC DRIVER};HOST=localhost;
        SERVER=ol_informix;DATABASE=ems_db;UID=informix;PWD=secret;
        PROTOCOL=onsoctcp;PORT=9088;";
$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

Use **prepared statements with named or positional placeholders** (`?`) for every query that uses user-supplied data. Never concatenate user input directly into SQL strings.

---

## 6. Authentication and Authorization

### Login Flow (Step by Step)

```
1. User submits login form (POST: login_id + password + role)
2. PHP sanitizes inputs (trim whitespace, check not empty)
3. PHP queries: SELECT * FROM users WHERE login_id = ? AND role = ? AND is_active = 1
4. If no row found → show error "Invalid credentials"
5. If row found → run password_verify($submitted_password, $row['password_hash'])
6. If password does not match → show error "Invalid credentials"
7. If password matches:
   a. Start session (session_start())
   b. Store in session: $_SESSION['user_id'], $_SESSION['role'], $_SESSION['full_name']
   c. Regenerate session ID (prevents session fixation attacks)
   d. Redirect to the correct dashboard based on role
```

**One generic error message** is used for both "user not found" and "wrong password" cases. This is intentional — different messages would tell an attacker which half of their guess was wrong.

---

### Access Control on Every Protected Page

Every page inside `admin/`, `user/`, and `em/` folders must start with an authorization check. This is done via a reusable function in `includes/auth.php`:

```
Function: require_role($required_role)
  - If session is not active → redirect to login page
  - If $_SESSION['role'] !== $required_role → redirect to login page
  - Otherwise → continue executing the page
```

Every protected page calls this function as its very first line, before any HTML output or database queries.

---

### Auto-Generating Login IDs and Passwords

When Admin creates a new User:
1. PHP counts existing `'user'` role accounts and assigns the next sequential number.
2. Login ID format: `USR-{YYYY}-{zero-padded 4 digit number}` (e.g., `USR-2024-0001`).
3. A random 8-character alphanumeric password is generated using PHP's `random_bytes()` or `bin2hex(random_bytes(4))`.
4. The plain-text password is displayed **once** on the success screen so the Admin can hand it to the user.
5. The password is immediately hashed and only the hash is stored.
6. There is no "send by email" step — Admin writes it down or prints the page.

For CSV import, the same logic is applied in a loop for each row.

---

### Logout

`logout.php` destroys the session (`session_destroy()`) and redirects to `index.php`. This is a POST action triggered by a logout button to prevent the browser back button from re-triggering logout accidentally.

---

## 7. Complaint Lifecycle

### Status States and Transitions

```
         [Submit]            [Pending]             [Resolved]
OPEN ──────────────> OPEN ──────────> PENDING ──────────> CLOSED
                     (default)         (EM action)         (EM action)
```

Status transitions are one-directional and only EM personnel can trigger them:
- `open` → `pending`: EM has picked up the complaint and is working on it.
- `pending` → `closed`: EM has resolved the complaint.
- `closed` is a terminal state. No further transitions are allowed.

**Why no backwards transitions?** Simplicity. If a complaint needs to be "re-opened," EM can add a message explaining the situation. Backwards transitions add complexity without adding meaningful value for this use case.

---

### Complaint Submission (User)

```
1. User is on the dashboard (user/dashboard.php)
2. User clicks "New Complaint"
3. User fills out the description textarea (max 500 chars, enforced client-side with JS counter + server-side with PHP check)
4. User submits the form (POST to user/complaint_new.php)
5. PHP validates the input
6. PHP inserts into complaints table with status = 'open', submitted_at = CURRENT
7. User is redirected to dashboard with a success message
```

---

### Complaint Status Update (EM)

```
1. EM is on dashboard (em/dashboard.php), sees list of complaints
2. EM clicks on a complaint
3. Complaint detail page shows: description, current status, submitter info, message thread
4. EM sees a "Update Status" button/dropdown
5. EM selects new status and submits (POST)
6. PHP checks: is the transition valid? (open→pending or pending→closed only)
7. PHP updates complaints table: status = new_status, updated_at = CURRENT
8. If status = 'closed', also sets closed_at = CURRENT
9. EM is redirected back to the complaint detail page with a success message
```

---

## 8. Message/Discussion System

Messages are only shown when a complaint's status is `'pending'`. This is enforced in both the User view and EM view: the message form and the message thread are wrapped in a PHP `if ($complaint['status'] === 'pending')` check.

### Message Display

Messages are displayed in chronological order (oldest first) using a simple query:

```sql
SELECT m.message_text, m.sent_at, u.full_name, u.role
FROM messages m
JOIN users u ON m.sender_id = u.user_id
WHERE m.complaint_id = ?
ORDER BY m.sent_at ASC
```

Each message bubble shows:
- Sender name
- Role tag (User / EM)
- Message text
- Timestamp

CSS classes differentiate the two sides: `.message-user` floats left, `.message-em` floats right (or uses background color). This gives a basic chat-like appearance without any JavaScript.

### Sending a Message

The message form is a simple `<form method="POST" action="/message_send.php">`. It contains:
- A hidden `complaint_id` field
- A `textarea` for the message
- A submit button

`message_send.php` validates the inputs, inserts into the `messages` table, and redirects back to the complaint detail page. There is no AJAX, no real-time updates. The user refreshes the page to see new messages. This is intentional simplicity.

---

## 9. Folder and Project Structure

```
ems/
├── index.php                  ← Landing page (two login buttons)
├── login.php                  ← Shared login handler (checks ?role= param)
├── logout.php                 ← Destroys session and redirects
├── message_send.php           ← POST handler for sending messages
│
├── config/
│   └── db.php                 ← Database connection (PDO/ODBC)
│
├── includes/
│   ├── auth.php               ← require_role() function
│   ├── helpers.php            ← Utility functions (generate_login_id, generate_password, etc.)
│   ├── header.php             ← HTML <head> + navigation bar partial
│   └── footer.php             ← Closing HTML tags, footer partial
│
├── admin/
│   ├── login.php              ← Admin-specific login page
│   ├── dashboard.php          ← Stats overview (user count, complaint counts by status)
│   ├── users.php              ← List all users
│   ├── user_create.php        ← Create user form + handler
│   ├── user_edit.php          ← Edit user form + handler (?id=X)
│   ├── user_delete.php        ← POST-only delete action (?id=X)
│   └── import_users.php       ← CSV upload + import logic
│
├── user/
│   ├── dashboard.php          ← User's complaint list
│   ├── complaint_new.php      ← New complaint form + handler
│   └── complaint_view.php     ← Complaint detail + message thread (?id=X)
│
├── em/
│   ├── dashboard.php          ← All complaints list, filterable
│   ├── complaint_view.php     ← Complaint detail + status updater + message thread (?id=X)
│   └── status_update.php      ← POST-only handler for status changes
│
└── assets/
    ├── css/
    │   └── style.css          ← All application styles
    └── js/
        └── main.js            ← Minor UI interactions (char counter, confirm dialogs)
```

**Key principle:** Each file handles one thing. Pages that show a form also handle the form submission (check `if ($_SERVER['REQUEST_METHOD'] === 'POST')`). This keeps related logic together and reduces the number of files a developer needs to navigate.

---

## 10. Development Roadmap

Build in this order. Each phase is independently testable before moving to the next.

### Phase 1 — Foundation (Days 1–2)
- Set up the Informix database and create all three tables.
- Create `config/db.php` with the database connection.
- Test the connection by running a simple SELECT query.
- Create `includes/auth.php`, `includes/header.php`, `includes/footer.php`.
- Create `index.php` (just the two login buttons, no logic yet).

### Phase 2 — Authentication (Days 3–4)
- Build `login.php` — form, POST handler, session creation, redirect logic.
- Build `logout.php`.
- Test: manually insert a test user into the DB and verify login/logout works.
- Build the `require_role()` gate in `auth.php` and add it to one test page.

### Phase 3 — Admin User Management (Days 5–7)
- Build `admin/users.php` (list all users from DB).
- Build `admin/user_create.php` (form + insert + auto-generate login ID and password).
- Build `admin/user_edit.php` (load user data, form + update query).
- Build `admin/user_delete.php` (soft-delete: set is_active = 0).
- Test full CRUD cycle.

### Phase 4 — Complaint Submission (Days 8–9)
- Build `user/dashboard.php` (list own complaints with status badges).
- Build `user/complaint_new.php` (form + insert, 500-char limit).
- Test: log in as a user, submit a complaint, verify it appears on the dashboard.

### Phase 5 — EM Complaint Management (Days 10–12)
- Build `em/dashboard.php` (list all complaints, show status, submitter name).
- Build `em/complaint_view.php` (complaint details + status update form).
- Build `em/status_update.php` (POST handler for status changes with transition validation).
- Build `user/complaint_view.php` (read-only complaint detail for the user).
- Test the full lifecycle: submit → open → pending → closed.

### Phase 6 — Messaging System (Days 13–14)
- Build `message_send.php` (POST handler for inserting messages).
- Add message thread display to both `user/complaint_view.php` and `em/complaint_view.php` (show only when status = 'pending').
- Test: EM marks a complaint as Pending, both User and EM exchange messages.

### Phase 7 — Admin Dashboard and CSV Import (Days 15–17)
- Build `admin/dashboard.php` (query COUNT for each complaint status, display summary).
- Build `admin/import_users.php` (upload CSV, parse with `fgetcsv()`, loop through rows and insert users, generate login IDs and passwords, display results).

### Phase 8 — Polish and Testing (Days 18–20)
- Apply consistent CSS styling across all pages.
- Add JavaScript character counter to the complaint description textarea.
- Add confirmation dialogs for delete actions.
- Test all edge cases: empty forms, invalid transitions, duplicate login IDs, SQL errors.
- Review all pages for missing authorization checks.

---

## 11. Security Considerations

These are practical, beginner-appropriate measures that cover the most important vulnerabilities for a simple internal PHP application.

### Prepared Statements — Most Important
Never build SQL queries by concatenating user input. Use PDO prepared statements for every query involving user-supplied data:

```php
// WRONG — never do this
$query = "SELECT * FROM users WHERE login_id = '" . $_POST['login_id'] . "'";

// CORRECT
$stmt = $pdo->prepare("SELECT * FROM users WHERE login_id = ?");
$stmt->execute([$_POST['login_id']]);
```

This prevents SQL injection — the most common and most damaging attack on PHP applications.

### Password Hashing
- Use `password_hash($password, PASSWORD_DEFAULT)` to store passwords.
- Use `password_verify($input, $stored_hash)` to check them at login.
- Never store or log plain-text passwords anywhere.

### Output Escaping (XSS Prevention)
Wrap every variable before printing it inside HTML with `htmlspecialchars()`:

```php
echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8');
```

This prevents Cross-Site Scripting (XSS), where an attacker injects JavaScript into your pages by submitting it as form data.

### Session Security
- Call `session_regenerate_id(true)` immediately after a successful login.
- Set a session timeout: if `$_SESSION['last_active']` is more than 30 minutes ago, destroy the session and redirect to login.
- Set `session.cookie_httponly = 1` in `php.ini` (prevents JavaScript from reading the session cookie).

### Form Submission Method
All actions that change data (create, update, delete, send message) must use `method="POST"`. Never use GET requests for actions that modify the database. This prevents accidental re-submissions when the browser back button is used, and prevents CSRF via simple URL manipulation.

### Role-Based Access on Every Page
Every protected page must call `require_role($role)` as its very first line. If this check is missing from even one page, a user can bypass access control by navigating directly to that URL.

### File Upload Safety (CSV Import)
- Check the uploaded file's extension and MIME type before processing.
- Use `fgetcsv()` to parse CSV — do not use `eval()` or `exec()` on file contents.
- Reject files above a reasonable size limit (e.g., 1MB).

### Minimal Error Exposure
In production, turn off PHP error display in `php.ini`:
```
display_errors = Off
log_errors = On
error_log = /path/to/ems_errors.log
```
Detailed error messages should go to a log file, not to the browser screen where they could expose database structure or file paths.

---

## 12. Future Enhancements

These are not needed now but are worth designing the system to accommodate.

- **Password Reset:** Allow Admin to reset a user's password, which generates a new random password and displays it.
- **Email Notifications:** Send an email to the User when their complaint status changes. Requires a mail server (SMTP) and PHP's `mail()` function or PHPMailer library.
- **Complaint Categories:** Add a `category` column to `complaints` (e.g., Electrical, Plumbing, Network). Useful for EM to filter and assign work.
- **Assigned EM Personnel:** Add an `assigned_em_id` column to `complaints` so a specific EM person is responsible for each complaint.
- **Admin Can View Complaints:** A simple read-only view for Admin to audit all complaints without being able to modify them.
- **Search and Filtering:** Add search by name or login ID on the Admin user list; filter complaints by date range or status on the EM dashboard.
- **Audit Log:** A `audit_log` table that records who changed what and when (e.g., "EM-0003 changed complaint #45 from open to pending at 14:32").
- **Complaint Attachments:** Allow users to upload a photo (e.g., a photo of a broken piece of equipment) when submitting a complaint.
- **Printable Complaint Report:** A print-friendly view of a closed complaint showing the full history from submission to resolution.

---

## 13. Assumptions, Missing Requirements, and Design Notes

### Clarifications Assumed in This Design

**"Personal Number"** is assumed to mean a government-issued identification number or an official employee/personnel number. It is stored as a plain text string to handle varying formats.

**EM personnel accounts** are created and managed by the Admin, the same way User accounts are. The `role` column distinguishes them. The Admin cannot directly create another Admin account (that is a manual DB operation).

**Single Admin account** is assumed. There is one Admin for this system. If multiple admins are needed in the future, the `users` table already supports it — just insert another row with `role = 'admin'`.

**Messages are append-only.** Messages cannot be edited or deleted after sending. This keeps the implementation simple and also preserves a clean audit trail of the discussion.

**No email notifications in v1.** The system assumes Admin will physically hand credentials to users after creation. If this is impractical, a password reset mechanism should be added early.

**The complaint description is typed text only.** There are no file attachments in this version.

### Potential Issues to Address Before Development

**Informix Driver on the Server:** Confirm that the PHP server has the Informix PDO driver (`pdo_informix`) or ODBC driver installed and configured before writing any database code. This is the most common first blocker on Informix + PHP setups. If neither is available, ODBC via `odbc_connect()` is the fallback.

**Character Set:** Confirm the Informix database character set (typically `en_US.8859-1` or `en_US.utf8`). Set the PHP connection character set to match, otherwise special characters in names or addresses may display incorrectly.

**Login ID Generation Race Condition:** If two Admin users try to create accounts simultaneously, the sequential ID generation could assign the same ID twice. For a small internal system this is negligible, but the `UNIQUE` constraint on `login_id` will catch it at the database level and PHP should display a friendly retry message.

**Status Transition Enforcement:** PHP must explicitly validate that the requested transition is legal (`open→pending` or `pending→closed`) before running the UPDATE query. Do not rely on the form alone — a malicious or confused user could POST directly to `status_update.php` with arbitrary values.

**Soft Delete vs. Hard Delete:** The design uses `is_active = 0` for "deleting" users. This preserves complaint history integrity. The Admin user list should only show `is_active = 1` users by default, with an optional "Show deactivated" toggle if needed.

**CSV Import Format:** The expected CSV column order for import must be documented clearly for Admin users. A sample CSV file should be provided alongside the application. Suggested column order: `Full Name, Contact Number, Personal Number, Address`.

**No "Forgot Password" Flow:** There is no self-service password reset. Users who forget their password must contact the Admin for a reset. This is acceptable for a small internal system.
