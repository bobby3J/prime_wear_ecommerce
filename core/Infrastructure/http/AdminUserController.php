<?php
namespace Infrastructure\Http;

use Infrastructure\Auth\SessionAuth;
use Infrastructure\Persistence\MySQLAdminUserRepository;

class AdminUserController
{
    public function __construct(
        private ?MySQLAdminUserRepository $userRepository = null
    ) {
        $this->userRepository = $this->userRepository ?? new MySQLAdminUserRepository();
    }

    public function view(): array
    {
        // Users management surface is restricted to superadmin.
        $this->ensureSuperadmin();

        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'role' => trim((string) ($_GET['role'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ];

        $perPage = (int) ($_GET['per_page'] ?? 10);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->userRepository->listForAdmin($filters, $page, $perPage);

        return [
            'view' => 'users/list_users.php',
            'data' => [
                'users' => $result['rows'] ?? [],
                'filters' => $filters,
                'pagination' => $result['pagination'] ?? [
                    'page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 1,
                ],
                'flashError' => trim((string) ($_GET['error'] ?? '')),
                'flashNotice' => trim((string) ($_GET['notice'] ?? '')),
            ],
        ];
    }

    public function create(): array
    {
        $this->ensureSuperadmin();

        return [
            'view' => 'users/insert_user.php',
            'data' => [
                'flashError' => trim((string) ($_GET['error'] ?? '')),
            ],
        ];
    }

    public function store(): void
    {
        $this->ensureSuperadmin();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $role = strtolower(trim((string) ($_POST['role'] ?? 'admin')));
        $status = strtolower(trim((string) ($_POST['status'] ?? 'active')));

        try {
            // Role, status, email, and password policy checks.
            $this->validateUserPayload($name, $email, $password, $role, $status, true);

            if ($this->userRepository->emailExists($email)) {
                throw new \RuntimeException('Email already exists.');
            }

            $this->userRepository->create(
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
                $status
            );

            header('Location: /admin/users/view?notice=' . urlencode('User created successfully.'));
            exit;
        } catch (\Throwable $e) {
            header('Location: /admin/users/create?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function edit(): array
    {
        $this->ensureSuperadmin();

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            return [
                'view' => 'errors/404.php',
                'data' => [],
            ];
        }

        $user = $this->userRepository->findById($id);
        if (!$user) {
            return [
                'view' => 'errors/404.php',
                'data' => [],
            ];
        }

        return [
            'view' => 'users/edit_user.php',
            'data' => [
                'user' => $user,
                'flashError' => trim((string) ($_GET['error'] ?? '')),
            ],
        ];
    }

    public function update(): void
    {
        $this->ensureSuperadmin();

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $role = strtolower(trim((string) ($_POST['role'] ?? 'admin')));
        $status = strtolower(trim((string) ($_POST['status'] ?? 'active')));

        try {
            if ($id <= 0) {
                throw new \RuntimeException('Invalid user ID.');
            }

            $existing = $this->userRepository->findById($id);
            if (!$existing) {
                throw new \RuntimeException('User not found.');
            }

            $this->validateUserPayload($name, $email, $password, $role, $status, false);

            if ($this->userRepository->emailExists($email, $id)) {
                throw new \RuntimeException('Email already exists.');
            }

            $wasActiveSuperadmin = ($existing['role'] === 'superadmin' && $existing['status'] === 'active');
            $willRemainActiveSuperadmin = ($role === 'superadmin' && $status === 'active');

            // Safety rail: do not allow demoting/disabling the last active superadmin.
            if ($wasActiveSuperadmin && !$willRemainActiveSuperadmin) {
                $remaining = $this->userRepository->countActiveSuperadmins($id);
                if ($remaining < 1) {
                    throw new \RuntimeException('At least one active superadmin must remain.');
                }
            }

            $passwordHash = null;
            if (trim($password) !== '') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            }

            $this->userRepository->update($id, $name, $email, $passwordHash, $role, $status);

            header('Location: /admin/users/view?notice=' . urlencode('User updated successfully.'));
            exit;
        } catch (\Throwable $e) {
            header('Location: /admin/users/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function delete(): void
    {
        $this->ensureSuperadmin();

        $id = (int) ($_GET['id'] ?? 0);

        try {
            if ($id <= 0) {
                throw new \RuntimeException('Invalid user ID.');
            }

            $user = $this->userRepository->findById($id);
            if (!$user) {
                throw new \RuntimeException('User not found.');
            }

            $currentAdminId = SessionAuth::adminId();
            // Prevent self-delete while currently authenticated.
            if ($currentAdminId !== null && $currentAdminId === $id) {
                throw new \RuntimeException('You cannot delete your currently logged-in account.');
            }

            // Same safety rail on delete path.
            if ($user['role'] === 'superadmin' && $user['status'] === 'active') {
                $remaining = $this->userRepository->countActiveSuperadmins($id);
                if ($remaining < 1) {
                    throw new \RuntimeException('At least one active superadmin must remain.');
                }
            }

            $this->userRepository->delete($id);

            header('Location: /admin/users/view?notice=' . urlencode('User deleted successfully.'));
            exit;
        } catch (\Throwable $e) {
            header('Location: /admin/users/view?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    private function ensureSuperadmin(): void
    {
        if (!SessionAuth::isSuperadmin()) {
            header('Location: /admin/dashboard?forbidden=1');
            exit;
        }
    }

    // Shared validation for both create and update.
    // When updating, password can be omitted to keep existing hash.
    private function validateUserPayload(
        string $name,
        string $email,
        string $password,
        string $role,
        string $status,
        bool $passwordRequired
    ): void {
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            throw new \RuntimeException('Name must be between 2 and 100 characters.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Enter a valid email address.');
        }

        if (!in_array($role, ['admin', 'superadmin'], true)) {
            throw new \RuntimeException('Role must be admin or superadmin.');
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new \RuntimeException('Status must be active or inactive.');
        }

        if ($passwordRequired && mb_strlen($password) < 8) {
            throw new \RuntimeException('Password must be at least 8 characters.');
        }

        if (!$passwordRequired && trim($password) !== '' && mb_strlen($password) < 8) {
            throw new \RuntimeException('Password must be at least 8 characters when provided.');
        }
    }
}
