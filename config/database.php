<?php
/**
 * Bareeda Injera - Database Connection (Singleton PDO)
 *
 * Usage:
 *   $db = Database::getInstance();
 *   $rows = $db->query("SELECT * FROM products WHERE is_active = 1");
 *   $row  = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
 */

class Database
{
    /** @var Database|null */
    private static $instance = null;

    /** @var PDO */
    private $pdo;

    /** @var int */
    private $queryCount = 0;

    // ─── Singleton ──────────────────────────────────────────

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->logError('Connection failed: ' . $e->getMessage());
            // In production, show a generic message to the user
            if (getenv('APP_ENV') === 'development') {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage());
            }
            throw new RuntimeException('Service temporarily unavailable. Please try again later.');
        }
    }

    /** Prevent cloning */
    private function __clone() {}

    /** Prevent unserialization */
    public function __wakeup()
    {
        throw new RuntimeException('Cannot unserialize singleton.');
    }

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the raw PDO connection (for advanced use).
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // ─── Query Helpers ──────────────────────────────────────

    /**
     * Execute a SELECT and return all rows.
     *
     * @param  string $sql
     * @param  array  $params
     * @return array
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        $this->queryCount++;
        return $stmt->fetchAll();
    }

    /**
     * Execute a SELECT and return the first row (or null).
     *
     * @param  string $sql
     * @param  array  $params
     * @return array|null
     */
    public function fetchOne(string $sql, array $params = [])
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        $this->queryCount++;
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Execute an INSERT / UPDATE / DELETE and return affected row count.
     *
     * @param  string $sql
     * @param  array  $params
     * @return int
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        $this->queryCount++;
        return $stmt->rowCount();
    }

    /**
     * Insert a row and return the last insert ID.
     *
     * @param  string $table
     * @param  array  $data  column => value
     * @return string
     */
    public function insert(string $table, array $data): string
    {
        $columns      = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->prepare($sql);
        $stmt->execute(array_values($data));
        $this->queryCount++;

        return $this->pdo->lastInsertId();
    }

    /**
     * Update rows in a table.
     *
     * @param  string $table
     * @param  array  $data   column => value
     * @param  string $where  e.g. "id = ?"
     * @param  array  $params values for the WHERE clause
     * @return int    affected rows
     */
    public function update(string $table, array $data, string $where, array $params = []): int
    {
        $set = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));
        $sql = "UPDATE `{$table}` SET {$set} WHERE {$where}";

        $values = array_merge(array_values($data), $params);
        $stmt   = $this->prepare($sql);
        $stmt->execute($values);
        $this->queryCount++;

        return $stmt->rowCount();
    }

    /**
     * Delete rows from a table.
     *
     * @param  string $table
     * @param  string $where
     * @param  array  $params
     * @return int
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql  = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        $this->queryCount++;
        return $stmt->rowCount();
    }

    // ─── Transactions ───────────────────────────────────────

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Run a callback inside a transaction.
     * Commits on success, rolls back on exception.
     *
     * @param  callable $callback
     * @return mixed    the callback's return value
     * @throws Throwable
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            $this->logError('Transaction rolled back: ' . $e->getMessage());
            throw $e;
        }
    }

    // ─── Prepared Statement ─────────────────────────────────

    private function prepare(string $sql): PDOStatement
    {
        try {
            return $this->pdo->prepare($sql);
        } catch (PDOException $e) {
            $this->logError('Prepare failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new RuntimeException('Query preparation failed.');
        }
    }

    // ─── Utility ────────────────────────────────────────────

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    // ─── Error Logging ──────────────────────────────────────

    private function logError(string $message): void
    {
        $logDir = defined('LOG_PATH') ? LOG_PATH : dirname(__DIR__) . '/logs/';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        @file_put_contents(
            $logDir . 'db_errors.log',
            "[{$timestamp}] {$message}" . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
        error_log("[DB] {$message}");
    }
}
