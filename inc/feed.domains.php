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

	public function domains_deleteDomainRecord($rid)
	{
		if ($this->page->domains->deleteSpecialRecord($rid))
			$this->setResult();
	}

	public function domains_addDomainRecord($did, $rname, $rtype, $rcontent, $rttl)
	{
		if ($this->page->domains->insertSpecialRecord($did, $rname, $rtype, $rcontent, $rttl))
			$this->setResult();
	}

	public function domains_updateDomainRecord($rid, $rname, $rtype, $rcontent, $rttl)
	{
		if ($this->page->domains->updateSpecialRecord($rid, $rname, $rtype, $rcontent, $rttl))
			$this->setResult();
	}

	public function domains_updateSOA($id, $soa)
	{
		if ($this->page->domains->deleteSpecialRecords($id, 'SOA') &&
			$this->page->domains->insertSpecialRecord($id, NULL, 'SOA', $soa))
			$this->setResult();
	}

	public function domains_updateMX($id, $mx)
	{
		if ($this->page->domains->deleteSpecialRecords($id, 'MX') &&
			$this->page->domains->insertSpecialRecord($id, NULL, 'MX', $mx))
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

	public function domains_mail()
	{
		if (mail("dns@ggdns.de", "test", "huhu, test", implode("\n", array(
				'From: dns@ggdns.de',
				'Reply-To: dns@ggdns.de'
			))))
			$this->setResult();
	}
}
