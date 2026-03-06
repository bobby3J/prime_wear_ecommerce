# Admin Auth and RBAC Flow

## Scope
This document describes the admin-side authentication and role-based access control (RBAC) flow for:

- admin login/logout
- superadmin-only users management
- sidebar visibility by role

Primary roles:

- `admin`
- `superadmin`

## Core Session Model
Session helper: `core/Infrastructure/Auth/SessionAuth.php`

Admin session keys:

- `admin_id`
- `admin_name`
- `admin_email`
- `admin_role`

Key methods:

- `loginAdmin(array $admin)` writes admin session keys and regenerates session id.
- `logoutAdmin()` clears admin session keys.
- `isAdminAuthorized()` allows only `admin|superadmin` with a valid `admin_id`.
- `isSuperadmin()` checks strict role match.

## Entry Guard (Admin Front Controller)
File: `admin/index.php`

Guard order:

1. Start session.
2. Resolve request path.
3. Allow public auth paths:
   - `/admin/login`
   - `/admin/bootstrap-superadmin`
4. If authenticated admin visits `/admin/login`, redirect to `/admin/dashboard`.
5. If unauthenticated request targets protected admin route, redirect to `/admin/login`.
6. If route starts with `/admin/users` and current role is not `superadmin`, redirect to `/admin/dashboard?forbidden=1`.
7. Register routes and dispatch.
8. Render view inside `main` layout (default) or `auth` layout.

## Login/Logout and First Bootstrap
Controller: `core/Infrastructure/http/AdminAuthController.php`  
Routes: `core/Infrastructure/routes/admin_auth.php`

### GET `/admin/login`
- Renders `admin/views/auth/login.php`.
- Includes optional one-time bootstrap form when no `admin/superadmin` exists.

### POST `/admin/login`
- Validates email/password input.
- Loads user by email.
- Requires:
  - `status = active`
  - `role IN ('admin', 'superadmin')`
- Verifies password via `password_verify`.
- Legacy compatibility: if password is stored as plain text and matches, login succeeds once and password is upgraded to hash.
- Binds session with `SessionAuth::loginAdmin(...)`.
- Redirects to `/admin/dashboard`.

### POST `/admin/logout`
- Clears admin session keys.
- Redirects to `/admin/login` with success notice.

### POST `/admin/bootstrap-superadmin`
- Allowed only when there is no existing admin/superadmin user.
- Creates first `superadmin` user with hashed password.
- Logs in immediately and redirects to dashboard.

## Superadmin-Only User Management
Controller: `core/Infrastructure/http/AdminUserController.php`  
Repository: `core/Infrastructure/persistence/MySQLAdminUserRepository.php`  
Routes: `core/Infrastructure/routes/users.php`

All actions enforce superadmin using `ensureSuperadmin()`:

- `GET /admin/users/view`
- `GET /admin/users/create`
- `POST /admin/users/create`
- `GET /admin/users/edit?id=...`
- `POST /admin/users/edit`
- `GET /admin/users/delete?id=...`

Business constraints:

- Email must be unique.
- Password policy:
  - create: required, min 8 chars
  - update: optional, but min 8 chars if provided
- Role must be `admin|superadmin`.
- Status must be `active|inactive`.
- You cannot delete your currently logged-in account.
- At least one active superadmin must remain (applies on update and delete).

## UI Visibility Rules
Sidebar file: `admin/includes/sidebar.php`

- `Users` menu item is rendered only when `SessionAuth::isSuperadmin()` is true.
- For `admin` role, users menu is not rendered.

Navbar file: `admin/includes/navbar.php`

- Displays current admin name and role from session.
- Logout uses real POST form to `/admin/logout`.

## Data Layer Notes
Repository file: `core/Infrastructure/persistence/MySQLAdminUserRepository.php`

Responsibilities:

- find by email/id
- list users with filters + pagination
- create/update/delete user rows
- count active superadmins
- count admin/superadmin users for bootstrap gate
- opportunistic password hash upgrade support

## Database Requirements
Setup script: `docs/admin-rbac-setup.sql`

Required `users.role` support:

- `admin`
- `superadmin`

If legacy `customer` exists in enum, it can remain for backward compatibility, but admin portal access is limited to admin/superadmin.

## Quick Review Checklist
Use this when reviewing changes:

1. Unauthenticated access to `/admin/dashboard` redirects to `/admin/login`.
2. Superadmin login shows `Users` menu in sidebar.
3. Admin login does not show `Users` menu.
4. Admin direct hit on `/admin/users/view` is redirected away.
5. Superadmin can create/edit/delete admin users.
6. Last active superadmin cannot be removed or demoted.
7. Logout clears session and returns to login page.
