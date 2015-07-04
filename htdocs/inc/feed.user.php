<?php

require_once(__DIR__ . '/feeds.php');

/**
 * Class FeedsUsers
 *
 * The user related RPC feeds.
 */
class FeedsUsers extends Feeds
{
	public function user_get()
	{
		$u = $this->page->users->getUserList();
		if (isset($u) && $u !== FALSE)
			$this->setResult($u);
	}

	public function user_getInfo()
	{
		$this->setResult($this->page->currentUser->getPrintableUser());
	}

	public function user_add($name, $password, $level, $email) {
		if ($this->page->users->registerUser($name, $password, $level, $email))
			$this->setResult();
	}

	public function user_updateName($username, $name)
	{
        $user = $this->page->users->getUserByName($username);
		if ($user->update('name', $name))
			$this->setResult();
	}

    public function user_updateLevel($username, $level)
    {
        $user = $this->page->users->getUserByName($username);
        if ($user->update('level', $level))
            $this->setResult();
    }

	public function user_updatePasswordSelf($password)
	{
		if ($this->page->currentUser->requestPasswordUpdate($password))
			$this->setResult();
	}

	public function user_updateEmailSelf($email)
	{
		if ($this->page->currentUser->requestEmailUpdate($email))
			$this->setResult();
	}

	public function user_verifyToken($token)
	{
		if ($this->page->email->verifyUpdate($this->page->currentUser->getUserName(), $token))
			$this->setResult();
	}

	public function user_delete($userName)
	{
		if (!isset($userName))
			return;
		if ($this->page->users->unregisterUser($userName))
		{
			$this->setResult($this->page->users->getUserList());
		}
	}

	public function user_login($user, $password)
	{
		if (!isset($user) || !isset($password))
			return;
		if ($this->page->currentUser->login($user, $password))
		{
			$this->setResult($this->page->currentUser->getPrintableUser());
		}
	}

	public function user_logout()
	{
		$this->page->currentUser->logout();
		$this->setResult($this->page->currentUser->getPrintableUser());
	}

	public function user_forgottenRequest($name, $email)
	{
		$this->page->currentUser->forgottenRequest($name, $email);
		$this->setResult();
	}

	public function user_forgottenResponse($name, $token, $password)
	{
		if ($this->page->currentUser->forgottenResponse($name, $token, $password))
			$this->setResult();
	}

	public function user_ip()
	{
		$ips = $this->page->currentUser->getIPs();
		$this->setSpecialHeader('Content-Type: text/plain; charset: utf-8');
		$this->setResult($ips[0], 'ok', true);
	}

	public function user_myIP()
	{
		$this->setResult($this->page->currentUser->getIPs());
	}

	public function user_testmail()
	{
		//$this->page->email->sendTo("rizzle@underdog-projects.net", 'göüäßg', 'testmail');
	}
}
