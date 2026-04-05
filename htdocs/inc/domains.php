<?php

/**
 * Class Domains
 *
 * Manipulates the database domain records.
 */
class Domains
{
    /**
     * @var Page The base page instance
     */
    private Page $page;

    function __construct(Page $page)
    {
        $this->page = $page;
    }

    private function touchRecord(int $recordId): void
    {
        $this->page->db->query("
            INSERT INTO dns_records (records_id, change_date) VALUES (?, NOW())
            ON DUPLICATE KEY UPDATE change_date = NOW()
        ", $recordId);
    }

    /**
     * @return array|null Returns a list of all domains and their admin records, or null on failure.
     */
    public function getDomains(): ?array
    {
        if ($this->page != 'admin')
            return null;
        $get = $this->page->db->query("
			SELECT * FROM domains d ORDER BY name
		");
        $result = $get->fetchall();
        foreach ($result as &$r) {
            $get = $this->page->db->query("
                SELECT
                  r.id,
                  r.name,
                  r.type,
                  IF(r.type = 'SOA', SUBSTRING_INDEX(r.content, ' ', 2), r.content) AS content,
                  r.ttl
                FROM records r
                LEFT JOIN dns_records_users dru
                  ON r.id = dru.records_id
				WHERE r.domain_id = ? AND (r.type NOT IN ('A', 'AAAA', 'CNAME') OR dru.records_id IS NULL)
				ORDER BY r.type, r.name, r.content
			",
                $r['id']
            );
            $r['records'] = $get->fetchall();
        }
        return $result;
    }

    /**
     * @param int $domainId The domain ID for which to update the SOA-record.
     * @return bool Return success.
     */
    private function updateSOARecord(int $domainId): bool
    {
        $set = $this->page->db->query("
            UPDATE records
            SET content = CONCAT(SUBSTRING_INDEX(content, ' ', 2), ' ', UNIX_TIMESTAMP(), SUBSTRING(content, CHAR_LENGTH(SUBSTRING_INDEX(content, ' ', 3)) + 1))
            WHERE domain_id = ? AND type = 'SOA'
        ",
            $domainId
        );
        return ($set->rowCount() > 0);
    }

    /**
     * @param string $name The name of the domain.
     * @param string $domainType The type of the domain.
     * @param string $soa The SOA record for the domain (only containing primary DNS and email).
     * @return bool Returns success.
     */
    public function addDomain(string $name, string $domainType, string $soa): bool
    {
        if ($this->page != 'admin')
            return false;
        $this->page->db->beginTransaction();
        try {
            $set = $this->page->db->query("INSERT INTO domains (name, type) VALUES (?, ?)", $name, $domainType);
            if ($set->rowCount() > 0) {
                $domainId = $this->page->db->getLastInsertId();
                $this->insertSpecialRecord($domainId, $name, 'SOA', $soa);
            }
            $this->page->db->commit();
            return true;
        } catch (Exception $e) {
            $this->page->db->rollBack();
            error_log("addDomain transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param int $recordId The ID of the record to delete (for admins).
     * @return bool Returns success.
     */
    public function deleteSpecialRecord(int $recordId): bool
    {
        if ($this->page != 'admin')
            return false;
        $this->page->db->beginTransaction();
        try {
            $domainId = $this->getDomainIDForRecord($recordId);
            $del = $this->page->db->query("DELETE FROM records WHERE id = ?", $recordId);
            if ($del->rowCount() > 0) {
                $result = $this->updateSOARecord($domainId);
                $this->page->db->commit();
                return $result;
            }
            $this->page->db->commit();
            return false;
        } catch (Exception $e) {
            $this->page->db->rollBack();
            error_log("deleteSpecialRecord transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete all records for a specific domain of a specific record type.
     *
     * @param int $domainId The ID of the domain for which to delete records.
     * @param string $recordType The record type to delete.
     * @return bool Returns success.
     */
    public function deleteSpecialRecords(int $domainId, string $recordType): bool
    {
        if ($this->page != 'admin')
            return false;
        $this->page->db->beginTransaction();
        try {
            $del = $this->page->db->query("DELETE FROM records WHERE domain_id = ? AND type = ?", $domainId, $recordType);
            if ($del->rowCount() > 0) {
                $result = $this->updateSOARecord($domainId);
                $this->page->db->commit();
                return $result;
            }
            $this->page->db->commit();
            return false;
        } catch (Exception $e) {
            $this->page->db->rollBack();
            error_log("deleteSpecialRecords transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * This fixes records of type SOA and appends the timestamp to the record.
     *
     * @param string $recordType The type of the record.
     * @param string $content The content string to verify.
     * @return string|null Returns the SOA content-string, or null on error.
     */
    private function getSOAContent(string $recordType, string $content): ?string
    {
        if ($recordType == 'SOA') {
            $content = trim($content);
            $content = preg_replace('/\s+/', ' ', $content);
            if (count(explode(' ', $content)) != 2)
                return null;
            $content = sprintf("%s %u", $content, time());
        }
        return $content;
    }

    /**
     * Inserts a special (admin-only) record for a specific domain.
     *
     * @param int $domainId The ID of the domain.
     * @param string $name The name of the record to add.
     * @param string $recordType The type of the record to add.
     * @param string $content The content of the record to add.
     * @param int $ttl The TTL of the record to add.
     * @return bool Return success.
     */
    public function insertSpecialRecord(int $domainId, string $name, string $recordType, string $content, int $ttl = 86400): bool
    {
        if ($this->page != 'admin')
            return false;
        if ($content && strlen($content) > 0) {
            if ($name === null)
                $name = $this->getDomainName($domainId);
            $content = $this->getSOAContent($recordType, $content);
            if ($content === null)
                return false;
            $this->page->db->beginTransaction();
            try {
                $in = $this->page->db->query("
                    INSERT INTO records
                      (domain_id, name, type, content, ttl)
                    VALUES
                      (?, ?, ?, ?, ?)
                ",
                    $domainId,
                    $name,
                    $recordType,
                    $content,
                    $ttl
                );
                if ($in->rowCount() > 0) {
                    $this->touchRecord($this->page->db->getLastInsertId());
                    $result = $this->updateSOARecord($domainId);
                    $this->page->db->commit();
                    return $result;
                }
                $this->page->db->commit();
            } catch (Exception $e) {
                $this->page->db->rollBack();
                error_log("insertSpecialRecord transaction failed: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    /**
     * Updates a specific special (admin-only) record.
     *
     * @param string $recordId The ID of the record.
     * @param string $name The name of the record to add.
     * @param string $recordType The type of the record to add.
     * @param string $content The content of the record to add.
     * @param int $ttl The TTL of the record to add.
     * @return bool Returns success.
     */
    public function updateSpecialRecord(string $recordId, string $name, string $recordType, string $content, int $ttl): bool
    {
        if ($this->page != 'admin')
            return false;
        $this->page->db->beginTransaction();
        try {
            $set = $this->page->db->query("
                UPDATE records
                SET name = ?, type = ?, content = ?, ttl = ?
                 WHERE id = ?
            ",
                $name, $recordType, $content, $ttl, $recordId
            );
            if ($set->rowCount() > 0) {
                $this->touchRecord($recordId);
                $result = $this->updateSOARecord($this->getDomainIDForRecord($recordId));
                $this->page->db->commit();
                return $result;
            }
            $this->page->db->commit();
            return false;
        } catch (Exception $e) {
            $this->page->db->rollBack();
            error_log("updateSpecialRecord transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a domain and all records of it.
     *
     * @param int $domainId The ID of the domain to delete.
     * @return bool Returns success.
     */
    public function deleteDomain(int $domainId): bool
    {
        if ($this->page != 'admin')
            return false;
        $del = $this->page->db->query("
            DELETE d, r, ptr
            FROM domains d
            LEFT JOIN records r
              ON d.id = r.domain_id
            LEFT JOIN records ptr
              ON r.name = ptr.content AND ptr.type = 'PTR'
            WHERE d.id=?
        ",
            $domainId
        );
        return $del->rowCount() > 0;
    }

    /**
     * Updates the name of a domain.
     *
     * @param int $domainId The ID of the domain.
     * @param string $domainName The new name of the domain.
     * @return bool Returns success.
     */
    public function updateDomainName(int $domainId, string $domainName): bool
    {
        if ($this->page != 'admin' ||
            strlen($domainName) < 1 || strlen($domainName) > 255
        )
            return false;
        $set = $this->page->db->query("
            UPDATE domains
            SET name = ?
            WHERE id = ?
        ",
            $domainName, $domainId
        );
        if ($set->rowCount() > 0)
            return $this->updateSOARecord($domainId);
        return false;
    }


    /**
     * Returns the list of all domain names and their corresponding ID.
     *
     * @return array|null The list of domains, or null if unauthorized.
     */
    public function getDomainsMini(): ?array
    {
        if ($this->page == 'nobody')
            return null;
        $get = $this->page->db->query("
			SELECT id, name
			FROM domains
			WHERE name NOT LIKE '%.arpa'
			ORDER BY name
		");
        return $get->fetchall();
    }

    /**
     * Returns the PTR address for an IP address.
     *
     * @param string $recordType The type of the record.
     * @param string $address The address to convert.
     * @return null|string The PTR-name for the address, or null on error.
     */
    private function getPTRName(string $recordType, string $address): ?string
    {
        switch ($recordType) {
            case 'A':
                return sprintf('%s.in-addr.arpa',
                    implode('.', array_reverse(explode('.', $address))));

            case 'AAAA':
                $address = bin2hex(inet_pton($address));
                return sprintf('%s.ip6.arpa',
                    implode('.', array_reverse(str_split($address))));
        }

        return null;
    }

    /**
     * Returns the corresponding domain ID for a PTR address.
     *
     * @param string $ptr The PTR address.
     * @return null|int The corresponding domain ID, or null on error.
     */
    private function getPTRDomainID(string $ptr): ?int
    {
        $q = $this->page->db->query("
			SELECT id, name FROM domains
			WHERE type = 'NATIVE' AND name LIKE '%.arpa'
		");

        $domains = array();

        while ($domain = $q->fetch())
            $domains[$domain['name']] = $domain['id'];

        $parts = explode('.', $ptr);
        $count = count($parts);

        while ($count-- > 1) {
            $cmp = implode('.', $parts);

            if (isset($domains[$cmp]))
                return $domains[$cmp];

            array_shift($parts);
        }

        return null;
    }

    /**
     * Creates a new record for a domain.
     *
     * @param int $domainId The ID of the domain.
     * @param string $recordType The type of the record.
     * @param string $name The name of the record.
     * @param string $content The content of the record.
     * @param string $password The password for record updates.
     * @param int $ttl The TTL of the record.
     * @return bool Returns success.
     */
    public function addRecord(int $domainId, string $recordType, string $name, string $content, string $password, int $ttl): bool
    {
        if ($this->page == 'nobody')
            return false;
        if (!in_array($recordType, array('A', 'AAAA', 'CNAME')))
            return false;
        $ttl = intval($ttl);
        if (strlen($name) == 0 || strlen($content) == 0 || $ttl < 1 || $ttl > 2147483647)
            return false;
        if (!$this->testRecordType($name, $recordType))
            return false;
        if (($name = $this->fixRecordName($domainId, $name)) === null)
            return false;
        if (!$this->isValidDomainName($name))
            return false;
        $this->page->db->beginTransaction();
        try {
            $set = $this->page->db->query("
                INSERT INTO records
                  (domain_id, name, type, content, ttl)
                VALUES
                  (?, ?, ?, ?, ?)
            ",
                $domainId, $name, $recordType, $content, $ttl
            );
            if ($set->rowCount() == 0) {
                $this->page->db->rollBack();
                return false;
            }

            $recordId = $this->page->db->getLastInsertId();
            $this->touchRecord($recordId);
            $this->page->db->query("
                INSERT INTO dns_records_users
                VALUES
                (?, ?, ?)
            ",
                $recordId, $password, $this->page
            );

            $ptr = $this->getPTRName($recordType, $content);
            $pid = $ptr !== null ? $this->getPTRDomainID($ptr) : null;

            if ($pid !== null && $ptr !== null)
                $this->page->db->query("
                    INSERT INTO records
                      (domain_id, name, type, content, ttl)
                    VALUES
                      (?, ?, ?, ?, ?)
                ",
                    $pid, $ptr, 'PTR', $name, $ttl
                );

            $result = $this->updateSOARecord($domainId);
            $this->page->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->page->db->rollBack();
            error_log("addRecord transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fixes a record name.
     *
     * @param int $domainId The ID of the domain.
     * @param string $recordName The name of the record.
     * @return string|null The fixed record name, or null on error.
     */
    public function fixRecordName(int $domainId, string $recordName): ?string
    {
        $domN = $this->page->db->query("SELECT name FROM domains WHERE id = ?", $domainId);
        if (!($dom = $domN->fetch()))
            return null;
        $dom = $dom['name'];
        if (!preg_match("/$dom\$/", $recordName))
            $recordName = sprintf("%s.%s", $recordName, $dom);
        return preg_replace('/\.\.+/', '.', $recordName);
    }

    /**
     * Checks whether a specific record name for a specific type is still free.
     * A CNAME for an existing A or AAAA record has to be for the same user.
     *
     * @param string $recordName The name of the record.
     * @param string $recordType The type of the record.
     * @return bool Whether this domain is free, or false on error.
     */
    public function isFreeDomain(string $recordName, string $recordType): bool
    {
        $dom = $this->page->db->query("
            SELECT
              COUNT(*) AS c
            FROM records r
            LEFT JOIN dns_records_users dru
              ON r.id = dru.records_id
            WHERE
              (r.name = ? AND dru.user != ?) OR
              (r.name = ? AND r.type = ?) OR
              (r.name = ? AND r.type = 'CNAME' AND ? IN ('A', 'AAAA')) OR
              (r.name = ? AND r.type IN ('A', 'AAAA') AND ? = 'CNAME')
        ",
            $recordName,
            $this->page,
            $recordName,
            $recordType,
            $recordName,
            $recordType,
            $recordName,
            $recordType
        );
        $row = $dom->fetch();
        return $row && $row['c'] == 0;
    }

    /**
     * Checks whether a specified domain name is valid.
     *
     * @param string $name The name of the record.
     * @return bool Whether it is valid.
     */
    public function isValidDomainName(string $name): bool
    {
        if (
            preg_match('/^\./', $name) ||
            preg_match('/\.$/', $name) ||
            preg_match('/(^|\.)-/', $name) ||
            preg_match('/-(\.|$)/', $name) ||
            !preg_match('/^[0-9a-zA-Z.-]+$/', $name) ||
            preg_match('/\.\./', $name) ||
            strlen($name) > 253
        )
            return false;

        return array_all(explode('.', $name), fn($label) => strlen($label) <= 63 && strlen($label) != 0);
    }

    /**
     * Checks whether the IP is valid.
     *
     * @param string $ip The IP.
     * @param string $type The type of the IP.
     * @return bool Whether the IP is valid.
     */
    private function isValidIP(string $ip, string $type): bool
    {
        switch ($type) {
            case 'A':
                return $this->isValidIPv4($ip);
            case 'AAAA':
                return $this->isValidIPv6($ip);
        }
        return false;
    }

    /**
     * Checks whether the IPv4 is valid.
     *
     * @param string $ip TheIP.
     * @return bool Whether the IP is valid.
     */
    private function isValidIPv4(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Checks whether the IPv6 is valid.
     *
     * @param string $ip TheIP.
     * @return bool Whether the IP is valid.
     */
    private function isValidIPv6(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Returns the list of records for the current user.
     *
     * @return array|null List of records, or null if unauthorized.
     */
    public function getMyRecords(): ?array
    {
        if ($this->page == 'nobody')
            return null;
        $get = $this->page->db->query("
            SELECT
              d.id AS domain_id,
              d.name AS domain_name,
              r.id AS id,
              r.name AS name,
              r.type AS type,
              r.content AS content,
              r.ttl AS ttl,
              r.prio AS prio,
              dr.change_date AS change_date,
              dru.password AS password
			FROM records r
            INNER JOIN domains d
              ON r.domain_id = d.id
            LEFT JOIN dns_records dr
              ON r.id = dr.records_id
            LEFT JOIN dns_records_users dru
              ON r.id = dru.records_id
            WHERE
              dru.user = ? AND
              r.type IN ('A', 'AAAA', 'CNAME')
            ORDER BY d.name, r.type, r.name, r.content
        ",
            $this->page
        );
        return $get->fetchall();
    }

    /**
     * Removes a record with the specified ID.
     *
     * @param int $recordId The record to remove.
     * @return bool Whether the deletion was successful.
     */
    public function deleteRecord(int $recordId): bool
    {
        $this->page->db->beginTransaction();
        try {
            $did = $this->getDomainIDForRecord($recordId);
            $set = $this->page->db->query("
                DELETE r, dru, ptr FROM records r
                LEFT JOIN dns_records_users dru
                  ON r.id = dru.records_id
                LEFT JOIN records ptr
                  ON r.name = ptr.content AND ptr.type = 'PTR'
                WHERE
                  r.id = ? AND
                  (? = 'admin' OR user = ?)
            ",
                $recordId,
                $this->page,
                $this->page
            );
            if ($set->rowCount() > 0) {
                $result = $this->updateSOARecord($did);
                $this->page->db->commit();
                return $result;
            }
            $this->page->db->commit();
            return false;
        } catch (Exception $e) {
            $this->page->db->rollBack();
            error_log("deleteRecord transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates a specific field of a record.
     *
     * @param int $recordId The ID of the record.
     * @param string $key The key to update.
     * @param string $value The value to update the key to.
     * @return bool Whether the update was successful.
     */
    public function updateRecord(int $recordId, string $key, string $value): bool
    {
        if (
            $key == 'name' &&
            !$this->testRecordType($value, null, $recordId)
        )
            return false;

        switch ($key) {
            case 'name':
            case 'content':
            case 'ttl':
                $table = 'r';
                break;
            case 'password':
                $table = 'dru';
                break;
            default:
                return false;
        }

        $this->page->db->beginTransaction();
        try {
            $set = $this->page->db->query("
                UPDATE records r
                LEFT JOIN dns_records_users dru
                  ON r.id = dru.records_id
                INNER JOIN dns_users u
                  ON dru.user = u.username
                SET
                  $table.$key = ?
                WHERE
                  id = ? AND
                  (? = 'admin' OR u.username = ?)
            ",
                $value,
                $recordId,
                $this->page,
                $this->page
            );

            if ($set->rowCount() > 0) {
                $this->touchRecord($recordId);
                $result = $this->updateSOARecord($this->getDomainIDForRecord($recordId));
                $this->page->db->commit();
                return $result;
            }

            $this->page->db->commit();
            return false;
        } catch (Exception $e) {
            $this->page->db->rollBack();
            error_log("updateRecord transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update the content field of a record with a specific ID.
     *
     * @param int $recordId The record ID.
     * @param string $password The update password.
     * @param string|null $content The content, or null to auto-detect IP.
     * @return bool Whether the record is set correctly.
     */
    public function recordUpdateIP(int $recordId, string $password, ?string $content = null): bool
    {
        $get = $this->page->db->query("SELECT name, type FROM records WHERE id = ?", $recordId);
        if ($row = $get->fetch())
            return $this->recordUpdateIPx($row['name'], $password, $row['type'], $content);
        return false;
    }

    /**
     * Update the content field of a record by name and type.
     *
     * @param string $recordName The record name.
     * @param string $password The update password.
     * @param string $recordType The record type ('A' or 'AAAA').
     * @param string|null $content The content, or null to auto-detect IP.
     * @return bool Whether the record is set correctly.
     */
    public function recordUpdateByName(string $recordName, string $password, string $recordType, ?string $content = null): bool
    {
        if (!in_array($recordType, array('A', 'AAAA')))
            return false;
        return $this->recordUpdateIPx($recordName, $password, $recordType, $content);
    }

    /**
     * Update the content field of a record with the specified domain name.
     *
     * @param string $recordName The name of the record.
     * @param string $password The update password of the record.
     * @param string $recordType The type of the record.
     * @param string|null $content The content, or null to use the IP the request came from.
     * @return bool Whether successful.
     */
    private function recordUpdateIPx(string $recordName, string $password, string $recordType, ?string $content = null): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $check = $this->page->db->query(
            "SELECT COUNT(*) AS c FROM dns_login_attempts WHERE ip = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            $ip
        );
        $row = $check->fetch();
        if ($row && $row['c'] >= 10)
            return false;

        if ($content === null) {
            if ($recordType === 'A')
                $content = $this->page->currentUser->getIPv4();
            elseif ($recordType === 'AAAA')
                $content = $this->page->currentUser->getIPv6();
            if ($content === null)
                return false;
        }

        switch ($recordType) {
            case 'A':
                if (!$this->isValidIPv4($content))
                    return false;
                break;

            case 'AAAA':
                if (!$this->isValidIPv6($content))
                    return false;
                break;

            case 'CNAME':
                if (!$this->isValidDomainName($content))
                    return false;
                break;

            default:
                return false;
        }

        $this->page->db->beginTransaction();
        try {
            $check = $this->page->db->query("
                UPDATE records r
                LEFT JOIN dns_records_users dru
                  ON r.id = dru.records_id
                SET r.content = ?
                WHERE
                  r.name = ? AND
                  dru.password = ? AND
                  LENGTH(dru.password) > 0 AND
                  r.type = ?
            ",
                $content, $recordName, $password, $recordType
            );

            if ($check->rowCount() > 0) {
                $this->page->db->query("DELETE FROM dns_login_attempts WHERE ip = ?", $ip);

                $recordIdQuery = $this->page->db->query("
                    SELECT r.id FROM records r
                    LEFT JOIN dns_records_users dru ON r.id = dru.records_id
                    WHERE r.name = ? AND dru.password = ? AND LENGTH(dru.password) > 0 AND r.type = ?
                ", $recordName, $password, $recordType);
                if ($rid = $recordIdQuery->fetch())
                    $this->touchRecord($rid['id']);

                $ptr = $this->getPTRName($recordType, $content);

                if (isset($ptr))
                    $this->page->db->query("
                        UPDATE records
                        SET name = ?
                        WHERE type = 'PTR' AND content = ?
                    ",
                        $ptr, $recordName
                    );

                $get = $this->page->db->query("
                    SELECT domain_id
                    FROM records r
                    LEFT JOIN dns_records_users dru
                      ON r.id = dru.records_id
                    WHERE
                      r.name = ? AND
                      dru.password = ? AND
                      LENGTH(dru.password) > 0 AND
                      r.type = ?
                ",
                    $recordName, $password, $recordType
                );
                if ($row = $get->fetch()) {
                    $result = $this->updateSOARecord($row['domain_id']);
                    $this->page->db->commit();
                    return $result;
                }
            } else {
                $get = $this->page->db->query("
                    SELECT COUNT(*) AS c
                    FROM records r
                    LEFT JOIN dns_records_users dru
                      ON r.id = dru.records_id
                    WHERE
                      r.name = ? AND
                      dru.password = ? AND
                      LENGTH(dru.password) > 0 AND
                      r.type = ?
                ",
                    $recordName, $password, $recordType
                );
                $row = $get->fetch();
                if ($row && $row['c'] == 1) {
                    $this->page->db->commit();
                    return true;
                }

                $this->page->db->query(
                    "INSERT INTO dns_login_attempts (ip, username, attempt_time) VALUES (?, ?, NOW())",
                    $ip, $recordName
                );
            }
            $this->page->db->commit();
            return false;
        } catch (Exception $e) {
            $this->page->db->rollBack();
            error_log("recordUpdateIPx transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Returns the ID of the domain for a specific record ID.
     *
     * @param int $recordId The ID of a record.
     * @return int|null The domain ID, or null on error.
     */
    private function getDomainIDForRecord(int $recordId): ?int
    {
        $get = $this->page->db->query("SELECT domain_id FROM records WHERE id = ?", $recordId);
        if ($row = $get->fetch())
            return (int) $row['domain_id'];
        return null;
    }

    /**
     * Check the following:
     * a) a recordID for a recordName matches.
     * b) a recordType for a recordID matches.
     *
     * @param string $recordName The name of a record.
     * @param string $recordType The type of a record.
     * @param int|null $recordId The ID of a record.
     * @return bool Whether successful.
     */
    private function testRecordType(string $recordName, string $recordType, ?int $recordId = null): bool
    {
        if ($recordType == null) {
            $get = $this->page->db->query("SELECT type FROM records WHERE id = ?", $recordId);
            if ($row = $get->fetch())
                $recordType = $row['type'];
        }
        $get = $this->page->db->query("SELECT id FROM records WHERE name = ? AND type = ?", $recordName, $recordType);
        $ok = true;
        while ($row = $get->fetch()) {
            if ($row['id'] != $recordId)
                $ok = false;
        }
        return $ok;
    }
}
