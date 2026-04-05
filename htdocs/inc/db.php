<?php

/**
 * Class DB
 *
 * The DB abstraction class for mysql (PDO).
 */
class DB
{
    /**
     * @var PDO The used database handle.
     */
    public PDO $handle;

    /**
     * @var int Transaction nesting depth.
     */
    private int $transactionDepth = 0;

    function __construct(Page $page)
    {
        $config = $page->settings['db'];
        $s = sprintf("mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4", $config['host'], $config['port'], $config['name']);
        $this->handle = new PDO($s, $config['user'], $config['pass']);
        $this->handle->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Returns the PDO type for a value.
     *
     * @param mixed $value The value.
     * @return int The PDO type for a value.
     */
    public function getPDOType(mixed $value): int
    {
        if (is_int($value)) return PDO::PARAM_INT;
        if (is_bool($value)) return PDO::PARAM_BOOL;
        if (is_null($value)) return PDO::PARAM_NULL;
        return PDO::PARAM_STR;
    }

    /**
     * Prepares and executes a PDO statement.
     *
     * @param string $sql The SQL query.
     * @param mixed ...$params Bind values, either as separate arguments or a single array.
     * @return PDOStatement The executed statement.
     */
    public function query(string $sql, mixed ...$params): PDOStatement
    {
        $q = $this->handle->prepare($sql);
        if (count($params) > 0) {
            if (is_array($params[0])) {
                foreach ($params[0] as $key => $value) {
                    if (is_int($key)) {
                        $q->bindValue($key + 1, $value, $this->getPDOType($value));
                    } else {
                        $q->bindValue($key[0] == ':' ? $key : ':' . $key, $value, $this->getPDOType($value));
                    }
                }
            } else {
                $k = 0;
                foreach ($params as $value) {
                    $q->bindValue(++$k, $value, $this->getPDOType($value));
                }
            }
        }
        $q->execute();
        return $q;
    }

    /**
     * Returns the last insert ID for auto increment columns.
     *
     * @return int The last insert ID.
     */
    public function getLastInsertId(): int
    {
        return $this->handle->lastInsertId();
    }

    public function beginTransaction(): void
    {
        if ($this->transactionDepth === 0)
            $this->handle->beginTransaction();
        $this->transactionDepth++;
    }

    public function commit(): void
    {
        if ($this->transactionDepth === 1)
            $this->handle->commit();
        $this->transactionDepth--;
    }

    public function rollBack(): void
    {
        if ($this->transactionDepth === 1)
            $this->handle->rollBack();
        $this->transactionDepth--;
    }
}
