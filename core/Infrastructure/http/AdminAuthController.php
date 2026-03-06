<?php
namespace Infrastructure\Http;

use Infrastructure\Auth\SessionAuth;
use Infrastructure\Persistence\MySQLAdminUserRepository;

class AdminAuthController
{
    public function __construct(
        private ?MySQLAdminUserRepository $userRepository = null
    ) {
        $this->userRepository = $this->userRepository ?? new MySQLAdminUserRepository();
    }

    public function showLogin(): array
    {
        // Logged-in admins should not revisit login.
        if (SessionAuth::isAdminAuthorized()) {
            header('Location: /admin/dashboard');
            exit;
        }

        return [
            'layout' => 'auth',
            'view' => 'auth/login.php',
            'data' => [
                'error' => trim((string) ($_GET['error'] ?? '')),
                'notice' => trim((string) ($_GET['notice'] ?? '')),
                // Initial setup card is shown only when no admin/superadmin exists.
                'canBootstrap' => $this->userRepository->countAdminUsers() === 0,
            ],
        ];
    }

    public function login(): void
    {
        // Normalize credentials before validation.
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $this->redirectLogin('Enter a valid email and password.');
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            $this->redirectLogin('Invalid email or password.');
        }

        if (($user['status'] ?? 'inactive') !== 'active') {
            $this->redirectLogin('This account is inactive. Contact superadmin.');
        }

        $role = (string) ($user['role'] ?? '');
        if (!in_array($role, ['admin', 'superadmin'], true)) {
            $this->redirectLogin('Only admin accounts can sign in here.');
        }

        $storedPassword = (string) ($user['password'] ?? '');
        $isValid = password_verify($password, $storedPassword);

        // Compatibility bridge:
        // If a legacy row still stores plain text, allow one login and re-hash immediately.
        if (!$isValid && hash_equals($storedPassword, $password)) {
            $isValid = true;
            $this->userRepository->updatePasswordHash(
                (int) $user['id'],
                password_hash($password, PASSWORD_DEFAULT)
            );
        }

        if (!$isValid) {
            $this->redirectLogin('Invalid email or password.');
        }

        SessionAuth::loginAdmin($user);

        header('Location: /admin/dashboard');
        exit;
    }

    public function bootstrapFirstSuperadmin(): void
    {
        // This endpoint is intentionally one-time only.
        if ($this->userRepository->countAdminUsers() > 0) {
            $this->redirectLogin('Initial setup is disabled because admin users already exist.');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $this->redirectLogin('Name must be between 2 and 100 characters.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectLogin('Enter a valid email address.');
        }

        if (mb_strlen($password) < 8) {
            $this->redirectLogin('Password must be at least 8 characters.');
        }

        $id = $this->userRepository->create(
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            'superadmin',
            'active'
        );

        $user = $this->userRepository->findById($id);
        if (!$user) {
            $this->redirectLogin('Unable to create first superadmin account.');
        }

        SessionAuth::loginAdmin($user);

        header('Location: /admin/dashboard');
        exit;
    }

    public function logout(): void
    {
        // Explicitly clear admin-only session keys.
        SessionAuth::logoutAdmin();
        header('Location: /admin/login?notice=' . urlencode('You have logged out successfully.'));
        exit;
    }

    // Keeps login error redirects consistent.
    private function redirectLogin(string $message): void
    {
        header('Location: /admin/login?error=' . urlencode($message));
        exit;
    }
}
