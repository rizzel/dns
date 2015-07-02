<?php

class DNSFeedsUsers extends DNSFeeds
{
	public function user_get()
	{
		$u = $this->page->user->getUserList();
		if (isset($u) && $u !== FALSE)
			$this->setResult($u);
	}

	public function user_getInfo()
	{
		$this->setResult($this->page->user->getCurrentUser());
	}

	public function user_add($name, $password, $level, $email) {
		if ($this->page->user->registerUser($name, $password, $level, $email)) {
			$this->setResult();
		}
	}

	public function user_update($id, $name, $password, $level)
	{
		if ($this->page->user->updateUser($id, $name, $password, $level))
		{
			$this->setResult();
		}
	}

	public function user_updatePassword($password)
	{
		if ($this->page->user->requestPasswordUpdate($password))
			$this->setResult();
	}

	public function user_updateEmail($email)
	{
		if ($this->page->user->requestEmailUpdate($email))
			$this->setResult();
	}

	public function user_verifyToken($token)
	{
		if ($this->page->email->verifyUpdate($this->page->user->getCurrentUser()->username, $token))
			$this->setResult();
	}

	public function user_delete($id)
	{
		if (!isset($id))
			return;
		if ($this->page->user->unregisterUser($id))
		{
			$this->setResult();
		}
	}

	public function user_login($user, $password)
	{
		if (!isset($user) || !isset($password))
			return;
		if ($this->page->user->login($user, $password))
		{
			$this->setResult($this->page->user->getCurrentUser());
		}
	}

	public function user_logout()
	{
		$this->page->user->logout();
		$this->setResult($this->page->user->getCurrentUser());
	}

	public function user_vergessenRequest($name, $email)
	{
		$this->page->user->vergessenRequest($name, $email);
		$this->setResult();
	}

	public function user_vergessenResponse($name, $token, $password)
	{
		if ($this->page->user->vergessenResponse($name, $token, $password))
			$this->setResult();
	}

	public function user_ip()
	{
		$ips = $this->page->user->getIPs();
		$this->setSpecialHeader('Content-Type: text/plain; charset: utf-8');
		$this->setResult($ips[0], 'ok', true);
	}

	public function user_myip()
	{
		$this->setResult($this->page->user->getIPs());
	}

	public function user_testmail()
	{
		//$this->page->email->sendTo("rizzle@underdog-projects.net", 'göüäßg', 'testmail');
	}
}
