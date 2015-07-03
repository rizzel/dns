<?php

/**
 * Class DB
 *
 * The DB abstraction class for mysql (PDO).
 */
class DB
{
    /**
     * @var Page The base page instance
     */
    private $page;

    /**
     * @var PDO The used database handle.
     */
    public $handle;

    function __construct($page)
    {
        $this->page = $page;
        $config = $page->settings->db;
        $s = sprintf("mysql:host=%s;port=%d;dbname=%s", $config['dbHost'], $config['dbPort'], $config['dbName']);
        $this->handle = new PDO($s, $config['dbUser'], $config['dbPass']);
        $this->handle->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->handle->query("SET NAMES 'utf-8'");
        $this->handle->query("SET CHARACTER SET 'utf-8'");
    }

    /**
     * Quotes the strings using the db handle.
     *
     * @param string $x The string to quote.
     * @return string The quoted string.
     */
    public function esc($x)
    {
        return $this->handle->quote($x);
    }

    /**
     * Returns the PDO type for a value.
     *
     * @param $value The value.
     * @return int The PDO type for a value.
     */
    public function getPDOType($value)
    {
        if (is_int($value)) return PDO::PARAM_INT;
        if (is_bool($value)) return PDO::PARAM_BOOL;
        if (is_null($value)) return PDO::PARAM_NULL;
        return PDO::PARAM_STR;
    }

    /**
     * Prepares a PDO statement.
     * The first argument is always a SQL query.
     * The second parameter can be one of:
     * a) an array containing all bind values.
     * b) an assoc-array containing all bind values (with or without leading colon in key)
     * c) separate parameters for the bind values.
     *
     * @param string $0 The SQL query.
     * @param array|string $1 Optional parameters.
     * @return PDOStatement The prepared statement.
     */
    public function query()
    {
        $args = func_get_args();
        $sql = array_shift($args);
        $q = $this->handle->prepare($sql);
        if (count($args) > 0) {
            if (is_array($args[0])) {
                foreach ($args[0] as $key => $value) {
                    if (is_int($key)) {
                        $q->bindValue($key + 1, $value, $this->getPDOType($value));
                    } else {
                        $q->bindValue($key[0] == ':' ? $key : ':' . $key, $value, $this->getPDOType($value));
                    }
                }
            } else {
                $k = 0;
                foreach ($args as $value) {
                    $q->bindValue(++$k, $value, $this->getPDOType($value));
                }
            }
        }
        $q->execute() || print("SQLERROR: " . $sql . print_r($args, true) . ' (' . print_r($q->errorInfo()) . ')');
        return $q;
    }

    /**
     * Returns the last insert ID for auto increment columns.
     *
     * @return int The last insert ID.
     */
    public function getLastInsertId()
    {
        return $this->handle->lastInsertId();
    }
}
