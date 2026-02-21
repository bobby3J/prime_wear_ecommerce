<?php
namespace Infrastructure\Auth;

use Domain\Customer\Customer;

class SessionAuth
{
    // Shared session auth helper used by:
    // - storefront customer APIs
    // - admin auth guard seam
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

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

    public static function adminRole(): ?string
    {
        self::start();
        return $_SESSION['admin_role'] ?? null;
    }

    public static function isAdminAuthorized(): bool
    {
        return in_array(self::adminRole(), ['admin', 'superadmin'], true);
    }

    public static function requireCustomer(): void
    {
        if (!self::isCustomerAuthenticated()) {
            self::abortJson(401, 'Authentication required.');
        }
    }

    public static function requireAdmin(): void
    {
        $role = self::adminRole();
        if (!in_array($role, ['admin', 'superadmin'], true)) {
            self::abortJson(403, 'Admin access required.');
        }
    }

    public static function requireSuperadmin(): void
    {
        if (self::adminRole() !== 'superadmin') {
            self::abortJson(403, 'Superadmin access required.');
        }
    }

    private static function abortJson(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}
