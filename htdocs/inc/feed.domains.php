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

	public function domains_delete(int $id): void
	{
		if ($this->page->domains->deleteDomain($id))
			$this->setResult();
	}

	public function domains_updateName(int $id, string $name): void
	{
		if ($this->page->domains->updateDomainName($id, $name))
			$this->setResult();
	}

	public function domains_deleteDomainRecord(int $rid): void
	{
		if ($this->page->domains->deleteSpecialRecord($rid))
			$this->setResult();
	}

	public function domains_addDomainRecord(int $did, string $rname, string $rtype, string $rcontent, int $rttl): void
	{
		if ($this->page->domains->insertSpecialRecord($did, $rname, $rtype, $rcontent, $rttl))
			$this->setResult();
	}

	public function domains_updateDomainRecord(string $rid, string $rname, string $rtype, string $rcontent, int $rttl): void
	{
		if ($this->page->domains->updateSpecialRecord($rid, $rname, $rtype, $rcontent, $rttl))
			$this->setResult();
	}

	public function domains_updateSOA(int $id, string $soa): void
	{
		if ($this->page->domains->deleteSpecialRecords($id, 'SOA') &&
			$this->page->domains->insertSpecialRecord($id, null, 'SOA', $soa))
			$this->setResult();
	}

	public function domains_updateMX(int $id, string $mx): void
	{
		if ($this->page->domains->deleteSpecialRecords($id, 'MX') &&
			$this->page->domains->insertSpecialRecord($id, null, 'MX', $mx))
			$this->setResult();
	}

	public function domains_miniList(): void
	{
		$d = $this->page->domains->getDomainsMini();
		if (isset($d))
			$this->setResult($d);
	}

	public function domains_addRecord(int $domain, string $type, string $name, string $content, string $password, int $ttl): void
	{
		if ($this->page->domains->addRecord($domain, $type, $name, $content, $password, $ttl))
			$this->setResult();
	}

	public function domains_myRecords(): void
	{
		$r = $this->page->domains->getMyRecords();
		if (isset($r))
			$this->setResult($r);
	}

	public function domains_deleteRecord(int $recordID): void
	{
		if ($this->page->domains->deleteRecord($recordID))
			$this->setResult();
	}

	public function domains_updateRecordName(int $recordid, string $name): void
	{
		if ($this->page->domains->updateRecord($recordid, 'name', $name))
			$this->setResult();
	}

	public function domains_updateRecordContent(int $recordid, string $content): void
	{
		if ($this->page->domains->updateRecord($recordid, 'content', $content))
			$this->setResult();
	}

	public function domains_updateRecordPassword(int $recordid, string $password): void
	{
		if ($this->page->domains->updateRecord($recordid, 'password', $password))
			$this->setResult();
	}

	public function domains_updateRecordTTL(int $recordid, string $ttl): void
	{
		if ($this->page->domains->updateRecord($recordid, 'ttl', $ttl))
			$this->setResult();
	}

	public function domains_recordTest(int $domainid, string $record, string $type): void
	{
		if (strlen($record) == 0)
		{
			$this->setResult();
			return;
		}
		if (($record = $this->page->domains->fixRecordName($domainid, $record)) === null)
			return;
		if (!$this->page->domains->isValidDomainName($record))
			$this->setResult(array('domain' => $record, 'type' => $type, 'status' => pgettext('domainRecord', 'Invalid name'), 'invalid' => true));
		elseif (!$this->page->domains->isFreeDomain($record, $type))
			$this->setResult(array('domain' => $record, 'type' => $type, 'status' => pgettext('domainRecord', 'Domain taken by someone else')));
		else
			$this->setResult(array('domain' => $record, 'type' => $type, 'status' => pgettext('domainRecord', 'Domain available'), 'free' => true));
	}
}