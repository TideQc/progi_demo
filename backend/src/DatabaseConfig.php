<?php
namespace App;

/**
 * DatabaseConfig
 *
 * Encapsulates database configuration from environment variables.
 */
class DatabaseConfig {
    private string $host;
    private string $user;
    private string $password;
    private string $database;

    /**
     * Constructor
     * @param string|null $host Database host
     * @param string|null $user Database user
     * @param string|null $password Database password
     * @param string|null $database Database name
     */
    public function __construct(
        ?string $host = null,
        ?string $user = null,
        ?string $password = null,
        ?string $database = null
    ) {
        $this->host = $host ?? getenv('DB_HOST') ?: 'localhost';
        $this->user = $user ?? getenv('DB_USER') ?: 'user';
        $this->password = $password ?? getenv('DB_PASS') ?: 'pass';
        $this->database = $database ?? getenv('DB_NAME') ?: 'bidcalc';
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getUser(): string {
        return $this->user;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getDatabase(): string {
        return $this->database;
    }

    /**
     * Get DSN for PDO connection
     * @return string PDO DSN string
     */
    public function getDsn(): string {
        return "mysql:host={$this->host};dbname={$this->database}";
    }
}
