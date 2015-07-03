<?php

class DNSDomains
{
    private $page;

    function __construct($page)
    {
        $this->page = $page;
    }

    public function getDomains()
    {
        if ($this->page->user->getCurrentUser()->level != 'admin')
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
                  ON r.id = dru.records_id
				WHERE r.domain_id = ? AND (r.type NOT IN ('A', 'AAAA', 'CNAME') OR ru.user = '')
				ORDER BY r.type, r.name, r.content
			",
                $r['id']
            );
            $r['records'] = $get->fetchall();
        }
        return $result;
    }

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

    public function addDomain($name, $domainType, $soa)
    {
        if ($this->page->user->getCurrentUser()->level != 'admin')
            return FALSE;
        $set = $this->page->db->query("INSERT INTO domains (name, type) VALUES (?, ?)", $name, $domainType);
        if ($set->rowCount() > 0) {
            $recordId = $this->page->db->handle->lastInsertId();
            $this->insertSpecialRecord($recordId, $name, 'SOA', $soa);
        }
        return true;
    }

    public function deleteSpecialRecord($recordId)
    {
        if ($this->page->user->getCurrentUser()->level != 'admin')
            return FALSE;
        $domainId = $this->getDomainForRecord($recordId);
        $del = $this->page->db->query("DELETE FROM records WHERE id = ?", $recordId);
        if ($del->rowCount() > 0)
            return $this->updateSOARecord($domainId);
        return FALSE;
    }

    public function deleteSpecialRecords($domainId, $recordType)
    {
        if ($this->page->user->getCurrentUser()->level != 'admin')
            return FALSE;
        $del = $this->page->db->query("DELETE FROM records WHERE domain_id = ? AND type = ?", $domainId, $recordType);
        if ($del->rowCount() > 0)
            return $this->updateSOARecord($domainId);
        return FALSE;
    }

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

    public function insertSpecialRecord($domainId, $name, $recordType, $content, $ttl = 86400)
    {
        if ($this->page->user->getCurrentUser()->level != 'admin')
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

    public function updateSpecialRecord($recordId, $name, $recordType, $content, $ttl)
    {
        if ($this->page->user->getCurrentUser()->level != 'admin')
            return FALSE;
        $set = $this->page->db->query("
            UPDATE records
            SET name = ?, type = ?, content = ?, ttl = ?
             WHERE id = ?
        ",
            $name, $recordType, $content, $ttl, $recordId
        );
        if ($set->rowCount() > 0)
            return $this->updateSOARecord($this->getDomainForRecord($recordId));
        return FALSE;
    }

    public function deleteDomain($domainId)
    {
        if ($this->page->user->getCurrentUser()->level != 'admin')
            return FALSE;
        $this->page->db->query("
            DELETE d, r
            FROM domains d
            LEFT JOIN records r
              ON d.id = r.domain_id
            WHERE d.id=?
        ",
            $domainId
        );
        return $this->page->db->handle->errorCode === '00000';
    }

    public function updateDomainName($domainId, $domainName)
    {
        if ($this->page->user->getCurrentUser()->level != 'admin' ||
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

    private function getDomainName($domainId)
    {
        $get = $this->page->db->query("SELECT name FROM domains WHERE id = ?", $domainId);
        if ($get && $row = $get->fetch())
            return $row['name'];
        return FALSE;
    }

    public function getDomainsMini()
    {
        if ($this->page->user->getCurrentUser()->level == 'nobody')
            return FALSE;
        $get = $this->page->db->query("
			SELECT id, name
			FROM domains
			WHERE name NOT LIKE '%.arpa'
			ORDER BY name
		");
        return $get->fetchall();
    }

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

    public function addRecord($domainId, $recordType, $name, $content, $password, $ttl)
    {
        if ($this->page->user->getCurrentUser()->level == 'nobody')
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
            $recordId = $this->page->db->handle->lastInsertId();
            $this->page->db->query("
                INSERT INTO dns_records_users
                VALUES
                (?, ?, ?)
            ",
                $recordId, $password, $this->page->users->getCurrentUser()->username
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

    public function fixRecordName($domainId, $record)
    {
        $domN = $this->page->db->query("SELECT name FROM domains WHERE id = ?", $domainId);
        if (!$domN || !($dom = $domN->fetch()))
            return FALSE;
        $dom = $dom['name'];
        if (!preg_match("/$dom\$/", $record))
            $record = "$record . $dom";
        preg_replace('/\.\.+/', '.', $record);
        return $record;
    }

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
            $recordName, $this->page->user->getCurrentUser()->username, $recordName, $recordType, $recordName, $recordType, $recordName, $recordType
        );
        if ($dom && $row = $dom->fetch())
            return ($row['c'] == 0);
        return FALSE;
    }

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

    public function getMyRecords()
    {
        if ($this->page->user->getCurrentUser()->level == 'nobody')
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
            $this->page->user->getCurrentUser()->username
        );
        return $get->fetchall();
    }

    public function deleteRecord($recordId)
    {
        $did = $this->getDomainForRecord($recordId);
//		$get = $this->page->db->query("SELECT name FROM records WHERE id = ?", $recordId);
//		$row = @$get->fetch();
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
            $this->page->user->getCurrentUser()->level,
            $this->page->user->getCurrentUser()->username
        );
        if ($set->rowCount() > 0) {
            return $this->updateSOARecord($did);
        }
        return FALSE;
    }

    public function updateRecord($recordId, $key, $value)
    {
        if ($key == 'name' &&
            !$this->testRecordType($value, NULL, $recordId)
        )
            return FALSE;
//        $get = $this->page->db->query("SELECT name FROM records WHERE id = ?", $recordId);
//        $row = @$get->fetch();
        $set = $this->page->db->query("
            UPDATE records r
            LEFT JOIN dns_records_users dru
              ON r.id = dru.records_id
            INNER JOIN dns_users u
              ON dru.user = u.username
            SET
              r.$key = ?,
              change_date = UNIX_TIMESTAMP()
            WHERE
              id = ? AND
              (? = 'admin' OR u.username = ?)
        ",
            $value,
            $recordId,
            $this->page->user->getCurrentUser()->level,
            $this->page->user->getCurrentUser()->username
        );
        if ($set->rowCount() > 0) {
            //		$this->page->email->sendToCurrent(
            //			"Record geändert: " . $row['name'],
            //			"Für Ihren Nutzer wurde ein Record geändert:
            //Name:      " . $row['name'] . "
            //Parameter: $key,
            //Wert:      $value"
            //		);
            return $this->updateSOARecord($this->getDomainForRecord($recordId));
        }
        return FALSE;
    }

    /**
     * @param array $args DomainID, Password, Content
     * @return boolean Whether the record is set correctly
     */
    public function recordUpdateIP($args)
    {
        if (count($args) < 2 || count($args) > 3)
            return FALSE;
        $get = $this->page->db->query("SELECT name, type FROM records WHERE id = ?", $args[0]);
        if ($get && $row = $get->fetch()) {
            return $this->recordUpdateIPx($row['name'], $args[1], $row['type'], $args[2]);
        } else {
            return FALSE;
        }
    }

    /**
     * @param array $args DomainName, Password, Content
     * @return boolean Whether the record is set correctly
     */
    public function recordUpdateIP4($args)
    {
        return $this->recordUpdateIPx($args[0], $args[1], 'A', $args[2]);
    }

    /**
     * @param array $args DomainName, Password, Content
     * @return boolean Whether the record is set correctly
     */
    public function recordUpdateIP6($args)
    {
        return $this->recordUpdateIPx($args[0], $args[1], 'AAAA', $args[2]);
    }

    private function recordUpdateIPx($recordName, $password, $recordType, $content = NULL)
    {
        if ($content == NULL)
            if (in_array($recordType, array('A', 'AAAA'))) {
                $ips = $this->page->user->getIPs();
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

    private function getDomainForRecord($recordId)
    {
        $get = $this->page->db->query("SELECT domain_id FROM records WHERE id = ?", $recordId);
        if ($get && $row = $get->fetch())
            return $row['domain_id'];
        return FALSE;
    }

    private function testRecordType($name, $recordType, $recordId = -1)
    {
        if ($recordType == NULL) {
            $get = $this->page->db->query("SELECT type FROM records WHERE id = ?", $recordId);
            if ($get && $row = $get->fetch())
                $recordType = $row['type'];
        }
        $get = $this->page->db->query("SELECT id FROM records WHERE name = ? AND type = ?", $name, $recordType);
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
