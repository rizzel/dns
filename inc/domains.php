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
			"SELECT d.*, r.content AS mx, LEFT(r2.content, LENGTH(r2.content) - 2) AS soa FROM domains d
				LEFT JOIN records r
				ON r.domain_id = d.id AND r.type = 'MX'
				LEFT JOIN records r2
				ON r2.domain_id = d.id AND r2.type = 'SOA'
				GROUP BY d.id"
		);
		return $get->fetchall();
	}

	public function addDomain($name, $type, $soa, $mx)
	{
		if ($this->page->user->getCurrentUser()->level != 'admin')
			return false;
		$set = $this->page->db->query(
			"INSERT INTO domains (name, type) VALUES (?, ?)",
			array($name, $type)
		);
		$id = $this->page->db->handle->lastInsertId();
		$this->insertSOARecord($id, $name, $soa);
		$this->insertMXRecord($id, $name, $mx);
		return true;
	}

	private function insertSOARecord($domainid, $name, $soa)
	{
		if ($soa && strlen($soa) > 0)
		{
			$this->page->db->query(
				"INSERT INTO records (domain_id, name, type, content, ttl, change_date, user)
				VALUES (?, ?, 'SOA', ?, 86400, UNIX_TIMESTAMP(), ?)",
				array(
					$domainid,
					$name,
					$soa . ' 0',
					$this->page->user->getCurrentUser()->username
				)
			);
		}
	}

	private function insertMXRecord($domainid, $name, $mx)
	{
		if ($mx && strlen($mx) > 0)
		{
			$this->page->db->query(
				"INSERT INTO records (domain_id, name, type, content, ttl, prio, change_date, user)
				VALUES (?, ?, 'MX', ? ,86400, 1, UNIX_TIMESTAMP(), ?)",
				array(
					$domainid,
					$name,
					$mx,
					$this->page->user->getCurrentUser()->username
				)
			);
		}
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
		$this->page->db->query(
			"UPDATE domains SET name = ? WHERE id = ?",
			array($name, $id)
		);
		return true;
	}

	public function updateDomainSOA($id, $soa)
	{
		if ($this->page->user->getCurrentUser()->level != 'admin')
			return false;
		$this->page->db->query(
			"DELETE FROM records WHERE domain_id = ? AND type = 'SOA'",
			array($id)
		);
		$get = $this->page->db->query(
			"SELECT name FROM domains WHERE id = ?",
			array($id)
		);
		if ($get)
		{
			$row = $get->fetch();
			$get->closeCursor();
		}
		if ($row)
			$this->insertSOARecord($id, $row['name'], $soa);
		return true;
	}

	public function updateDomainMX($id, $mx)
	{
		if ($this->page->user->getCurrentUser()->level != 'admin')
			return false;
		$this->page->db->query(
			"DELETE FROM records WHERE domain_id = ? AND type = 'MX'",
			array($id)
		);
		$get = $this->page->db->query(
			"SELECT name FROM domains WHERE id = ?",
			array($id)
		);
		if ($get)
		{
			$row = $get->fetch();
			$get->closeCursor();
		}
		if ($row)
			$this->insertMXRecord($id, $row['name'], $mx);
		return true;
	}

	public function getDomainsMini()
	{
		if ($this->page->user->getCurrentUser()->level == 'nobody')
			return false;
		$get = $this->page->db->query(
			"SELECT id, name FROM domains"
		);
		return $get->fetchall();
	}

	public function addRecord($domain, $type, $name, $content, $password, $ttl)
	{
		if ($this->page->user->getCurrentUser()->level == 'nobody')
			return false;
		$this->page->db->query(
			"INSERT INTO records (domain_id, name, type, content, ttl, change_date, password, user) VALUES
			(?, ?, ?, ?, ?, UNIX_TIMESTAMP(), ?, ?)",
			array(
				$domain, $name, $type, $content, $ttl, $password,
				$this->page->user->getCurrentUser()->username
			)
		);
		return TRUE;
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
				r.type IN ('A', 'AAAA', 'CNAME')",
			array(
				$this->page->user->getCurrentUser()->username
			)
		);
		return $get->fetchall();
	}

	public function deleteRecord($recordid)
	{
		$set = $this->page->db->query(
			"DELETE records FROM records r
				INNER JOIN dns_users u
				ON r.user = u.username
				WHERE id = ? AND
					(? = 'admin' OR u.username = ?)",
			array(
				$recordid,
				$this->page->user->getCurrentUser()->level,
				$this->page->user->getCurrentUser()->username
			)
		);
		return ($set->rowCount() > 0);
	}

	public function updateRecord($recordid, $key, $value)
	{
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
		return ($set->rowCount() > 0);
	}

	public function recordUpdateIP($recordid, $password, $content)
	{
		$check = $this->page->db->query(
			"UPDATE records
				SET content = ?, change_date = UNIX_TIMESTAMP()
				WHERE id = ? AND password = ?",
			array(
				$content, $recordid, $password
			)
		);
		return ($set->rowCount() > 0);
	}
}
