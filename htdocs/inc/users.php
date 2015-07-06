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
            $password = $this::createPassword($password);

            $q = $this->page->db->query("
                INSERT INTO dns_users
                  (username, password, salt, level, email)
                VALUES
                  (?, ?, ?, ?, ?)
            ",
                $username,
                $password['hash'],
                $password['salt'],
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
     * Hash a password and return the hash and the salt.
     *
     * @param string $password The password to hash.
     * @param string|null $salt The salt to use (NULL to create it).
     * @return object The password object.
     */
    public static function createPassword($password, $salt = NULL)
    {
        if ($salt == NULL) {
            $salt = '';
            $possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            for ($i = 0; $i < 12; $i++)
                $salt .= $possible[mt_rand(1, strlen($possible)) - 1];
        }

        return array(
            'hashed' => sha1($password . $salt),
            'salt' => $salt
        );
    }
}

