<?php

require_once(__DIR__ . '/feeds.php');

/**
 * Class FeedsUsers
 *
 * The user related RPC feeds.
 */
class FeedsUsers extends Feeds
{
    public function user_get(): void
    {
        $u = $this->page->users->getUserList();
        if (isset($u))
            $this->setResult($u);
    }

    public function user_getInfo(): void
    {
        $this->setResult($this->page->currentUser->getPrintableUser());
    }

    public function user_add(string $name, string $password, string $level, string $email): void
    {
        if ($this->page->users->registerUser($name, $password, $level, $email))
            $this->setResult();
    }

    public function user_updateName(string $username, string $name): void
    {
        $user = $this->page->users->getUserByName($username);
        if ($user->update('username', $name))
            $this->setResult();
    }

    public function user_updateLevel(string $username, string $level): void
    {
        $user = $this->page->users->getUserByName($username);
        if ($user->update('level', $level))
            $this->setResult();
    }

    public function user_updatePasswordSelf(string $password): void
    {
        if ($this->page->currentUser->requestPasswordUpdate($password))
            $this->setResult();
    }

    public function user_updateEmailSelf(string $email): void
    {
        if ($this->page->currentUser->requestEmailUpdate($email))
            $this->setResult();
    }

    public function user_verifyToken(string $token): void
    {
        if ($this->page->email->verifyUpdate($this->page->currentUser->username, $token))
            $this->setResult();
    }

    public function user_delete(string $userName): void
    {
        if ($this->page->users->unregisterUser($userName)) {
            $this->setResult($this->page->users->getUserList());
        }
    }

    public function user_login(string $user, string $password): void
    {
        if ($this->page->currentUser->login($user, $password)) {
            $this->setResult($this->page->currentUser->getPrintableUser());
        }
    }

    public function user_logout(): void
    {
        $this->page->currentUser->logout();
        $this->setResult($this->page->currentUser->getPrintableUser());
    }

    public function user_forgottenRequest(string $name, string $email): void
    {
        $this->page->currentUser->forgottenRequest($name, $email);
        $this->setResult();
    }

    public function user_forgottenResponse(string $name, string $token, string $password): void
    {
        if ($this->page->currentUser->forgottenResponse($name, $token, $password))
            $this->setResult();
    }

    public function user_ip(): void
    {
        $ips = $this->page->currentUser->getIPs();
        $this->setSpecialHeader('Content-Type: text/plain; charset: utf-8');
        $this->setResult($ips[0], 'ok', true);
    }

    public function user_myIP(): void
    {
        $this->setResult($this->page->currentUser->getIPs());
    }
}
