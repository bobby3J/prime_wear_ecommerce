<?php
namespace Infrastructure\Auth;

use Domain\Customer\Customer;

class SessionAuth
{
    // Shared session auth helper used by:
    // - storefront customer APIs
    // - admin auth/session guard
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    // Storefront customer session binding.
    public static function loginCustomer(Customer $customer): void
    {
        self::start();
        $_SESSION['customer_id'] = $customer->getId();
        $_SESSION['customer_name'] = $customer->getName();
        $_SESSION['customer_email'] = $customer->getEmail();
        $_SESSION['customer_role'] = 'customer';
    }

    public static function logoutCustomer(): void
    {
        self::start();
        unset(
            $_SESSION['customer_id'],
            $_SESSION['customer_name'],
            $_SESSION['customer_email'],
            $_SESSION['customer_role']
        );
    }

    public static function customerId(): ?int
    {
        self::start();
        return isset($_SESSION['customer_id']) ? (int) $_SESSION['customer_id'] : null;
    }

    public static function isCustomerAuthenticated(): bool
    {
        return self::customerId() !== null;
    }

    // Admin session binding.
    // Expected keys:
    // - admin_id
    // - admin_name
    // - admin_email
    // - admin_role (admin|superadmin)
    public static function loginAdmin(array $admin): void
    {
        self::start();
        // Rotate session id on privilege boundaries.
        session_regenerate_id(true);

        $_SESSION['admin_id'] = (int) ($admin['id'] ?? 0);
        $_SESSION['admin_name'] = (string) ($admin['name'] ?? '');
        $_SESSION['admin_email'] = (string) ($admin['email'] ?? '');
        $_SESSION['admin_role'] = (string) ($admin['role'] ?? 'admin');
    }

    public static function logoutAdmin(): void
    {
        self::start();
        unset(
            $_SESSION['admin_id'],
            $_SESSION['admin_name'],
            $_SESSION['admin_email'],
            $_SESSION['admin_role']
        );
    }

    public static function adminId(): ?int
    {
        self::start();
        return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    }

    public static function adminName(): ?string
    {
        self::start();
        return isset($_SESSION['admin_name']) ? (string) $_SESSION['admin_name'] : null;
    }

    public static function adminEmail(): ?string
    {
        self::start();
        return isset($_SESSION['admin_email']) ? (string) $_SESSION['admin_email'] : null;
    }

    public static function adminRole(): ?string
    {
        self::start();
        return isset($_SESSION['admin_role']) ? (string) $_SESSION['admin_role'] : null;
    }

    // Central authorization rule for admin area access.
    public static function isAdminAuthorized(): bool
    {
        return self::adminId() !== null
            && in_array(self::adminRole(), ['admin', 'superadmin'], true);
    }

    public static function isSuperadmin(): bool
    {
        return self::adminRole() === 'superadmin';
    }

    public static function requireCustomer(): void
    {
        if (!self::isCustomerAuthenticated()) {
            self::abortJson(401, 'Authentication required.');
        }
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdminAuthorized()) {
            self::abortJson(403, 'Admin access required.');
        }
    }

    public static function requireSuperadmin(): void
    {
        if (!self::isSuperadmin()) {
            self::abortJson(403, 'Superadmin access required.');
        }
    }

    // JSON-safe hard stop for API-like entry points.
    private static function abortJson(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}
