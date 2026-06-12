# Electronic Maintenance System (EMS) — System Design Document

**Stack:** PHP · HTML · CSS · JavaScript · MySQL
**Intended audience:** Junior developer / first-year intern
**Version:** 1.1

---

## Table of Contents

1.  [Default Credentials](#0-default-credentials)
2.  [System Architecture Overview](#1-system-architecture-overview)
3.  [User Roles and Permissions](#2-user-roles-and-permissions)
4.  [Application Workflow](#3-application-workflow)
5.  [Page-by-Page Structure and Navigation](#4-page-by-page-structure-and-navigation)
6.  [Database Schema](#5-database-schema)
7.  [Authentication and Authorization](#6-authentication-and-authorization)
8.  [Complaint Lifecycle](#7-complaint-lifecycle)
9.  [Message/Discussion System](#8-messagediscussion-system)
10. [Folder and Project Structure](#9-folder-and-project-structure)
11. [Development Roadmap](#10-development-roadmap)
12. [Security Considerations](#11-security-considerations)
13. [Future Enhancements](#12-future-enhancements)
14. [Assumptions, Missing Requirements, and Design Notes](#13-assumptions-missing-requirements-and-design-notes)
15. [Changes Still Needed](#14-changes-still-needed)

---

## 0. Default Credentials

The system comes with three pre-configured user accounts for testing and development:

| Role | Login ID | Password | Full Name |
|---|---|---|---|
| Admin | `ADM-001` | `admin123` | System Administrator |
| User | `USR-2026-0001` | `Password123` | Demo User |
| EM | `EM-0001` | `Password123` | Demo EM |

**Login paths:**
- **Admin login:** Navigate to `/login.php?role=admin` (or `/admin/login.php`, which redirects there)
- **User login:** From the landing page (`/index.php`), click "User Login" or go to `/login.php?role=user`
- **EM login:** From the landing page (`/index.php`), click "EM Login" or go to `/login.php?role=em`

**Note:** These credentials are hardcoded in `scripts/init_db.php` and are automatically created when the database is initialized. Change these passwords immediately in a production environment. To reset the database and recreate default users, run `php scripts/init_db.php`. The admin password can be overridden before running init by setting the `EMS_ADMIN_PASSWORD` environment variable.

---

## 1. System Architecture Overview

This is a traditional server-rendered PHP web application. There is no frontend framework — PHP generates HTML pages on the server, the browser renders them, and the user navigates from page to page.

```
Browser (HTML/CSS/JS)
        │  HTTP request
        ▼
   PHP Pages (.php files)
        │  SQL queries via PDO
        ▼
  MySQL database (or SQLite via EMS_DB_DSN)
```

**Why this architecture?**
It is the simplest possible structure for this kind of system. Each PHP page handles one job: it receives a request, talks to the database, and sends back an HTML page. There are no APIs, no JSON endpoints, no JavaScript frameworks to learn. A developer who knows basic PHP and SQL can read any file and immediately understand what it does.

**Sessions for login state:**
PHP sessions are used to remember who is logged in. When a user logs in successfully, their `user_id`, `role`, `full_name`, and `login_id` are stored in the PHP `$_SESSION`. Every protected page checks the session at the top before doing anything else.

**Database:**
The primary database is MySQL. The connection is configured in `config/db.php` (git-ignored; copy from `config/db.example.php`). The DSN can be overridden via the `EMS_DB_DSN` environment variable, which also allows SQLite to be used for local development if preferred.

---

## 2. User Roles and Permissions

| Permission | Admin | User | EM |
|---|---|---|---|
| Create/edit/delete users | ✅ | ❌ | ❌ |
| Import users via CSV | ✅ | ❌ | ❌ |
| View all users | ✅ | ❌ | ❌ |
| View complaint statistics | ✅ | ❌ | ❌ |
| View all complaints (read-only) | ✅ | ❌ | ❌ |
| Submit a new complaint | ❌ | ✅ | ❌ |
| View own complaints | ❌ | ✅ | ❌ |
| Track complaint status | ❌ | ✅ | ❌ |
| View all complaints | ❌ | ❌ | ✅ |
| Update complaint status | ❌ | ❌ | ✅ |
| Send messages on a complaint | ❌ | ✅ | ✅ |

**Design decision:** The Admin is a management role. They can view complaints in read-only mode for auditing purposes but cannot modify them. If the client later needs the Admin to interact with complaints directly, that is a straightforward addition (see Section 12).

---

## 3. Application Workflow

### Landing Page
The landing page (`index.php`) is the first page a user sees. The navigation bar contains login links for all three roles. The Admin login is also accessible via `/admin/login.php`, which simply redirects to the shared login page with the admin role pre-set.

### Full Workflow Narrative

```
1. User opens the application in a browser.
2. They see the landing page and click a login link from the nav bar.
3. They enter their credentials (login ID + password).
4. PHP checks the credentials against the database.
5. If valid, a session is created and they are redirected to their dashboard.
6. From the dashboard, they perform their role-specific actions.
7. When done, they click Logout, which destroys the session and returns them to the landing page.
```

---

## 4. Page-by-Page Structure and Navigation

### Public Pages (no login required)
| Page | File | Purpose |
|---|---|---|
| Landing Page | `index.php` | Entry point with role login links in the nav bar |
| Login | `login.php?role=user\|em\|admin` | Shared login handler — role is set via query string |

### Admin Pages (requires Admin session)
| Page | File | Purpose |
|---|---|---|
| Admin Dashboard | `admin/dashboard.php` | Total users and complaint stats by status |
| User List | `admin/users.php` | Table of all users with Edit/Deactivate actions |
| Create User | `admin/user_create.php` | Form to add a single user |
| Edit User | `admin/user_edit.php?id=X` | Form to edit an existing user |
| Delete User | `admin/user_delete.php` | POST-only action; soft-deletes the user |
| Import Users | `admin/import_users.php` | CSV upload and preview of generated credentials |
| All Complaints | `admin/complaints.php` | Read-only complaint list, filterable by status |
| Complaint Detail | `admin/complaint_view.php?id=X` | Read-only complaint detail and message thread |

### User Pages (requires User session)
| Page | File | Purpose |
|---|---|---|
| User Dashboard | `user/dashboard.php` | List of own complaints with status badges |
| New Complaint | `user/complaint_new.php` | Form to submit a new complaint |
| View Complaint | `user/complaint_view.php?id=X` | Complaint details + message thread (if Pending) |

### EM Pages (requires EM session)
| Page | File | Purpose |
|---|---|---|
| EM Dashboard | `em/dashboard.php` | List of all complaints, sortable by field |
| View Complaint | `em/complaint_view.php?id=X` | Complaint details + status updater + message thread |

### Shared Actions
| File | Purpose |
|---|---|
| `logout.php` | POST-only; destroys session and redirects to `index.php` |
| `message_send.php` | POST-only handler for submitting a message |

---

## 5. Database Schema

The production database is MySQL. Tables are created by `scripts/init_db.php`. The same script seeds the three default accounts.

---

### Table: `users`

Stores all system users — Admins, regular Users, and EM personnel — in a single table differentiated by a `role` column.

```sql
CREATE TABLE users (
    user_id         INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login_id        VARCHAR(20)      NOT NULL UNIQUE,
    password_hash   VARCHAR(255)     NOT NULL,
    role            ENUM('admin','user','em') NOT NULL,
    full_name       VARCHAR(100)     NOT NULL,
    contact_number  VARCHAR(20)      NULL,
    personal_number VARCHAR(50)      NULL,
    address         VARCHAR(300)     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active       TINYINT(1)       NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Column notes:**
- `login_id`: Auto-generated at creation. Format: `USR-YYYY-NNNN` for Users, `EM-NNNN` for EM staff, `ADM-NNNN` for Admins. Examples: `USR-2026-0043`, `EM-0012`.
- `password_hash`: The output of PHP's `password_hash()`. Raw passwords are never stored.
- `role`: Only three allowed values. Enforced at the application layer via an `ENUM`.
- `is_active`: `1` = active, `0` = deactivated. Soft delete — user records are never physically removed. This preserves complaint history.
- `personal_number`: Assumed to be a government-issued ID or employee number. Stored as a string to accommodate different formats.

---

### Table: `complaints`

Stores every complaint submitted by a User.

```sql
CREATE TABLE complaints (
    complaint_id    INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED     NOT NULL,
    description     VARCHAR(500)     NOT NULL,
    status          ENUM('open','pending','closed') NOT NULL DEFAULT 'open',
    submitted_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at       DATETIME         NULL,
    CONSTRAINT fk_complaints_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Column notes:**
- `status`: Starts as `'open'` automatically. Only EM personnel can change it.
- `submitted_at`: Set automatically at INSERT time.
- `updated_at`: Auto-updated on every row change via `ON UPDATE CURRENT_TIMESTAMP`.
- `closed_at`: Null until the complaint is marked `'closed'`. Records the exact resolution timestamp.

---

### Table: `messages`

Stores all messages in the discussion thread attached to a complaint.

```sql
CREATE TABLE messages (
    message_id      INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
    complaint_id    INT UNSIGNED     NOT NULL,
    sender_id       INT UNSIGNED     NOT NULL,
    message_text    VARCHAR(1000)    NOT NULL,
    sent_at         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id),
    CONSTRAINT fk_messages_sender    FOREIGN KEY (sender_id)    REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Column notes:**
- `complaint_id`: Ties every message to exactly one complaint.
- `sender_id`: Can be a User or an EM — both can send messages on the same thread.
- `message_text`: 1000 characters max.

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

### Setup

```bash
# 1. Copy and configure the database connection
cp config/db.example.php config/db.php
# Edit config/db.php with your MySQL credentials

# 2. Create tables and seed default accounts
php scripts/init_db.php

# 3. (Optional) override the admin password before seeding
EMS_ADMIN_PASSWORD=mysecretpassword php scripts/init_db.php
```

To use SQLite instead (local development only), set:
- `EMS_DB_DSN=sqlite:/path/to/ems.sqlite` — full PDO DSN
- `EMS_DB_PATH=/path/to/ems.sqlite` — path only (DSN is built automatically)

Use **prepared statements with positional placeholders** (`?`) for every query that uses user-supplied data. Never concatenate user input directly into SQL strings.

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
   b. Store in session: user_id, role, full_name, login_id
   c. Regenerate session ID (prevents session fixation attacks)
   d. Redirect to the correct dashboard based on role
```

**One generic error message** is used for both "user not found" and "wrong password" cases to prevent user enumeration.

---

### Access Control on Every Protected Page

Every page inside `admin/`, `user/`, and `em/` must start with an authorization check via `require_role()` in `includes/auth.php`:

```
Function: require_role($required_role)
  - If session is not active → redirect to login page
  - If $_SESSION['role'] !== $required_role → redirect to login page
  - Otherwise → continue executing the page
```

Every protected page calls this function as its very first line, before any HTML output or database queries.

---

### Auto-Generating Login IDs and Passwords

When Admin creates a new user:
1. PHP finds the last existing login ID for that role and increments the number suffix (uses `ORDER BY user_id DESC LIMIT 1`, not `COUNT`, so gaps from deactivated accounts never cause duplicates).
2. Login ID format: `USR-{YYYY}-{zero-padded 4 digits}`, `EM-{0000}`, or `ADM-{0000}`.
3. A random 10-character password is generated using `random_int()` over a defined alphabet (no ambiguous characters).
4. The plain-text password is displayed **once** on the success screen.
5. Only the bcrypt hash is stored.

For CSV import, the same logic runs in a loop for each row.

---

### Logout

`logout.php` accepts POST only. It calls `session_unset()` then `session_destroy()` and redirects to `index.php`. POST-only prevents the browser back button from accidentally re-triggering logout.

---

## 7. Complaint Lifecycle

### Status States and Transitions

```
         [Submit]            [EM action]           [EM action]
  ──────────────────> OPEN ──────────> PENDING ──────────> CLOSED
                     (default)                            (terminal)
```

Transitions are one-directional and only EM personnel can trigger them:
- `open` → `pending`: EM has picked up the complaint and is working on it.
- `pending` → `closed`: EM has resolved the complaint.
- `closed` is a terminal state. No further transitions are allowed.

---

### Complaint Submission (User)

```
1. User navigates to user/complaint_new.php
2. User fills out the description textarea (max 500 chars)
   — live character counter updates via main.js
3. User submits the form (POST)
4. PHP validates length with mb_strlen()
5. PHP inserts into complaints table: status = 'open'
6. User is redirected to dashboard with a success message
```

---

### Complaint Status Update (EM)

```
1. EM opens em/complaint_view.php
2. The "Update Status" section shows a context-sensitive button:
   — "Mark as Pending" when status is open
   — "Close Complaint" when status is pending
   — No button when status is closed
3. EM submits the form (POST to em/status_update.php)
4. PHP validates the transition (open→pending or pending→closed only)
5. PHP updates complaints: status, updated_at = NOW()
6. If closing: also sets closed_at = NOW()
7. EM is redirected back to the complaint detail page
```

---

## 8. Message/Discussion System

Messages are only shown when a complaint's status is `'pending'`. This is enforced in both the User and EM views: the message form and thread are wrapped in a PHP `if ($complaint['status'] === 'pending')` check.

### Message Display

Messages are displayed in chronological order (oldest first):

```sql
SELECT m.message_text, m.sent_at, u.full_name, u.role
FROM messages m
JOIN users u ON m.sender_id = u.user_id
WHERE m.complaint_id = ?
ORDER BY m.sent_at ASC
```

Each message shows: sender name, role tag (user / em), message text, and timestamp.

### Sending a Message

The message form POSTs to `message_send.php`, which:
1. Verifies the session and that the role is `user` or `em`.
2. Verifies the complaint exists and its status is `pending`.
3. For `user` role: verifies the complaint belongs to their `user_id`.
4. Inserts into the `messages` table.
5. Redirects back to the complaint detail page.

There is no AJAX and no real-time updates. The page must be refreshed to see new messages.

### Append-Only

Messages cannot be edited or deleted after sending. This preserves a clean audit trail.

---

## 9. Folder and Project Structure

```
ems/
├── index.php                  ← Landing page
├── login.php                  ← Shared login handler (?role=user|em|admin)
├── logout.php                 ← POST-only session destroyer
├── message_send.php           ← POST handler for messages
│
├── config/
│   ├── db.php                 ← Database connection — git-ignored, do not commit
│   └── db.example.php         ← Template: copy to db.php and fill in credentials
│
├── includes/
│   ├── auth.php               ← require_role(), authenticate_login(), session helpers
│   ├── helpers.php            ← generate_login_id(), generate_password(), text_truncate()
│   ├── header.php             ← HTML <head> + navigation bar partial
│   └── footer.php             ← Closing HTML tags + main.js script tag
│
├── admin/
│   ├── login.php              ← Redirects to /login.php?role=admin
│   ├── dashboard.php          ← User counts + complaint stats by status
│   ├── users.php              ← List all users; toggle deactivated view
│   ├── user_create.php        ← Create user; auto-generate credentials
│   ├── user_edit.php          ← Edit user details and active status
│   ├── user_delete.php        ← POST-only soft-delete (is_active = 0)
│   ├── import_users.php       ← CSV bulk import with credential preview
│   ├── complaints.php         ← Read-only complaint list, filter by status
│   └── complaint_view.php     ← Read-only complaint detail + message thread
│
├── user/
│   ├── dashboard.php          ← User's own complaint list
│   ├── complaint_new.php      ← New complaint form (500-char limit)
│   └── complaint_view.php     ← Complaint detail + message thread (pending only)
│
├── em/
│   ├── dashboard.php          ← All complaints, sortable by column
│   ├── complaint_view.php     ← Complaint detail + status updater + message thread
│   └── status_update.php      ← POST-only status transition handler
│
├── assets/
│   ├── css/style.css          ← All application styles
│   └── js/main.js             ← Character counter (data-char-counter attribute)
│
├── scripts/
│   └── init_db.php            ← Creates MySQL tables and seeds default accounts
│
├── .gitignore                 ← Excludes config/db.php
└── nixpacks.toml              ← Deployment: PHP 8.2 + pdo + pdo_mysql
```

**Key principle:** Each file handles one thing. Pages that show a form also handle the POST submission (check `$_SERVER['REQUEST_METHOD'] === 'POST'`). This keeps related logic together and reduces the number of files to navigate.

---

## 10. Development Roadmap

### Phase 1 — Foundation (Days 1–2)
- Set up the MySQL database and create all three tables via `scripts/init_db.php`.
- Create `config/db.php` with the PDO connection.
- Create `includes/auth.php`, `includes/header.php`, `includes/footer.php`.
- Create `index.php`.

### Phase 2 — Authentication (Days 3–4)
- Build `login.php` — form, POST handler, session creation, redirect.
- Build `logout.php`.
- Build `require_role()` in `auth.php`.

### Phase 3 — Admin User Management (Days 5–7)
- Build `admin/users.php`, `admin/user_create.php`, `admin/user_edit.php`, `admin/user_delete.php`.
- Test full CRUD cycle including soft-delete.

### Phase 4 — Complaint Submission (Days 8–9)
- Build `user/dashboard.php` and `user/complaint_new.php`.

### Phase 5 — EM Complaint Management (Days 10–12)
- Build `em/dashboard.php`, `em/complaint_view.php`, `em/status_update.php`.
- Build `user/complaint_view.php` (read-only detail for the submitting user).

### Phase 6 — Messaging System (Days 13–14)
- Build `message_send.php`.
- Add message thread display to both `user/complaint_view.php` and `em/complaint_view.php`.

### Phase 7 — Admin Dashboard and CSV Import (Days 15–17)
- Build `admin/dashboard.php`.
- Build `admin/import_users.php`.
- Build `admin/complaints.php` and `admin/complaint_view.php` (read-only complaint access for Admin).

### Phase 8 — Polish and Testing (Days 18–20)
- Apply consistent CSS across all pages.
- Add JavaScript character counter to the complaint textarea.
- Test all edge cases: empty forms, invalid transitions, duplicate login IDs, direct POST attacks.
- Review all pages for missing `require_role()` calls.

---

## 11. Security Considerations

### Prepared Statements — Most Important
Never build SQL queries by concatenating user input:

```php
// WRONG
$query = "SELECT * FROM users WHERE login_id = '" . $_POST['login_id'] . "'";

// CORRECT
$stmt = $pdo->prepare("SELECT * FROM users WHERE login_id = ?");
$stmt->execute([$_POST['login_id']]);
```

### Password Hashing
- `password_hash($password, PASSWORD_DEFAULT)` to store.
- `password_verify($input, $stored_hash)` to check at login.
- Never store or log plain-text passwords.

### Output Escaping (XSS Prevention)
Every variable printed inside HTML must be wrapped:

```php
echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
```

### Session Security
- `session_regenerate_id(true)` immediately after successful login.
- `session.cookie_httponly = 1` in `php.ini` (prevents JS from reading the session cookie).
- Consider a 30-minute idle timeout using `$_SESSION['last_active']`.

### Form Submission Method
All state-changing actions (create, update, delete, send message, logout) use `method="POST"`. Pages that receive a non-POST request redirect away without processing.

### Role-Based Access on Every Page
Every protected page must call `require_role($role)` as its very first line.

### File Upload Safety (CSV Import)
- `fgetcsv()` is used to parse CSV safely — no `eval()` or `exec()`.
- File extension and MIME type validation and a size limit (e.g. 1 MB) should be added (see Section 14).

### Minimal Error Exposure
In production, set in `php.ini`:
```
display_errors = Off
log_errors = On
error_log = /path/to/ems_errors.log
```

---

## 12. Future Enhancements

- **Password Reset:** Allow Admin to reset a user's password and display the new plain-text credential once.
- **Email Notifications:** Email the User when their complaint status changes (PHPMailer + SMTP).
- **Complaint Categories:** Add a `category` column (Electrical, Plumbing, Network) for EM filtering.
- **Assigned EM Personnel:** An `assigned_em_id` column on complaints to track responsibility.
- **Search and Filtering:** Search by name/login on the Admin user list; filter complaints by date range.
- **Audit Log:** An `audit_log` table recording who changed what and when.
- **Complaint Attachments:** Allow users to upload a photo with their complaint submission.
- **Printable Complaint Report:** A print-friendly view of a closed complaint showing the full history.

---

## 13. Assumptions, Missing Requirements, and Design Notes

**"Personal Number"** is assumed to mean a government-issued identification number or employee number, stored as plain text.

**EM accounts** are created by the Admin the same way User accounts are, differentiated only by the `role` column.

**Single Admin account** is assumed for v1. The schema already supports multiple admins.

**Messages are append-only.** They cannot be edited or deleted, preserving a clean audit trail.

**No email notifications in v1.** Admin physically hands credentials to users after creation.

**Complaint descriptions are typed text only.** No file attachments in this version.

**Login ID generation race condition:** If two accounts are created simultaneously, both could compute the same next ID. The `UNIQUE` constraint on `login_id` catches this at the database level; PHP should display a friendly retry message.

**Status transition enforcement:** PHP validates the transition in `em/status_update.php` — never rely on the form alone. A direct POST with arbitrary values must be rejected server-side.

**Soft delete:** `is_active = 0` is used instead of hard delete to preserve complaint history integrity. The Admin user list shows only active users by default; a "Show deactivated" toggle reveals the rest.

**No "Forgot Password" flow:** Users who forget their password must contact Admin.

---

## 14. Changes Still Needed

This section lists gaps and corrections between the original design document and the actual implementation, plus outstanding work that should be completed before the project is considered production-ready.

---

### Corrections to the Original README

| # | Original claim | Actual state |
|---|---|---|
| 1 | Database described as SQLite with file at `data/ems.sqlite` | Production database is **MySQL**. SQLite is still supported as a fallback via `EMS_DB_DSN` but is not the default. The schema in `scripts/init_db.php` uses MySQL syntax (`ENGINE=InnoDB`, `ENUM`, `ON UPDATE CURRENT_TIMESTAMP`). |
| 2 | Admin login described as a separate page at `admin/login.php` | `admin/login.php` exists but only issues a redirect to `/login.php?role=admin`. There is no separate admin login form — all roles share `login.php`. |
| 3 | Landing page described as having two login buttons (User and EM only) | The landing page no longer has login buttons. Login links for all three roles appear in the **nav bar** on every page. |
| 4 | Session stores `user_id`, `role`, `full_name` | Session also stores **`login_id`** (set in `start_auth_session()` in `includes/auth.php`). |
| 5 | Password generation described as 8 characters (`bin2hex(random_bytes(4))`) | Actual implementation generates **10 characters** using `random_int()` over a custom alphabet (no ambiguous characters like `0`, `O`, `l`, `1`). |
| 6 | `admin/` section does not list complaint pages | Two admin complaint pages now exist: `admin/complaints.php` (filterable list) and `admin/complaint_view.php` (read-only detail + message thread). The permissions table has been updated above to reflect this. |
| 7 | EM dashboard described as filterable by status | Actual EM dashboard (`em/dashboard.php`) is **sortable by column** (ID, status, user name, submitted date) — not filtered. Filtering by status exists only in the Admin complaints view. |

---

### Outstanding Work (Not Yet Implemented)

#### High Priority — Should be done before any real use

- **CSV file validation (import_users.php):** The current implementation does not check the uploaded file's MIME type or enforce a maximum file size. Add both before allowing public-facing use.

- **Session idle timeout:** The login flow regenerates the session ID on login but does not enforce a timeout for idle sessions. Add a `last_active` timestamp to the session and redirect to login after 30 minutes of inactivity.

- **CSRF protection on forms:** State-changing forms (status update, message send, user delete) are not protected by a CSRF token. Add a random token stored in the session and validated on each POST. This is especially important for `admin/user_delete.php` and `em/status_update.php`.

- **`php.ini` hardening for production:** `display_errors` should be confirmed off and `error_log` should be configured. Document the required `php.ini` settings in a deployment checklist.

#### Medium Priority — Quality and usability improvements

- **Friendly retry on duplicate login ID:** If two accounts are created simultaneously and the `UNIQUE` constraint fires, the user sees a raw PDO exception. Catch the exception in `user_create.php` and `import_users.php` and display a human-readable message.

- **"Show deactivated" toggle on Admin user list:** The toggle exists in `admin/users.php` but deactivated users have no visual distinction (different row colour, strikethrough) to make them immediately recognizable. Add a CSS class for inactive rows.

- **Pagination:** The complaint lists in `admin/complaints.php` and `em/dashboard.php` load all rows in one query. Add `LIMIT` / `OFFSET` pagination once the dataset grows.

- **Character counter on complaint form:** `user/complaint_new.php` uses the `data-char-counter` attribute and `main.js` — but the counter text reads `"0 characters"` on load rather than reflecting any pre-filled value from a failed submission. Ensure the counter updates on page load in addition to on input.

#### Low Priority — Nice to have

- **Sample CSV file:** The original README recommends providing a sample CSV for the bulk import feature. No sample file has been added to the repo yet.

- **Confirm dialog on Deactivate button:** `admin/users.php` has `onclick="return confirm(...)"` on the Deactivate button, but there is no equivalent protection on any other destructive action. Audit all destructive actions and ensure a confirmation step is present.

- **Admin password reset:** Admins currently have no way to reset a user's password through the UI. Implement a "Reset Password" action on `admin/user_edit.php` that generates a new credential and displays it once.

- **`admin/login.php` redirect notice:** The redirect in `admin/login.php` is a bare `header('Location: ...')` with no fallback HTML for clients that do not follow redirects. Add a `<meta http-equiv="refresh">` fallback and a link.
  DvcddQ0jp13Sk
