<?php

class DNSDomains {
	private $page;

	function __construct($page)
	{
		$this->page = $page;
	}

	public function getDomains()
	{
		if ($this->page->user->getCurrentUser()->level != 'admin')
			return false;
		$get = $this->page->db->query(
			"SELECT * FROM domains d ORDER BY name"
		);
		$result = $get->fetchall();
		foreach($result AS &$r)
		{
			$get = $this->page->db->query(
				"SELECT id, name, type,
					IF(type = 'SOA', SUBSTRING_INDEX(content, ' ', 2), content) AS content, ttl FROM records
					WHERE domain_id = ? AND (type NOT IN ('A', 'AAAA', 'CNAME') OR user = '') ORDER BY name, type, content",
				$r['id']
			);
			$r['records'] = $get->fetchall();
		}
		return $result;
	}

	private function updateSOARecord($domainid)
	{
		$set = $this->page->db->query(
			"UPDATE records
				SET content = CONCAT(SUBSTRING_INDEX(content, ' ', 2), ' ', UNIX_TIMESTAMP())
				WHERE domain_id = ? AND type = 'SOA'",
			$domainid
		);
		return ($set->rowCount() > 0);
	}

	public function addDomain($name, $type, $soa)
	{
		if ($this->page->user->getCurrentUser()->level != 'admin')
			return false;
		$set = $this->page->db->query(
			"INSERT INTO domains (name, type) VALUES (?, ?)",
			array($name, $type)
		);
		$id = $this->page->db->handle->lastInsertId();
		$this->insertSpecialRecord($id, $name, 'SOA', $soa);
		return true;
	}

	public function deleteSpecialRecord($recordid)
	{
		if ($this->page->user->getCurrentUser()->level != 'admin')
			return false;
		$did = $this->getDomainForRecord($recordid);
		$del = $this->page->db->query(
			"DELETE FROM records WHERE id = ?", $recordid
		);
		if ($del->rowCount() > 0)
			return $this->updateSOARecord($did);
	}

	public function deleteSpecialRecords($domainid, $type)
	{
		if ($this->page->user->getCurrentUser()->level != 'admin')
			return false;
		$del = $this->page->db->query(
			"DELETE FROM records WHERE domain_id = ? AND type = ?",
			array($domainid, $type)
		);
		if ($del->rowCount() > 0)
			return $this->updateSOARecord($domainid);
	}

	public function insertSpecialRecord($domainid, $name, $type, $content, $ttl = 86400)
	{
		if ($this->page->user->getCurrentUser()->level != 'admin')
			return false;
		if ($content && strlen($content) > 0)
		{
			if ($name === NULL)
				$name = $this->getDomainName($domainid);
			$in = $this->page->db->query(
				sprintf("INSERT INTO records (domain_id, name, type, content, ttl, change_date)
				VALUES (?, ?, ?, %s, ?, UNIX_TIMESTAMP())",
				$type == 'SOA' ? 'CONCAT(?, UNIX_TIMESTAMP())' : '?'),
				array(
					$domainid,
					$name,
					$type,
					$content,
					$ttl
				)
			);
			if ($in->rowCount() > 0)
				$this->updateSOARecord($domainid);
		}
	}

	public function updateSpecialRecord($recordid, $name, $type, $content, $ttl)
	{
		if ($this->page->user->getCurrentUser()->level != 'admin')
			return false;
		$set = $this->page->db->query(
			"UPDATE records SET name = ?, type = ?, content = ?, ttl = ? WHERE id = ?",
			array($name, $type, $content, $ttl, $recordid)
		);
		if ($set->rowCount() > 0)
			$this->updateSOARecord($this->getDomainForRecord($recordid));
	}

	public function deleteDomain($id)
	{
		if ($this->page->user->getCurrentUser()->level != 'admin')
			return false;
		$this->page->db->query(
			"DELETE FROM domains WHERE id=?",
			array($id)
		);
		$this->page->db->query(
			"DELETE FROM records WHERE domain_id=?",
			array($id)
		);
		return true;
	}

	public function updateDomainName($id, $name)
	{
		if ($this->page->user->getCurrentUser()->level != 'admin' ||
			strlen($name) < 1 || strlen($name) > 255)
			return false;
		$set = $this->page->db->query(
			"UPDATE domains SET name = ? WHERE id = ?",
			array($name, $id)
		);
		if ($set->rowCount() > 0)
			return $this->updateSOARecord($id);
	}

