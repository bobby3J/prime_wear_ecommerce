<?php
namespace Infrastructure\Persistence;

use Core\Infrastructure\Persistence\Database;
use PDO;

class MySQLAdminUserRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function countUsers(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        return (int) $stmt->fetchColumn();
    }

    // Admin portal is concerned with these roles only.
    public function countAdminUsers(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'superadmin')");
        return (int) $stmt->fetchColumn();
    }

    public function countActiveSuperadmins(?int $excludeId = null): int
    {
        // Used by controller guard rails to ensure at least one active superadmin remains.
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'superadmin' AND status = 'active'";
        $params = [];

        if ($excludeId !== null) {
            $sql .= " AND id <> ?";
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, password, role, status, created_at, updated_at
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, password, role, status, created_at, updated_at
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
        $stmt->execute([$email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        // Exclusion supports "same email" checks when editing an existing user.
        $sql = "SELECT 1 FROM users WHERE email = ?";
        $params = [$email];

        if ($excludeId !== null) {
            $sql .= " AND id <> ?";
            $params[] = $excludeId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function create(string $name, string $email, string $passwordHash, string $role, string $status): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password, role, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$name, $email, $passwordHash, $role, $status]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $email, ?string $passwordHash, string $role, string $status): void
    {
        if ($passwordHash !== null) {
            $stmt = $this->pdo->prepare(
                "UPDATE users
                 SET name = ?, email = ?, password = ?, role = ?, status = ?, updated_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([$name, $email, $passwordHash, $role, $status, $id]);
            return;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE users
             SET name = ?, email = ?, role = ?, status = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$name, $email, $role, $status, $id]);
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$passwordHash, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function listForAdmin(array $filters, int $page, int $perPage): array
    {
        // Server-side filtering + pagination for admin users table.
        $q = trim((string) ($filters['q'] ?? ''));
        $role = trim((string) ($filters['role'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(name LIKE ? OR email LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        if ($role !== '') {
            $where[] = 'role = ?';
            $params[] = $role;
        }

        if ($status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, role, status, created_at, updated_at
             FROM users
             {$whereSql}
             ORDER BY id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'rows' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }
}
