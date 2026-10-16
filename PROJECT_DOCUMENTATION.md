# College Feedback System — Student / Staff / Admin Enhancements

This project extends the existing public and admin flows with role-based student and staff portals and an admin status report.

## Local database

- Database name: `college_feedback_system`
- User: `root`
- Password: `root`

## Migration commands

Run from the project root:

```bash
mysql -u root -proot < database/migration_users.sql
mysql -u root -proot < database/migration_status_log.sql
mysql -u root -proot < database/migration_student_approvals.sql
mysql -u root -proot < database/migration_public_feedback_type.sql
```

## Role model

- Public feedback uses one role-free form, stores the submitter type as `Public`, and never stores a name when anonymous submission is selected.
- Student and staff credentials live in `portal_users`; the corresponding managed identities are represented in `users`.
- Staff can create student identities only. Those identities remain pending and cannot sign in until an administrator approves them; rejected identities remain blocked.
- The admin student-approvals page lists only staff-created student identities and records approval or rejection in the account audit log.
- Student and staff pages must enforce session-role checks before allowing access to protected screens.
- System-level account and status changes are recorded in `account_audit_log` and `feedback_status_log`.

## Security notes

- Passwords are stored with `password_hash`.
- OTP records are stored as hashed values in `user_password_otp`.
- Admin/staff status changes should add a matching entry in `feedback_status_log` with the correct actor metadata.
- Anonymous submissions must not get a linked `user_id`.

## Project structure

- `public-portal/` — feedback form and confirmation pages
- `student-portal/` — student dashboard and auth flows
- `staff-portal/` — staff dashboard and portal management
- `admin-portal/` — admin dashboard and reports
- `database/` — schema and migration scripts
