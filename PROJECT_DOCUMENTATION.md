# College Feedback System — Current Technical Documentation

This document describes the implementation currently present in the repository. The complete academic report is in `COLLEGE_FEEDBACK_SYSTEM_COMPLETE_PROJECT_REPORT.md`.

## Local database

- Database name: `college_feedback_system`
- User: `root`
- Password: `root`

## Migration commands

Run from the project root:

```bash
mysql -u root -proot < database/migration_status.sql
mysql -u root -proot < database/migration_otp.sql
mysql -u root -proot < database/migration_profile.sql
mysql -u root -proot < database/migration_notifications.sql
mysql -u root -proot < database/migration_general_notifications.sql
mysql -u root -proot < database/migration_student_staff.sql
mysql -u root -proot < database/migration_users.sql
mysql -u root -proot < database/migration_accounts.sql
mysql -u root -proot < database/migration_status_log.sql
mysql -u root -proot < database/migration_student_approvals.sql
mysql -u root -proot < database/migration_portal_feedback_fields.sql
mysql -u root -proot < database/migration_feedback_dropdown_options.sql
mysql -u root -proot < database/migration_portal_password_otp.sql
mysql -u root -proot < database/migration_account_academic_info.sql
```

For a fresh database, import `database/schema.sql` before these migrations. Existing installations should review legacy migration history before applying overlapping account migrations.

## Role model

- Student and staff credentials live in `portal_users`; the corresponding managed identities are represented in `users`.
- Staff can create student identities only. Those identities remain pending and cannot sign in until an administrator approves them; rejected identities remain blocked.
- The admin student-approvals page lists only staff-created student identities and records approval or rejection in the account audit log.
- Student and staff pages must enforce session-role checks before allowing access to protected screens.
- System-level account and status changes are recorded in `account_audit_log` and `feedback_status_log`.
- Students and staff can view only their own linked feedback history.
- Only `New` feedback can be edited or deleted by its owner.
- `Resolved` feedback is final and cannot be moved to another status.
- Student academic fields are department/faculty and semester/year. Staff employment fields are staff department and institutional staff role; staff role is not a system permission.
- Lookup values are loaded from `feedback_dropdown_options` and validated server-side.
- Staff/student accounts are synchronized between `portal_users` and `users`.

## Security notes

- Passwords are stored with `password_hash`.
- Admin OTP records are stored in `password_otp`; current shared portal recovery/change-password records use `portal_password_otp`. The legacy `user_password_otp` table remains for compatibility.
- Active OTP validity is 10 minutes. Resend cooldown is 60 seconds. A new successful OTP request invalidates the previous active code.
- Admin/staff status changes should add a matching entry in `feedback_status_log` with the correct actor metadata.
- Anonymous submissions do not store a submitter name. Authenticated portal anonymous feedback may remain linked internally to the owning account for tracking, while ordinary details hide the identity.
- Attachments are limited to JPG/JPEG/PNG/PDF and 5 MB, and are stored outside the web root by default.

## Current routes and features

### Student portal

`login.php`, `dashboard.php`, `feedback_options.php`, `edit_feedback.php`, `forgot_password.php`,
`reset_password.php`, and `logout.php`. The dashboard contains feedback creation/history and shared
profile/security tabs with search, filters, pagination, profile updates, phone, academic fields, and
password OTP flow.

### Staff portal

The student route set plus `manage_users.php`. Staff can create student identities. Those records are
created as `pending`, include `created_by_staff_id`, and cannot sign in until an administrator approves
them through the Admin Student Approvals page.

### Admin portal

Admin routes provide dashboard charts and metrics, feedback details, attachment downloads, status
updates, deletion confirmation, reports/CSV, account CRUD, enable/disable/reset/delete actions,
student approval, audit filtering, admin profile/email verification, password recovery/change, and
per-admin notification polling.

The current root `index.php` is a public landing page. The older `public-portal/` directory described
in previous documentation is not present in this checkout and must not be treated as an active module.

## Database tables and values

The schema and migrations define: `admin`, `feedback`, `portal_users`, `users`, `portal_submissions`,
`portal_reset_tokens`, `password_otp`, `portal_password_otp`, `user_password_otp`,
`feedback_dropdown_options`, `feedback_status_log`, `notifications`, and `account_audit_log`.

Important values are:

- Feedback status: `New`, `Reviewed`, `Resolved`.
- Account status: `active`, `disabled`.
- Student approval: `pending`, `approved`, `rejected`.
- Portal roles: `student`, `staff`.
- Feedback submitter types: `Student`, `Teacher & Staff`.

Student departments are `+2 Science`, `+2 Management`, `BCA`, `BBS`, and `MBS`; semester/year values
are parent-dependent. Staff departments and institutional roles are seeded by
`migration_feedback_dropdown_options.sql`.

## Database compatibility checks

Current portal profile PHP reads/writes `phone` on both `users` and `portal_users`, but the visible
account migrations do not add those columns. Add/verify them before deploying phone profile updates
to a fresh database. Also, `migration_portal_password_otp.sql` declares only `forgot_password` in its
ENUM while the shared change-password code uses `change_password`; update the deployed column to allow
both purposes.

## Project structure

- `student-portal/` — student dashboard and auth flows
- `staff-portal/` — staff dashboard and portal management
- `admin-portal/` — admin dashboard and reports
- `database/` — schema and migration scripts

## Verification commands

```bash
find . -path './vendor' -prune -o -path './.git' -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l
git diff --check
composer validate
```
