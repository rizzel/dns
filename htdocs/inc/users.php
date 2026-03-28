<?PHP

require_once(__DIR__ . '/user.php');

class Users
{
    /**
     * @var Page The base page instance
     */
    private $page;

    function __construct($page)
    {
        $this->page = $page;
    }

    public function getUserByName($name = NULL)
    {
        return new User($this->page, $name);
    }

    public function getUserList()
    {
        if ($this->page->currentUser->getLevel() != 'admin')
            return NULL;
        $q = $this->page->db->query("SELECT username FROM dns_users ORDER BY username");
        $result = array();
        while ($r = $q->fetch()) {
            $u = $this->getUserByName($r['username']);
            if (!$u->isAnonymous())
                $result[] = $u->getPrintableUser(TRUE);
        }
        return $result;
    }

    public function registerUser($username, $password, $level, $email)
    {
        if ($this->page->currentUser->getLevel() == 'admin') {
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
            return ($q->errorCode() === '00000');
        }
        return FALSE;
    }

    public function unRegisterUser($userName)
    {
        $u = $this->getUserByName($userName);
        if ($u->isAnonymous())
            return FALSE;

        if (($this->page->currentUser->getUserName() == $userName && $this->page->currentUser->getLevel() == 'user') ||
            $this->page->currentUser->getLevel() == 'admin'
        ) {
            $this->page->db->query("DELETE FROM dns_users WHERE username=?", $userName);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Hash a password using bcrypt.
     *
     * @param string $password The password to hash.
     * @return string The bcrypt hash.
     */
    public static function createPassword($password)
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
    public static function verifyLegacyPassword($password, $hash, $salt)
    {
        return strlen($hash) > 1 && strlen($salt) > 0 && hash_equals($hash, sha1($password . $salt));
    }
}

