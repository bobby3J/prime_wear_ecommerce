<?php
namespace Infrastructure\Persistence;

use Domain\Customer\Customer;
use Domain\Customer\CustomerRepository;
use PDO;

class MySQLCustomerRepository implements CustomerRepository
{
    // Persistence adapter for customers table.
    // Used by auth use cases for register/login/session resolution.
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?Customer
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, password, status, created_at, updated_at
             FROM customers
             WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function findByEmail(string $email): ?Customer
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, password, status, created_at, updated_at
             FROM customers
             WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function create(string $name, string $email, string $passwordHash): Customer
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO customers (name, email, password, status, created_at, updated_at)
             VALUES (?, ?, ?, 'active', NOW(), NOW())"
        );
        $stmt->execute([$name, $email, $passwordHash]);

        $id = (int) $this->pdo->lastInsertId();
        $customer = $this->findById($id);

        if (!$customer) {
            throw new \RuntimeException('Unable to load created customer.');
        }

        return $customer;
    }

    private function mapRowToEntity(array $row): Customer
    {
        return Customer::fromPersistence(
            (int) $row['id'],
            $row['name'],
            $row['email'],
            $row['password'],
            $row['status'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at'])
        );
    }
}
