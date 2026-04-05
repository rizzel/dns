<?php

require_once(__DIR__ . '/feeds.php');

/**
 * Class FeedsDomains
 *
 * The domain related RPC feeds.
 */
class FeedsDomains extends Feeds
{
	public function domains_get(): void
	{
		$d = $this->page->domains->getDomains();
		if (isset($d))
			$this->setResult($d);
	}

	public function domains_add(string $name, string $type, string $soa): void
	{
		if ($this->page->domains->addDomain($name, $type, $soa))
			$this->setResult();
	}

	public function domains_delete(string $id): void
	{
		if ($this->page->domains->deleteDomain(intval($id)))
			$this->setResult();
	}

	public function domains_updateName(string $id, string $name): void
	{
		if ($this->page->domains->updateDomainName(intval($id), $name))
			$this->setResult();
	}

	public function domains_deleteDomainRecord(string $rid): void
	{
		if ($this->page->domains->deleteSpecialRecord(intval($rid)))
			$this->setResult();
	}

	public function domains_addDomainRecord(string $did, string $rname, string $rtype, string $rcontent, string $rttl): void
	{
		if ($this->page->domains->insertSpecialRecord(intval($did), $rname, $rtype, $rcontent, intval($rttl)))
			$this->setResult();
	}

	public function domains_updateDomainRecord(string $rid, string $rname, string $rtype, string $rcontent, string $rttl): void
	{
		if ($this->page->domains->updateSpecialRecord($rid, $rname, $rtype, $rcontent, intval($rttl)))
			$this->setResult();
	}

	public function domains_updateSOA(string $id, string $soa): void
	{
		if ($this->page->domains->deleteSpecialRecords(intval($id), 'SOA') &&
			$this->page->domains->insertSpecialRecord(intval($id), null, 'SOA', $soa))
			$this->setResult();
	}

	public function domains_updateMX(string $id, string $mx): void
	{
		if ($this->page->domains->deleteSpecialRecords(intval($id), 'MX') &&
			$this->page->domains->insertSpecialRecord(intval($id), null, 'MX', $mx))
			$this->setResult();
	}

	public function domains_miniList(): void
	{
		$d = $this->page->domains->getDomainsMini();
		if (isset($d))
			$this->setResult($d);
	}

	public function domains_addRecord(string $domain, string $type, string $name, string $content, string $password, string $ttl): void
	{
		if ($this->page->domains->addRecord(intval($domain), $type, $name, $content, $password, intval($ttl)))
			$this->setResult();
	}

	public function domains_myRecords(): void
	{
		$r = $this->page->domains->getMyRecords();
		if (isset($r))
			$this->setResult($r);
	}

	public function domains_deleteRecord(string $recordID): void
	{
		if ($this->page->domains->deleteRecord(intval($recordID)))
			$this->setResult();
	}

	public function domains_updateRecordName(string $recordid, string $name): void
	{
		if ($this->page->domains->updateRecord(intval($recordid), 'name', $name))
			$this->setResult();
	}

	public function domains_updateRecordContent(string $recordid, string $content): void
	{
		if ($this->page->domains->updateRecord(intval($recordid), 'content', $content))
			$this->setResult();
	}

	public function domains_updateRecordPassword(string $recordid, string $password): void
	{
		if ($this->page->domains->updateRecord(intval($recordid), 'password', $password))
			$this->setResult();
	}

	public function domains_updateRecordTTL(string $recordid, string $ttl): void
	{
		if ($this->page->domains->updateRecord(intval($recordid), 'ttl', $ttl))
			$this->setResult();
	}

	public function domains_recordTest(string $domainid, string $record, string $type): void
	{
		if (strlen($record) == 0)
		{
			$this->setResult();
			return;
		}
		if (($record = $this->page->domains->fixRecordName(intval($domainid), $record)) === null)
			return;
		if (!$this->page->domains->isValidDomainName($record))
			$this->setResult(array('domain' => $record, 'type' => $type, 'status' => pgettext('domainRecord', 'Invalid name'), 'invalid' => true));
		elseif (!$this->page->domains->isFreeDomain($record, $type))
			$this->setResult(array('domain' => $record, 'type' => $type, 'status' => pgettext('domainRecord', 'Domain taken by someone else')));
		else
			$this->setResult(array('domain' => $record, 'type' => $type, 'status' => pgettext('domainRecord', 'Domain available'), 'free' => true));
	}
}