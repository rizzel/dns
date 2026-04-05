<?PHP

require_once(__DIR__ . '/user.php');

class Users
{
    /**
     * @var Page The base page instance
     */
    private Page $page;

    function __construct(Page $page)
    {
        $this->page = $page;
    }

    public function getUserByName(?string $name = NULL): User
    {
        return new User($this->page, $name);
    }

    public function getUserList(): ?array
    {
        if ($this->page->currentUser->level != 'admin')
            return null;
        $q = $this->page->db->query("SELECT username FROM dns_users ORDER BY username");
        $result = array();
        while ($r = $q->fetch()) {
            $u = $this->getUserByName($r['username']);
            if (!$u->isAnonymous())
                $result[] = $u->getPrintableUser(true);
        }
        return $result;
    }

    public function registerUser(string $username, string $password, int $level, string $email): bool
    {
        if ($this->page->currentUser->level == 'admin') {
            $hash = $this::createPassword($password);

            $q = $this->page->db->query("
                INSERT INTO dns_users
                  (username, password, salt, level, email)
                VALUES
                  (?, ?, '', ?, ?)
            ",
                $username,
                $hash,
                $level,
                $email
            );
            return $q->rowCount() > 0;
        }
        return false;
    }

    public function unRegisterUser(string $userName): bool
    {
        $u = $this->getUserByName($userName);
        if ($u->isAnonymous())
            return false;

        if (($this->page->currentUser->username == $userName && $this->page->currentUser->level == 'user') ||
            $this->page->currentUser->level == 'admin'
        ) {
            $this->page->db->query("DELETE FROM dns_users WHERE username=?", $userName);
            return true;
        }
        return false;
    }

    /**
     * Hash a password using bcrypt.
     *
     * @param string $password The password to hash.
     * @return string The bcrypt hash.
     */
    public static function createPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a password against legacy SHA1+salt hash.
     *
     * @param string $password The plaintext password.
     * @param string $hash The stored SHA1 hash.
     * @param string $salt The stored salt.
     * @return bool Whether the password matches.
     */
    public static function verifyLegacyPassword(string $password, string $hash, string $salt): bool
    {
        return strlen($hash) > 1 && strlen($salt) > 0 && hash_equals($hash, sha1($password . $salt));
    }
}