	private function getDomainName($domainid)
	{
		$get = $this->page->db->query("SELECT name FROM domains WHERE id = ?", $id);
		if ($get && $row = $get->fetch())
			return $row['name'];
	}

	public function getDomainsMini()
	{
		if ($this->page->user->getCurrentUser()->level == 'nobody')
			return false;
		$get = $this->page->db->query(
			"SELECT id, name FROM domains ORDER BY name"
		);
		return $get->fetchall();
	}

	public function addRecord($domain, $type, $name, $content, $password, $ttl)
	{
		if ($this->page->user->getCurrentUser()->level == 'nobody')
			return false;
		if (!in_array($type, array('A', 'AAAA', 'CNAME')))
			return false;
		if (strlen($name) == 0 || strlen($content) == 0 || !preg_match('/^\d+$/', $ttl))
			return false;
		if (!$this->testRecordType($name, $type))
			return false;
		if (($name = $this->fixRecordName($domain, $name)) === false)
			return false;
		if (!$this->isValidDomainName($name))
			return false;
		$set = $this->page->db->query(
			"INSERT INTO records (domain_id, name, type, content, ttl, change_date, password, user) VALUES
			(?, ?, ?, ?, ?, UNIX_TIMESTAMP(), ?, ?)",
			array(
				$domain, $name, $type, $content, $ttl, $password,
				$this->page->user->getCurrentUser()->username
			)
		);
		if ($set->rowCount() > 0)
		{
			$this->page->email->sendToCurrent(
				"Neuer Record: $name",
				"Für Ihren Nutzer wurde ein neuer Record angelegt:
	Name:    $name
	Typ:     $type
	Content: $content"
			);
			return $this->updateSOARecord($domain);
		}
	}

	public function fixRecordName($domainid, $record)
	{
		$domN = $this->page->db->query("SELECT name FROM domains WHERE id = ?", $domainid);
		if (!$domN || !($dom = $domN->fetch()))
			return false;
		$dom = $dom['name'];
		if (!preg_match("/$dom\$/", $record))
			$record = "$record.$dom";
		preg_replace('/\.\.+/', '.', $record);
		return $record;
	}

	public function isFreeDomain($name, $type)
	{
		$dom = $this->page->db->query("SELECT COUNT(*) AS c FROM records WHERE name = ? AND type = ?",
									  array($name, $type)
		);
		if ($dom && $row = $dom->fetch())
			return ($row['c'] == 0);
	}

	public function isValidDomainName($name)
	{
		return (
			!preg_match('/^\./', $name) && // darf nicht mit einem punkt beginnen
			!preg_match('/\.$/', $name) && // darf nicht mit einem punkt aufhören
			!preg_match('/(^|\.)-/', $name) && // darf kein - am anfang einer subdomain haben
			!preg_match('/-(\.|$)/', $name) && // darf kein - am ende einer subdomain haben
			preg_match('/^[0-9a-zA-Z.-]+$/', $name)); // darf nur diese zeichen beinhalten
	}

	public function getMyRecords()
	{
		if ($this->page->user->getCurrentUser()->level == 'nobody')
			return false;
		$get = $this->page->db->query(
			"SELECT
				d.id AS domain_id,
				d.name AS domain_name,
				r.id AS id,
				r.name AS name,
				r.type AS type,
				r.content AS content,
				r.ttl AS ttl,
				r.prio AS prio,
				r.change_date AS change_date,
				r.password AS password
			FROM records r
				INNER JOIN domains d
				ON r.domain_id = d.id
				WHERE r.user=? AND
				r.type IN ('A', 'AAAA', 'CNAME')
				ORDER BY domain_name, name, type, content",
			array(
				$this->page->user->getCurrentUser()->username
			)
		);
		return $get->fetchall();
	}

	public function deleteRecord($recordid)
	{
		$did = $this->getDomainForRecord($recordid);
		$get = $this->page->db->query("SELECT name FROM records WHERE id = ?", $recordid);
		$row = @$get->fetch();
		$set = $this->page->db->query(
			"DELETE FROM records
				WHERE id = ? AND
					(? = 'admin' OR user = ?)",
			array(
				$recordid,
				$this->page->user->getCurrentUser()->level,
				$this->page->user->getCurrentUser()->username
			)
		);
		if ($set->rowCount() > 0)
		{
			$this->page->email->sendToCurrent(
				"Record gelöscht: " . $row['name'],
				"Für Ihren Nutzer wurde ein Record gelöscht:
	Name: " . $row['name']
			);
			return $this->updateSOARecord($did);
		}
	}

