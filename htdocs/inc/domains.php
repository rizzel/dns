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
    private $page;

    function __construct($page)
    {
        $this->page = $page;
    }

    /**
     * @return array|bool Returns a list of all domains and their admin records, or FALSE on failure.
     */
    public function getDomains()
    {
        if ($this->page->currentUser->getLevel() != 'admin')
            return FALSE;
        $get = $this->page->db->query("
			SELECT * FROM domains d ORDER BY name
		");
        $result = $get->fetchall();
        foreach ($result AS &$r) {
            $get = $this->page->db->query("
                SELECT
                  r.id,
                  r.name,
                  r.type,
                  IF(r.type = 'SOA', SUBSTRING_INDEX(r.content, ' ', 2), r.content) AS content,
                  r.ttl
                FROM records r
                LEFT JOIN dns_records_users dru
                  ON dru.records_id is null or r.id = dru.records_id
				WHERE r.domain_id = ? AND (r.type NOT IN ('A', 'AAAA', 'CNAME') OR dru.user = '' or dru.records_id is null)
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
    private function updateSOARecord($domainId)
    {
        $set = $this->page->db->query("
            UPDATE records
            SET content = CONCAT(SUBSTRING_INDEX(content, ' ', 2), ' ', UNIX_TIMESTAMP())
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
    public function addDomain($name, $domainType, $soa)
    {
        if ($this->page->currentUser->getLevel() != 'admin')
            return FALSE;
        $set = $this->page->db->query("INSERT INTO domains (name, type) VALUES (?, ?)", $name, $domainType);
        if ($set->rowCount() > 0) {
            $recordId = $this->page->db->getLastInsertId();
            $this->insertSpecialRecord($recordId, $name, 'SOA', $soa);
        }
        return TRUE;
    }

    /**
     * @param int $recordId The ID of the record to delete (for admins).
     * @return bool Returns success.
     */
    public function deleteSpecialRecord($recordId)
    {
        if ($this->page->currentUser->getLevel() != 'admin')
            return FALSE;
        $domainId = $this->getDomainIDForRecord($recordId);
        $del = $this->page->db->query("DELETE FROM records WHERE id = ?", $recordId);
        if ($del->rowCount() > 0)
            return $this->updateSOARecord($domainId);
        return FALSE;
    }

    /**
     * Delete all records for a specific domain of a specific record type.
     *
     * @param int $domainId The ID of the domain for which to delete records.
     * @param string $recordType The record type to delete.
     * @return bool Returns success.
     */
    public function deleteSpecialRecords($domainId, $recordType)
    {
        if ($this->page->currentUser->getLevel() != 'admin')
            return FALSE;
        $del = $this->page->db->query("DELETE FROM records WHERE domain_id = ? AND type = ?", $domainId, $recordType);
        if ($del->rowCount() > 0)
            return $this->updateSOARecord($domainId);
        return FALSE;
    }

    /**
     * This fixes records of type SOA and appends the timestamp to the record.
     *
     * @param string $recordType The type of the record.
     * @param string $content The content string to verify.
     * @return bool|string Returns the SOA content-string, or FALSE on error.
     */
    private function getSOAContent($recordType, $content)
    {
        if ($recordType == 'SOA') {
            $content = trim($content);
            preg_replace('/\s+/', ' ', $content);
            if (count(explode(' ', $content)) != 2)
                return FALSE;
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
    public function insertSpecialRecord($domainId, $name, $recordType, $content, $ttl = 86400)
    {
        if ($this->page->currentUser->getLevel() != 'admin')
            return FALSE;
        if ($content && strlen($content) > 0) {
            if ($name === NULL)
                $name = $this->getDomainName($domainId);
            $content = $this->getSOAContent($recordType, $content);
            if ($content === FALSE)
                return FALSE;
            $in = $this->page->db->query("
                INSERT INTO records
                  (domain_id, name, type, content, ttl, change_date)
				VALUES
                  (?, ?, ?, ?, ?, UNIX_TIMESTAMP())
			",
                $domainId,
                $name,
                $recordType,
                $content,
                $ttl
            );
            if ($in->rowCount() > 0)
                return $this->updateSOARecord($domainId);
        }
        return FALSE;
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
    public function updateSpecialRecord($recordId, $name, $recordType, $content, $ttl)
    {
        if ($this->page->currentUser->getLevel() != 'admin')
            return FALSE;
        $set = $this->page->db->query("
            UPDATE records
            SET name = ?, type = ?, content = ?, ttl = ?
             WHERE id = ?
        ",
            $name, $recordType, $content, $ttl, $recordId
        );
        if ($set->rowCount() > 0)
            return $this->updateSOARecord($this->getDomainIDForRecord($recordId));
        return FALSE;
    }

    /**
     * Deletes a domain and all records of it.
     *
     * @param int $domainId The ID of the domain to delete.
     * @return bool Returns success.
     */
    public function deleteDomain($domainId)
    {
        if ($this->page->currentUser->getLevel() != 'admin')
            return FALSE;
        $this->page->db->query("
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
        return $this->page->db->handle->errorCode() === '00000';
    }

    /**
     * Updates the name of a domain.
     *
     * @param int $domainId The ID of the domain.
     * @param string $domainName The new name of the domain.
     * @return bool Returns success.
     */
    public function updateDomainName($domainId, $domainName)
    {
        if ($this->page->currentUser->getLevel() != 'admin' ||
            strlen($domainName) < 1 || strlen($domainName) > 255
        )
            return FALSE;
        $set = $this->page->db->query("
            UPDATE domains
            SET name = ?
            WHERE id = ?
        ",
            $domainName, $domainId
        );
        if ($set->rowCount() > 0)
            return $this->updateSOARecord($domainId);
        return FALSE;
    }

    /**
     * Returns the name of a domain specified by its ID.
     *
     * @param int $domainId The ID of the domain.
     * @return string|bool The name of the domain, or FALSE on error.
     */
    private function getDomainName($domainId)
    {
        $get = $this->page->db->query("SELECT name FROM domains WHERE id = ?", $domainId);
        if ($get && $row = $get->fetch())
            return $row['name'];
        return FALSE;
    }

    /**
     * Returns the list of all domain names and their corresponding ID.
     *
     * @return array|bool The list of domains, or FALSE on error.
     */
    public function getDomainsMini()
    {
        if ($this->page->currentUser->getLevel() == 'nobody')
            return FALSE;
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
     * @return null|string The PTR-name for the address, or NULL on error.
     */
    private function getPTRName($recordType = 'A', $address)
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

        return NULL;
    }

    /**
     * Returns the corresponding domain ID for a PTR address.
     *
     * @param string $ptr The PTR address.
     * @return null|int The corresponding domain ID, or NULL on error.
     */
    private function getPTRDomainID($ptr)
    {
        if (!isset($ptr))
            return NULL;

        $q = $this->page->db->query("
			SELECT id, name FROM domains
			WHERE type = 'NATIVE' AND name LIKE '%.arpa'
		");

        $domains = array();

        while ($q && $domain = $q->fetch())
            $domains[$domain['name']] = $domain['id'];

        $parts = explode('.', $ptr);
        $count = count($parts);

        while ($count-- > 1) {
            $cmp = implode('.', $parts);

            if (isset($domains[$cmp]))
                return $domains[$cmp];

            array_shift($parts);
        }

        return NULL;
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
    public function addRecord($domainId, $recordType, $name, $content, $password, $ttl)
    {
        if ($this->page->currentUser->getLevel() == 'nobody')
            return FALSE;
        if (!in_array($recordType, array('A', 'AAAA', 'CNAME')))
            return FALSE;
        if (strlen($name) == 0 || strlen($content) == 0 || !preg_match('/^\d+$/', $ttl))
            return FALSE;
        if (!$this->testRecordType($name, $recordType))
            return FALSE;
        if (($name = $this->fixRecordName($domainId, $name)) === FALSE)
            return FALSE;
        if (!$this->isValidDomainName($name))
            return FALSE;
        $set = $this->page->db->query("
            INSERT INTO records
              (domain_id, name, type, content, ttl, change_date)
            VALUES
			  (?, ?, ?, ?, ?, UNIX_TIMESTAMP())
		",
            $domainId, $name, $recordType, $content, $ttl
        );
        if ($set->rowCount() > 0) {
            $recordId = $this->page->db->getLastInsertId();
            $this->page->db->query("
                INSERT INTO dns_records_users
                VALUES
                (?, ?, ?)
            ",
                $recordId, $password, $this->page->currentUser->getUserName()
            );
            $ptr = $this->getPTRName($recordType, $content);
            $pid = $this->getPTRDomainID($ptr);

            if (isset($pid) && isset($ptr))
                $this->page->db->query("
                    INSERT INTO records
                      (domain_id, name, type, content, ttl, change_date)
                    VALUES
                      (?, ?, ?, ?, ?, UNIX_TIMESTAMP())
                ",
                    $pid, $ptr, 'PTR', $name, $ttl
                );

            //		$this->page->email->sendToCurrent(
            //			"Neuer Record: $name",
            //			"Für Ihren Nutzer wurde ein neuer Record angelegt:
            //Name:    $name
            //Typ:     $recordType
            //Content: $content"
            //		);
            return $this->updateSOARecord($domainId);
        }
        return FALSE;
    }

    /**
     * Fixes a record name.
     *
     * @param $domainId The ID of the domain.
     * @param $recordName The name of the record.
     * @return bool|string The fixed record name, or FALSE on error.
     */
    public function fixRecordName($domainId, $recordName)
    {
        $domN = $this->page->db->query("SELECT name FROM domains WHERE id = ?", $domainId);
        if (!$domN || !($dom = $domN->fetch()))
            return FALSE;
        $dom = $dom['name'];
        if (!preg_match("/$dom\$/", $recordName))
            $recordName = sprintf("%s.%s", $recordName, $dom);
        preg_replace('/\.\.+/', '.', $recordName);
        return $recordName;
    }

    /**
     * Checks whether a specific record name for a specific type is still free.
     * A CNAME for an existing A or AAAA record has to be for the same user.
     *
     * @param string $recordName The name of the record.
     * @param string $recordType The type of the record.
     * @return bool Whether this domain is free, or FALSE on error.
     */
    public function isFreeDomain($recordName, $recordType)
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
            $this->page->currentUser->getUserName(),
            $recordName,
            $recordType,
            $recordName,
            $recordType,
            $recordName,
            $recordType
        );
        if ($dom && $row = $dom->fetch())
            return ($row['c'] == 0);
        return FALSE;
    }

    /**
     * Checks whether a specified domain name is valid.
     *
     * @param string $name The name of the record.
     * @return bool Whether it is valid.
     */
    public function isValidDomainName($name)
    {
        return (
            !preg_match('/^\./', $name) && // darf nicht mit einem punkt beginnen
            !preg_match('/\.$/', $name) && // darf nicht mit einem punkt aufhören
            !preg_match('/(^|\.)-/', $name) && // darf kein - am anfang einer subdomain haben
            !preg_match('/-(\.|$)/', $name) && // darf kein - am ende einer subdomain haben
            preg_match('/^[0-9a-zA-Z.-]+$/', $name) // darf nur diese zeichen beinhalten
        );
    }

    /**
     * Checks whether the IP is valid.
     *
     * @param string $ip The IP.
     * @param string $type The type of the IP.
     * @return bool Whether the IP is valid.
     */
    private function isValidIP($ip, $type)
    {
        switch ($type) {
            case 'A':
                return $this->isValidIPv4($ip);
            case 'AAAA':
                return $this->isValidIPv6($ip);
        }
        return FALSE;
    }

    /**
     * Checks whether the IPv4 is valid.
     *
     * @param string $ip TheIP.
     * @return bool Whether the IP is valid.
     */
    private function isValidIPv4($ip)
    {
        if (function_exists("filter_var")) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, array('flags' => FILTER_FLAG_IPV4)))
                return FALSE;
        } else {
            if (inet_pton($ip) === FALSE || strchr($ip, ":") !== FALSE)
                return FALSE;
        }
        return TRUE;
    }

    /**
     * Checks whether the IPv6 is valid.
     *
     * @param string $ip TheIP.
     * @return bool Whether the IP is valid.
     */
    private function isValidIPv6($ip)
    {
        if (function_exists("filter_var")) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, array('flags' => FILTER_FLAG_IPV6)))
                return FALSE;
        } else {
            if (inet_pton($ip) === FALSE || strchr($ip, ':') === FALSE)
                return FALSE;
        }
        return TRUE;
    }

    /**
     * Returns the list of records for the current user.
     *
     * @return array|bool List of records, or FALSE on error.
     */
    public function getMyRecords()
    {
        if ($this->page->currentUser->getLevel() == 'nobody')
            return FALSE;
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
              r.change_date AS change_date,
              dru.password AS password
			FROM records r
            INNER JOIN domains d
              ON r.domain_id = d.id
            LEFT JOIN dns_records_users dru
              ON r.id = dru.records_id
            WHERE
              dru.user = ? AND
              r.type IN ('A', 'AAAA', 'CNAME')
            ORDER BY d.name, r.type, r.name, r.content
        ",
            $this->page->currentUser->getUserName()
        );
        return $get->fetchall();
    }

    /**
     * Removes a record with the specified ID.
     *
     * @param int $recordId The record to remove.
     * @return bool Whether the deletion was successful.
     */
    public function deleteRecord($recordId)
    {
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
            $this->page->currentUser->getLevel(),
            $this->page->currentUser->getUserName()
        );
        if ($set->rowCount() > 0) {
            return $this->updateSOARecord($did);
        }
        return FALSE;
    }

    /**
     * Updates a specific field of a record.
     *
     * @param int $recordId The ID of the record.
     * @param string $key The key to update.
     * @param string $value The value to update the key to.
     * @return bool Whether the update was successful.
     */
    public function updateRecord($recordId, $key, $value)
    {
        if (
            $key == 'name' &&
            !$this->testRecordType($value, NULL, $recordId)
        )
            return FALSE;

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
                return FALSE;
        }

        $set = $this->page->db->query("
            UPDATE records r
            LEFT JOIN dns_records_users dru
              ON r.id = dru.records_id
            INNER JOIN dns_users u
              ON dru.user = u.username
            SET
              $table.$key = ?,
              change_date = UNIX_TIMESTAMP()
            WHERE
              id = ? AND
              (? = 'admin' OR u.username = ?)
        ",
            $value,
            $recordId,
            $this->page->currentUser->getLevel(),
            $this->page->currentUser->getUserName()
        );

        if ($set->rowCount() > 0)
            return $this->updateSOARecord($this->getDomainIDForRecord($recordId));

        return FALSE;
    }

    /**
     * Update the content field of a record with a specific ID.
     *
     * @param array $args Array containing DomainID, Password, Content.
     * @return bool Whether the record is set correctly.
     */
    public function recordUpdateIP($args)
    {
        if (count($args) < 2 || count($args) > 3)
            return FALSE;
        $get = $this->page->db->query("SELECT name, type FROM records WHERE id = ?", $args[0]);
        if ($get && $row = $get->fetch()) {
            return $this->recordUpdateIPx($row['name'], $args[1], $row['type'], isset($args[2]) ? $args[2] : NULL);
        } else {
            return FALSE;
        }
    }

    /**
     * Update the content field of a IPv4 record with the specified name.
     *
     * @param array $args Array containing DomainName, Password, Content.
     * @return bool Whether the record is set correctly.
     */
    public function recordUpdateIP4($args)
    {
        return $this->recordUpdateIPx($args[0], $args[1], 'A', isset($args[2]) ? $args[2] : NULL);
    }

    /**
     * Update the content field of a IPv6 record with the specified name.
     *
     * @param array $args Array containing DomainName, Password, Content.
     * @return bool Whether the record is set correctly.
     */
    public function recordUpdateIP6($args)
    {
        return $this->recordUpdateIPx($args[0], $args[1], 'AAAA', isset($args[2]) ? $args[2] : NULL);
    }

    /**
     * Update the content field of a record with the specified domain name.
     *
     * @param string $recordName The name of the record.
     * @param string $password The update password of the record.
     * @param string $recordType The type of the record.
     * @param string $content The content, or null to use the IP the request came from.
     * @return bool Whether successful.
     */
    private function recordUpdateIPx($recordName, $password, $recordType, $content = NULL)
    {
        if ($content == NULL)
            if (in_array($recordType, array('A', 'AAAA'))) {
                $ips = $this->page->currentUser->getIPs();
                $content = $ips[0];
            } else
                return FALSE;

        switch ($recordType) {
            case 'A':
                if (!$this->isValidIPv4($content))
                    return FALSE;
                break;

            case 'AAAA':
                if (!$this->isValidIPv6($content))
                    return FALSE;
                break;

            case 'CNAME':
                if (!$this->isValidDomainName($content))
                    return FALSE;
                break;

            default:
                return FALSE;
        }

        $check = $this->page->db->query("
            UPDATE records r
            LEFT JOIN dns_records_users dru
              ON r.id = dru.records_id
            SET r.content = ?, r.change_date = UNIX_TIMESTAMP()
            WHERE
              r.name = ? AND
              dru.password = ? AND
              LENGTH(dru.password) > 0 AND
              r.type = ?
        ",
            $content, $recordName, $password, $recordType
        );

        if ($check->rowCount() > 0) {
            $ptr = $this->getPTRName($recordType, $content);

            if (isset($ptr))
                $this->page->db->query("
					UPDATE records
                    SET name = ?, change_date = UNIX_TIMESTAMP()
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
            if ($get && $row = $get->fetch())
                return $this->updateSOARecord($row['domain_id']);
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
            if ($get && $row = $get->fetch())
                return ($row['c'] == 1);
        }
        return FALSE;
    }

    /**
     * Returns the ID of the domain for a specific record ID.
     *
     * @param int $recordId The ID of a record.
     * @return bool The domain ID, or FALSE on error.
     */
    private function getDomainIDForRecord($recordId)
    {
        $get = $this->page->db->query("SELECT domain_id FROM records WHERE id = ?", $recordId);
        if ($get && $row = $get->fetch())
            return $row['domain_id'];
        return FALSE;
    }

    /**
     * Check the following:
     * a) a recordID for a recordName matches.
     * b) a recordType for a recordID matches.
     *
     * @param string $recordName The name of a record.
     * @param string $recordType The type of a record.
     * @param int $recordId The ID of a record.
     * @return bool Whether successful.
     */
    private function testRecordType($recordName, $recordType, $recordId = NULL)
    {
        if ($recordType == NULL) {
            $get = $this->page->db->query("SELECT type FROM records WHERE id = ?", $recordId);
            if ($get && $row = $get->fetch())
                $recordType = $row['type'];
        }
        $get = $this->page->db->query("SELECT id FROM records WHERE name = ? AND type = ?", $recordName, $recordType);
        if ($get) {
            $ok = true;
            while ($row = $get->fetch()) {
                if ($row['id'] != $recordId)
                    $ok = FALSE;
            }
            return $ok;
        }
        return FALSE;
    }
}
