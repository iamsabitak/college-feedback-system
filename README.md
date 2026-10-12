# College Online Feedback System

Built exactly to the stack named in Section 9.2 of the project proposal:
**HTML5 / CSS3 / JavaScript** (frontend), **PHP** (backend), **MySQL** (database), run locally on **XAMPP**.

## Folder structure

```
college-feedback-system/
├── public-portal/     ← Frontend Portal (feedback form, home, about, contact)
├── admin-portal/      ← Backend/Admin Portal (login, dashboard, search/filter/delete)
├── database/
│   ├── schema.sql             ← Run this once to create the database + tables
│   ├── migration_status.sql   ← Run once to add feedback workflow statuses
│   ├── migration_otp.sql      ← Run once to add OTP password recovery
│   ├── migration_profile.sql  ← Run once to add admin profile fields
│   └── migration_notifications.sql ← Run once to add per-admin notification read state
└── composer.json      ← PHPMailer dependency
```

Two folders, one per portal, matching the two-portal scope described in Section 4.

## Setup (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL** from the control panel.
2. Copy the whole `college-feedback-system` folder into your XAMPP `htdocs` directory
   (e.g. `C:\xampp\htdocs\college-feedback-system`, `/Applications/XAMPP/htdocs/college-feedback-system`,
   or `/opt/lampp/htdocs/college-feedback-system` on Linux).
3. Import the database schema from the project root. On this local setup, MySQL uses `root` with
   password `root`:
   ```sh
   mysql -u root -proot < database/schema.sql
   ```
   This creates the `college_feedback_system` database, the `feedback` and `admin` tables, a
   default admin account, and sample feedback rows. You can also import `schema.sql` through
   phpMyAdmin.
4. Visit:
   - Public portal: `http://localhost/college-feedback-system/public-portal/`
   - Admin portal: `http://localhost/college-feedback-system/admin-portal/login.php`

### Status migration

Import `database/migration_status.sql` once after `database/schema.sql`, including for a new
installation, before using the updated admin dashboard. It adds the `New`, `Reviewed`, and
`Resolved` status column to feedback entries; existing entries start as `New`.

### OTP password recovery setup

Import `database/migration_otp.sql` after the schema and status migrations:

```sh
mysql -u root -p < database/migration_otp.sql
# Enter password: root
```

Non-interactively, the local root/root setup can use:

```sh
mysql -u root -proot < database/migration_otp.sql
```

The command `mysql -u root -p database/migration_otp.sql` from the original setup note is not a
valid file import: MySQL interprets the final argument as a database name. Use the `<` redirection
shown above. The migration adds the administrator email and OTP table, then assigns
`sabitakhadka091@gmail.com` to the default `admin` account. Both portal DB includes are configured
for the local MySQL credentials `root` / `root`.

Install PHPMailer from the project root (Composer must be installed):

```sh
composer require phpmailer/phpmailer
```

OTP email and the public contact form use Gmail SMTP through the shared PHPMailer helper. On the
Gmail account `sabitakhadka091@gmail.com`, enable **2-Step Verification**, then create an **App
Password** at <https://myaccount.google.com/apppasswords>. Configure the generated App Password
as the `OTP_SMTP_APP_PASSWORD` environment variable, or put it in the Git-ignored local file
`admin-portal/includes/mailer.local.php`:

```php
<?php
return ['app_password' => 'your Google App Password'];
```

Do not use the account password or commit the App Password. Contact form messages are delivered to
`sabitakhadka091@gmail.com`; replies use the sender's validated email address. OTP codes expire
after 60 seconds, and another request is rate-limited for 60 seconds server-side.

### Public contact form

The Contact page includes a message form for name, email, subject, and message. Submissions are
validated on the server and sent through PHPMailer; delivery errors are logged privately and shown
to the visitor as a generic retry message. On desktop, the map and directions note are stacked
above Office Hours in the left column, and the message form fills the right column. On mobile, the
map and Office Hours appear first, followed by the form. The campus social links and feedback call
to action remain in the Office Hours panel.

