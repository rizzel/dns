<?PHP

class User
{
    /**
     * @var Page The base page instance
     */
    private $page;

    /**
     * @var array The current user of this class.
     */
    private $user;

    function __construct($page)
    {
        $this->page = $page;
        $this->startSession();
    }

    /**
     * Returns whether this user is logged in (!nobody).
     *
     * @return bool Whether this user is logged in (!nobody).
     */
    public function isLoggedIn()
    {
        return $this->getCurrentUser()->level != 'nobody';
    }

    /**
     * Returns the anonymous user data.
     *
     * @return array The anonymous user.
     */
    private function getAnonymousUser()
    {
        return array(
            'level' => 'nobody',
            'username' => 'anonymous',
            'email' => ''
        );
    }

    /**
     * Returns the current user.
     *
     * @return object The current user.
     */
    public function getCurrentUser()
    {
        if (isset($this->user) && isset($this->user['username'])) {
            return $this->getCleanUser($this->user);
        }
        return $this->getCleanUser($this->getAnonymousUser());
    }

    /**
     * Cleans up a specific user and returns the object.
     *
     * @param array $user The user to clean.
     * @return object Returns a specific user.
     */
    private function getCleanUser($user)
    {
        $ret = (object)(array(
            'username' => $user['username'],
            'level' => $user['level'],
//'debug' => (array_key_exists('debug', $_SESSION) ? $_SESSION['debug'] : false)
            'debug' => TRUE,
            'email' => $user['email']
        ));
        return $ret;
    }

    /**
     * Starts the user session.
     */
    private function startSession()
    {
        session_start();
        if (array_key_exists('username', $_SESSION) && isset($_SESSION['username'])) {
            $q = $this->page->db->query("
                SELECT *
                FROM dns_users u
                WHERE u.sessionid=? AND u.username=?
            ",
                session_id(),
                $_SESSION['username']
            );
            $this->user = $q->fetch();
            $this->user = $this->user === FALSE ? NULL : $this->user;
        }

        if (array_key_exists('debug', $_GET))
            $_SESSION['debug'] = $_GET['debug'];
    }

    /**
     * Returns a user by his name.
     *
     * @param string $name The name of the user.
     * @return object The user.
     */
    public function getUserByName($name)
    {
        return $this->getCleanUser($this->_getUserByName($name));
    }

    /**
     * Returns a user by his name.
     *
     * @param string $name The name of the user.
     * @return mixed|null
     */
    private function _getUserByName($name)
    {
        $q = $this->page->db->query("SELECT * FROM dns_users WHERE username=?", $name);
        $f = $q->fetch();
        return $f === FALSE ? NULL : $f;
    }

    /**
     * Returns the list of all users.
     *
     * @return array|null The list of all user, or NULL on error.
     */
    public function getUserList()
    {
        if ($this->getCurrentUser()->level != 'admin')
            return NULL;
        $q = $this->page->db->query("SELECT * FROM dns_users u ORDER BY username");
        $result = array();
        while ($r = $q->fetch()) {
            array_push($result, $this->getCleanUser($r));
        }
        foreach ($result AS &$user) {
            $q = $this->page->db->query("
                SELECT
                  r.name,
                  r.type,
                  d.name AS domain_name
                FROM records r
                INNER JOIN domains d
                  ON r.domain_id = d.id
                LEFT JOIN dns_records_users dru
                  ON r.id = dru.records_id
                WHERE
                  dru.user = ? AND
                  r.type IN ('A', 'AAAA', 'CNAME')
            ",
                $user->username
            );
            $user->records = $q->fetchall();
        }
        return $result;
    }

    /**
     * Registers an user.
     *
     * @param string $username The username of the user.
     * @param string $password The password of the user.
     * @param string $level The level of the user.
     * @param string $email The email of the user.
     * @return bool Whether successful.
     */
    public function registerUser($username, $password, $level, $email)
    {
        if ($this->getCurrentUser()->level == 'admin') {
            $salt = base_convert(rand(10e16, 10e20), 10, 36);
            $q = $this->page->db->query("
                INSERT INTO dns_users
                  (username, password, salt, level, email)
                VALUES
                  (?, ?, ?, ?, ?)
            ",
                $username,
                sha1($password . $salt),
                $salt,
                $level,
                $email
            );
            return ($q->errorCode() === '00000');
        }
        return FALSE;
    }

    /**
     * Request an update for an user password.
     *
     * @param string $password The password for the user.
     * @return bool Whether successful.
     */
    public function requestPasswordUpdate($password)
    {
        $get = $this->page->db->query(
            "SELECT salt FROM dns_users WHERE username = ?",
            $this->getCurrentUser()->username
        );
        if ($get && $row = $get->fetch()) {
            $p = $this->createPassword($password, $row['salt']);
            return $this->page->email->createUpdate(
                'Passwortänderung bestätigen',
                "Bitte bestätigen Sie die Änderung ihres Passwortes für ihren Account " .
                "ggdns.de für den User " . $this->getCurrentUser()->username . ".\n\n",
                'password',
                $p->hashed
            );
        }
        return FALSE;
    }

    /**
     * Update a password for an user after confirming the token.
     *
     * @param string $user The username of the user.
     * @param string $password The new password for the user.
     * @return bool Whether successful.
     */
    public function confirmPasswordUpdate($user, $password)
    {
        $p = $this->createPassword($password);
        $set = $this->page->db->query("
            UPDATE dns_users
            SET
              password = ?,
              salt = ?
            WHERE username = ?
        ",
            $p->hashed,
            $p->salt,
            $user
        );
        return ($set->rowCount() > 0);
    }

