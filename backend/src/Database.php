<?php
namespace App;

use PDO;
use PDOException;

/**
 * Database
 *
 * Handles database connection creation and lifecycle.
 */
class Database {
    private DatabaseConfig $config;
    private ?PDO $connection = null;

    /**
     * Constructor
     * @param DatabaseConfig $config Database configuration
     */
    public function __construct(DatabaseConfig $config) {
        $this->config = $config;
    }

    /**
     * Get or create PDO connection
     * @return PDO Database connection
     * @throws PDOException if connection fails
     */
    public function getConnection(): PDO {
        if ($this->connection === null) {
            $this->connection = new PDO(
                $this->config->getDsn(),
                $this->config->getUser(),
                $this->config->getPassword()
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $this->connection;
    }

    /**
     * Close database connection
     */
    public function close(): void {
        $this->connection = null;
    }
}