	public function updateRecord($recordid, $key, $value)
	{
		if ($key == 'name' &&
			!$this->testRecordType($value, null, $recordid))
			return false;
		$get = $this->page->db->query("SELECT name FROM records WHERE id = ?", $recordid);
		$row = @$get->fetch();
		$set = $this->page->db->query(
			"UPDATE records r
				INNER JOIN dns_users u
				ON r.user = u.username
				SET r.$key = ?, change_date = UNIX_TIMESTAMP()
				WHERE id = ? AND
					(? = 'admin' OR u.username = ?)",
			array(
				$value,
				$recordid,
				$this->page->user->getCurrentUser()->level,
				$this->page->user->getCurrentUser()->username
			)
		);
		if ($set->rowCount() > 0)
		{
			$this->page->email->sendToCurrent(
				"Record geändert: " . $row['name'],
				"Für Ihren Nutzer wurde ein Record geändert:
	Name:      " . $row['name'] . "
	Parameter: $key,
	Wert:      $value"
			);
			return $this->updateSOARecord($this->getDomainForRecord($recordid));
		}
	}

	/**
	 * @param array $args DomainID, Passwort, Content
	 */
	public function recordUpdateIP($args)
	{
		if (count($args) < 2 || count($args) > 3)
			return false;
		if (count($args) == 2)
			array_push($args, $this->page->user->getIPs()[0]);
		$check = $this->page->db->query(
			"UPDATE records
				SET content = ?, change_date = UNIX_TIMESTAMP()
				WHERE id = ? AND password = ? AND LENGTH(password) > 0",
			array($args[2], $args[0], $args[1])
		);
		if ($check->rowCount() > 0)
			return $this->updateSOARecord($this->getDomainForRecord($recordid));
	}

	/**
	 * @param array $args DomainName, Passwort, Content
	 */
	public function recordUpdateIP4($args)
	{
		return $this->recordUpdateIPx($args[0], $args[1], 'A', count($args) > 2 ? $args[2] : null);
	}

	/**
	 * @param array $args DomainName, Passwort, Content
	 */
	public function recordUpdateIP6($args)
	{
		return $this->recordUpdateIPx($args[0], $args[1], 'AAAA', count($args) > 2 ? $args[2] : null);
	}

	private function recordUpdateIPx($name, $passwort, $type, $content = null)
	{
		if ($content == null)
			$content = $this->page->user->getIPs()[0];
		$check = $this->page->db->query(
			"UPDATE records
				SET content = ?, change_date = UNIX_TIMESTAMP()
				WHERE name = ? AND password = ? AND LENGTH(password) > 0 AND type = ?",
			array($content, $name, $passwort, $type)
		);
		if ($check->rowCount() > 0)
		{
			$get = $this->page->db->query(
				"SELECT domain_id FROM records WHERE name = ? AND password = ? AND LENGTH(password) > 0 AND type = ?",
				array($name, $passwort, $type)
			);
			if ($get && $row = $get->fetch())
				return $this->updateSOARecord($row['domain_id']);
		}
	}

	private function getDomainForRecord($recordid)
	{
		$get = $this->page->db->query("SELECT domain_id FROM records WHERE id = ?", $recordid);
		if ($get && $row = $get->fetch())
			return $row['domain_id'];
	}

	private function testRecordType($name, $type, $recordid = -1)
	{
		if ($type == null)
		{
			$get = $this->page->db->query(
				"SELECT type FROM records WHERE id = ?",
				$recordid
			);
			if ($get && $row = $get->fetch())
				$type = $row['type'];
		}
		$get = $this->page->db->query(
			"SELECT id FROM records
				WHERE name = ? AND type = ?",
			array($name, $type)
		);
		if ($get)
		{
			$ok = true;
			while ($row = $get->fetch())
			{
				if ($row['id'] != $recordid)
					$ok = false;
			}
			return $ok;
		}
		return $false;
	}
}