    /**
     * Request an update for an user email.
     *
     * @param string $email The email to update.
     * @return bool Whether successful.
     */
    public function requestEmailUpdate($email)
    {
        return $this->page->email->createUpdate(
            'Emailänderung bestätigen',
            "Bitte bestätigen Sie die Änderung ihres Passwortes für ihren Account bei\n" .
            "ggdns.de für den User " . $this->getCurrentUser()->username . " mit folgendem Link:\n\n",
            'email',
            $email
        );
    }

    /**
     * Update an email for a user after confirming the token.
     *
     * @param string $user The username of the user.
     * @param string $email The new email for the user.
     * @return bool Whether successful.
     */
    public function confirmEmailUpdate($user, $email)
    {
        $set = $this->page->db->query("UPDATE dns_users SET email = ? WHERE username = ?", $email, $user);
        if ($set->rowCount() > 0) {
            $this->page->email->sendTo(
                $email,
                "Passwort gesetzt",
                "Das Passwort wurde erfolgreich geändert für den Benutzer $user."
            );
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Request a password reset token.
     *
     * @param string $name The name of the user.
     * @param string $email The email to send the token to.
     */
    public function vergessenRequest($name, $email)
    {
        $user = $this->page->user->getUserByName($name);
        if ($user->email == $email) {
            $this->page->email->createUpdate(
                "Passwort Zurücksetzung Token",
                "Bitte Nutzen Sie folgendes Token zum Zurücksetzen des Passwortes.",
                'vergessen',
                $email,
                $name
            );
        }
    }

    /**
     * Set a new password after the password reset token.
     *
     * @param string $name The name of the user.
     * @param string $token The token.
     * @param string $password The new password to set.
     * @return bool Whether successful.
     */
    public function vergessenResponse($name, $token, $password)
    {
        if ($this->page->email->verifyUpdate($name, $token)) {
            $this->confirmPasswordUpdate($name, $password);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Updates data for an user.
     *
     * @param string $name The original name of the user.
     * @param string $username The new name of the user.
     * @param string $password The password of the user.
     * @param string $level The level of the user.
     * @return bool Whether succesful.
     */
    public function updateUser($name, $username, $password, $level)
    {
        if (!isset($name))
            $name = $this->getCurrentUser()->username;
        if ($name == 'anonymous') return FALSE;
        $user = $this->getCurrentUser();
        if ($user->level != 'admin')
            return FALSE;
        $u = $this->_getUserByName($name);
        if (!isset($u)) return FALSE;
        if (($user->username == $name && $user->level != 'nobody') ||
            $user->level == 'admin'
        ) {
            $sql = "UPDATE dns_users SET ";
            $toUpdate = array();
            $toUpdateVal = array();
            if (isset($username)) {
                array_push($toUpdate, "username=?");
                array_push($toUpdateVal, $username);
            }
            if (isset($password)) {
                array_push($toUpdate, "password=?", "salt=?");
                $password = $this->createPassword($password);
                array_push($toUpdateVal, $password->hashed, $password->salt);
            }
            if (isset($level) && $user->level == 'admin') {
                array_push($toUpdate, "level=?");
                array_push($toUpdateVal, $level);
            }
            $sql .= implode(", ", $toUpdate);
            $sql .= " WHERE username=?";
            array_push($toUpdateVal, $name);
            $q = $this->page->db->query($sql, $toUpdateVal);
            return ($q->rowCount() > 0);
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
    private function createPassword($password, $salt = NULL)
    {
        if ($salt == NULL)
            $salt = base_convert(rand(10e16, 10e20), 10, 36);
        return $this->page->toObject(array(
            'hashed' => sha1($password . $salt),
            'salt' => $salt
        ));
    }

    /**
     * Remove a user (but keep his domains).
     *
     * @param string $name The name of the user.
     * @return bool Whether successful.
     */
    public function unRegisterUser($name)
    {
        if ($name == 'anonymous') return FALSE;
        $user = $this->getCurrentUser();
        if (($user->username == $name && $user->level != 'nobody') ||
            $user->level == 'admin'
        ) {
            $this->page->db->query("DELETE FROM dns_users WHERE username=?", $name);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Try to log in a user.
     *
     * @param string $username The username.
     * @param string $password The password of the user.
     * @return bool Whether successful.
     */
    public function login($username, $password)
    {
        $u = $this->_getUserByName($username);
        if (
            isset($u) &&
            array_key_exists('salt', $u) &&
            array_key_exists('password', $u) &&
            sha1($password . $u['salt']) == $u['password']
        ) {
            $this->page->db->query("UPDATE dns_users SET sessionid=? WHERE username=?", session_id(), $u['username']);
            $_SESSION['username'] = $u['username'];
            $this->user = $u;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Try to logout.
     */
    public function logout()
    {
        $this->page->db->query("UPDATE dns_users SET sessionid = NULL WHERE sessionid=?", session_id());
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        $_SESSION = array();
        session_destroy();
        $this->user = $this->getAnonymousUser();
    }

    /**
     * Returns a list of IPs the client connected from.
     *
     * @return array The list of IPs.
     */
    public function getIPs()
    {
        $ret = array($_SERVER['REMOTE_ADDR']);
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER))
            array_unshift($ret, $_SERVER['HTTP_X_FORWARDED_FOR']);
        return $ret;
    }
}

