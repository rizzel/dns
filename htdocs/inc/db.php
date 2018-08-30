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

        $dsnParts = [];
        if (!empty($config['dbHost'])) {
            $dsnParts[] = 'host=' . $config['dbHost'];
            if (!empty($config['dbPort'])) {
                $dsnParts[] = 'port=' . $config['dbPort'];
            }
        }
        $dbParts[] = 'dbname=' . $config['dbName'];

        $s = sprintf(config['dbType'] . ':' . implode(';', $dsnParts));
        $this->handle = new PDO($s, $config['dbUser'], $config['dbPass']);
        $this->handle->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        if ($config['dbType'] === 'mysql') {
            $this->handle->query("SET NAMES 'utf-8'");
            $this->handle->query("SET CHARACTER SET 'utf-8'");
        }
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
     * Because of the possibility of multi master postgresql setups we cannot rely on the AUTO_INCREMENT PRIMARY KEY.
     * This function keeps track of the primary key of all relevant tables in the database and returns the next unused one for a specific table.
     *
     * @param string $table The table the primary key should be created for
     * @return int The next primary key for the given table
     */
    public function getNextPrimaryKeyId($table) {
        if (!in_array($table, ['comments', 'cryptokeys', 'domainmetadata', 'domain', 'record', 'tsigkeys']))
            die("table error");
        $this->handle->beginTransaction();
        $offsetId = isset($this->page->settings->db['multiMaster']['primaryKeyOffset']) ? $this->page->settings->db['multiMaster']['primaryKeyOffset'] : 0;
        $field = 'current_max_' . $table . '_id';
        $get = $this->query("SELECT $field FROM dns_max_key WHERE offset_id = ?", $offsetId);

        if (empty($get)) {
            $this->query("INSERT INTO dns_max_key (
                offset_id,
                current_max_comments_id,
                current_max_cryptokeys_id,
                current_max_domainmetadata_id,
                current_max_domain_id,
                current_max_record_id,
                current_max_tsigkeys_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)", $offsetId, $offsetId, $offsetId, $offsetId, $offsetId, $offsetId, $offsetId);
            $result = $offsetId;
        } else {
            $result = $get->fetch(PDO::FETCH_NUM)[0];
            $this->query("UPDATE dns_max_key SET $field = ? WHERE offset_id = ?", $result + 1, $offsetId);
        }
        $this->handle->commit();

        return $result;
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
