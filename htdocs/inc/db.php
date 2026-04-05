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
    private PDO $handle;

    /**
     * @var int Transaction nesting depth.
     */
    private int $transactionDepth = 0;

    function __construct(Page $page)
    {
        $config = $page->settings['db'];
        $s = sprintf("mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4", $config['host'], $config['port'], $config['name']);
        $this->handle = new PDO($s, $config['user'], $config['pass'], [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    }

    /**
     * Prepares and executes a PDO statement.
     *
     * @param string $sql The SQL query.
     * @param mixed ...$params Bind values as separate arguments.
     * @return PDOStatement The executed statement.
     */
    public function query(string $sql, mixed ...$params): PDOStatement
    {
        $q = $this->handle->prepare($sql);
        $q->execute($params);
        return $q;
    }

    /**
     * Returns the last insert ID for auto increment columns.
     *
     * @return int The last insert ID.
     */
    public function getLastInsertId(): int
    {
        return (int) $this->handle->lastInsertId();
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
        if ($this->transactionDepth > 0) {
            $this->handle->rollBack();
            $this->transactionDepth = 0;
        }
    }
}
