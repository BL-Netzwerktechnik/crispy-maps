<?php

namespace Crispy\DatabaseControllers;

use crisp\core\Postgres;
use Exception;
use PDO;

class DatabaseController
{

    private PDO $pdo;
    private bool $strictTransaction = true;

    public function __construct()
    {
        $this->pdo = (new Postgres())->getDBConnector();
    }

    protected function isStrictTransaction(): bool
    {
        return $this->strictTransaction;
    }

    protected function setStrictTransaction(bool $strictTransaction): void
    {
        $this->strictTransaction = $strictTransaction;
    }

    protected function getDatabaseConnector(): PDO
    {
        return $this->pdo;
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function beginTransaction()
    {
        if ($this->pdo->inTransaction()) {
            throw new Exception('Cannot begin transaction, because transaction is already active.');
        }
        $this->pdo->beginTransaction();
    }

    public function commitTransaction()
    {
        if (!$this->pdo->inTransaction()) {
            throw new Exception('Cannot commit transaction, because no transaction is active.');
        }
        $this->pdo->commit();
    }

    public function rollbackTransaction()
    {
        if (!$this->pdo->inTransaction()) {
            throw new Exception('Cannot rollback transaction, because no transaction is active.');
        }
        $this->pdo->rollBack();
    }
}