### Admin profile setup

Import the profile migration after the OTP migration. It is safe to run if the OTP email column
already exists and adds `phone` and `updated_at` when they are missing:

```sh
mysql -u root -proot < database/migration_profile.sql
```

Profile username and phone updates save in place. Email updates are held until a 6-digit code sent
to the proposed new address is verified.

### Feedback notifications

The admin bell uses **Approach B: authenticated polling**, so no WebSocket process or extra port is
needed. Import the read-state migration once after `schema.sql` and the OTP migration:

```sh
mysql -u root -proot < database/migration_notifications.sql
```

`admin-portal/js/admin.js` checks `check_new_feedback.php` immediately when an admin page opens,
then every 12 seconds. This is ordinary HTTP polling: each check is a short-lived request, not a
persistent WebSocket connection, so the PHP server may log a connection as accepted and then
closed for each check. Each result and read-state update is scoped to the signed-in admin; unread
counts and read flags come from MySQL, so other tabs see changes on their next poll. Notification
details include the submitter name only for non-anonymous feedback; anonymous payloads never
contain that field. Start the same PHP/Apache server used for the portals; no separate notification
process is needed.

## Default admin login

- **Username:** `admin`
- **Password:** `admin123`

Change this in the `admin` table (via phpMyAdmin) before using the system for real — generate a new
hash with PHP's `password_hash('yourpassword', PASSWORD_DEFAULT)`.

## Modules implemented (Section 7 of the proposal)

| # | Module | Where |
|---|--------|-------|
| 1 | Feedback Form | `public-portal/feedback.php`, `js/main.js` (role-based categories, star rating) |
| 2 | Anonymous Feedback | `public-portal/submit_feedback.php` — name is never read from the request when anonymous, so it can't reach the database |
| 3 | Admin Login | `admin-portal/login.php` — `password_verify()`, generic error message, session regeneration |
| 4 | Admin Dashboard | `admin-portal/dashboard.php` — 4 summary cards, feedback table |
| 5 | Search, Filter & Delete | `admin-portal/dashboard.php` (search + 4 filters), `delete_feedback.php` + confirmation modal |
| 6 | Feedback Statuses | `database/migration_status.sql`, `status_update.php`, and `view_feedback.php` — New / Reviewed / Resolved workflow |
| 7 | Password Recovery & Change | `admin-portal/forgot_password.php`, `admin-portal/change_password.php` — single-use email OTP verification and secure hash updates |
| 8 | Admin Profile | `admin-portal/profile.php`, `admin-portal/update_profile.php` — inline profile updates and verified email changes |
| 9 | Feedback Notifications | `database/migration_notifications.sql`, `admin-portal/check_new_feedback.php`, and read endpoints — authenticated per-admin polling and read state |
| 10 | Ratings Chart | `admin-portal/dashboard.php` — dependency-free 1–5 rating distribution chart |
| 11 | Public Contact Form | `public-portal/contact.php`, `public-portal/submit_contact.php` — validated visitor messages sent using the shared PHPMailer/Gmail SMTP configuration |

## Security notes (Section 13)

- Passwords stored with `password_hash()` / verified with `password_verify()` — never plain text.
- Every query uses PDO **prepared statements** (`PDO::ATTR_EMULATE_PREPARES => false`).
- All user text is passed through `htmlspecialchars()` on output and `strip_tags()` on input.
- `admin-portal/includes/auth.php` is required at the top of every protected page and redirects
  to the login page if there's no active session.
- Deleting an entry always requires confirming in the modal before the DELETE request fires.
- Anonymous submissions never have a name value reach the database at all — not merely hidden.
- Password reset codes are six-digit, single-use, expire after 60 seconds, and are rate-limited
   server-side. Logged-in password changes also verify the current password before sending an OTP.

## Out of scope (per Section 4.2)

Login accounts for students/staff, general feedback email/SMS notifications, and PDF/Excel export
are intentionally not included. Feedback statuses, the ratings distribution chart, and admin OTP
password recovery have been implemented from the proposal's future-enhancements list.
