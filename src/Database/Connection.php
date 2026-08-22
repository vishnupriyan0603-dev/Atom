<?php

namespace Atom\Database;

use PDO;
use PDOException;

class Connection
{
    private ?PDO $pdo = null;
    private bool $connected = false;
    private ?string $connectError = null;

    public function __construct(
        string $host = '',
        string $dbName = '',
        string $user = '',
        string $password = '',
        string $port = '3306'
    ) {
        if (empty($host) || empty($dbName)) {
            $this->connectError = "Database configuration parameters are empty.";
            return;
        }

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $this->pdo = new PDO($dsn, $user, $password, $options);
            $this->connected = true;
        } catch (PDOException $e) {
            $this->connectError = "Database Connection Failed: " . $e->getMessage();
            $this->connected = false;
        }
    }

    public static function fromPdo(PDO $pdo): self
    {
        $conn = new self();
        $conn->pdo = $pdo;
        $conn->connected = true;
        return $conn;
    }

    /**
     * Checks if connection is active.
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Returns the active PDO instance or null.
     */
    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    /**
     * Returns connection error details if any.
     */
    public function getError(): ?string
    {
        return $this->connectError;
    }
}
