<?php
namespace Infrastructure\Persistence;

use Core\Infrastructure\Persistence\Database;
use Domain\Shared\TransactionManager;
use PDO;

class PdoTransactionManager implements TransactionManager
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function transactional(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
