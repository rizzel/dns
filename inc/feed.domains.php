<?php

class DNSFeedsDomains extends DNSFeeds
{
	public function domains_get()
	{
		$d = $this->page->domains->getDomains();
		if (isset($d) && $d !== FALSE)
			$this->setResult($d);
	}

	public function domains_add($name, $type, $soa, $mx)
	{
		if ($this->page->domains->addDomain($name, $type, $soa, $mx))
			$this->setResult();
	}

	public function domains_delete($id)
	{
		if ($this->page->domains->deleteDomain($id))
			$this->setResult();
	}

	public function domains_updateName($id, $name)
	{
		if ($this->page->domains->updateDomainName($id, $name))
			$this->setResult();
	}

	public function domains_updateSOA($id, $soa)
	{
		if ($this->page->domains->updateDomainSOA($id, $soa))
			$this->setResult();
	}

	public function domains_updateMX($id, $mx)
	{
		if ($this->page->domains->updateDomainMX($id, $mx))
			$this->setResult();
	}

	public function domains_minilist()
	{
		$d = $this->page->domains->getDomainsMini();
		if (isset($d) && $d !== FALSE)
			$this->setResult($d);
	}

	public function domains_addRecord($domain, $type, $name, $content, $password, $ttl)
	{
		if ($this->page->domains->addRecord($domain, $type, $name, $content, $password, $ttl))
			$this->setResult();
	}

	public function domains_myRecords()
	{
		$r = $this->page->domains->getMyRecords();
		if (isset($r) && $r !== false)
			$this->setResult($r);
	}

	public function domains_deleteRecord($recordID)
	{
		if ($this->page->domains->deleteRecord($recordID))
			$this->setResult();
	}

	public function domaisn_updateIP($recordid, $password, $ip)
	{
		if ($this->page->domains->recordUpdateIP($recordid, $password, $ip))
			$this->setResult();
	}

	public function domains_updateRecordName($recordid, $name)
	{
		if ($this->page->domains->updateRecord($recordid, 'name', $name))
			$this->setResult();
	}

	public function domains_updateRecordContent($recordid, $content)
	{
		if ($this->page->domains->updateRecord($recordid, 'content', $content))
			$this->setResult();
	}

	public function domains_updateRecordPassword($recordid, $password)
	{
		if ($this->page->domains->updateRecord($recordid, 'password', $password))
			$this->setResult();
	}

	public function domains_updateRecordTTL($recordid, $ttl)
	{
		if ($this->page->domains->updateRecord($recordid, 'ttl', $ttl))
			$this->setResult();
	}
}
