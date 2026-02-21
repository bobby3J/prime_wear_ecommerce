<?php
namespace Domain\Shared;

interface TransactionManager
{
    /**
     * Runs a callback in a single database transaction.
     * The callback result is returned on successful commit.
     */
    public function transactional(callable $callback): mixed;
}
