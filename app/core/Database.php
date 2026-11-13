<?php
/**
 * Database Connection & Query Wrapper (PDO)
 * AI Online Exam & Proctoring System
 */

class Database
{
    private static ?Database $instance = null;
    private ?PDO $dbh = null;
    private $stmt;
    private ?string $error = null;

    private function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->dbh = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            // In development or when DB is not yet created, we store error instead of dying
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): ?PDO
    {
        return $this->dbh;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function isConnected(): bool
    {
        return $this->dbh !== null;
    }

    public function query(string $sql)
    {
        if (!$this->dbh) return $this;
        $this->stmt = $this->dbh->prepare($sql);
        return $this;
    }

    public function bind($param, $value, $type = null)
    {
        if (!$this->stmt) return $this;
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    public function execute(array $params = []): bool
    {
        if (!$this->stmt) return false;
        if (!empty($params)) {
            return $this->stmt->execute($params);
        }
        return $this->stmt->execute();
    }

    public function resultSet(array $params = []): array
    {
        $this->execute($params);
        return $this->stmt ? $this->stmt->fetchAll() : [];
    }

    public function single(array $params = [])
    {
        $this->execute($params);
        $res = $this->stmt ? $this->stmt->fetch() : null;
        return ($res !== false && $res !== null) ? $res : null;
    }

    public function fetchColumn(array $params = [], int $columnNumber = 0)
    {
        $this->execute($params);
        return $this->stmt ? $this->stmt->fetchColumn($columnNumber) : false;
    }

    public function rowCount(): int
    {
        return $this->stmt ? $this->stmt->rowCount() : 0;
    }

    public function lastInsertId(): string
    {
        return $this->dbh ? $this->dbh->lastInsertId() : '0';
    }
}
