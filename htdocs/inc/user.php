<?PHP

class User
{
    /**
     * @var Page The base page instance
     */
    private $page;

    /**
     * @var string The name of the user.
     */
    private $username;

    /**
     * @var string The level of the user.
     */
    private $level;

    /**
     * @var string|NULL The name of the user or NULL for session-loading.
     */
    private $email;

    function __construct($page, $userToLoad)
    {
        $this->page = $page;
        $this->loadUser($userToLoad);
    }

    public function getUserName()
    {
        return $this->username;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getDebug()
    {
        return TRUE;
        return array_key_exists('debug', $_SESSION) ? $_SESSION['debug'] : FALSE;
    }

    public function isAnonymous()
    {
        return $this->username == 'anonymous';
    }

    private function loadUser($userToLoad)
    {
        if ($userToLoad === NULL)
            $this->startSession();
        else
            if (!$this->loadUserByName($userToLoad))
                if (!$this->loadUserByEmail($userToLoad))
                    $this->loadUserByName('anonymous');
    }

    private function loadUserByName($username)
    {
        $load = $this->page->db->query('SELECT level, email FROM dns_users WHERE username = ?', $username);
        if ($load && $row = $load->fetch()) {
            $this->username = $username;
            $this->level = $row['level'];
            $this->email = $row['email'];
            return TRUE;
        }
        $this->username = $this->level = $this->email = NULL;
        return FALSE;
    }

    private function loadUserByEmail($useremail)
    {
        $load = $this->page->db->query('SELECT username, level FROM dns_users WHERE email = ?', $useremail);
        if ($load && $row = $load->fetch()) {
            $this->username = $row['username'];
            $this->level = $row['level'];
            $this->email = $row['email'];
            return TRUE;
        }
        $this->username = $this->level = $this->email = NULL;
        return FALSE;
    }


    public function checkLogin($password)
    {
        $load = $this->page->db->query('SELECT password, salt FROM dns_users WHERE username = ?', $this->username);
        if ($load && $row = $load->fetch()) {
            if (
                isset($row['password']) && isset($row['salt']) && strlen($row['password']) > 1 &&
                sha1($password . $row['salt']) == $row['password']
            ) {
                return TRUE;
            }
        }
        return FALSE;
    }


    public function isLoggedIn()
    {
        return in_array($this->level, array('user', 'admin'));
    }

    public function getPrintableUser($withRecords = FALSE)
    {
        $user = array(
            'username' => $this->username,
            'level' => $this->level,
            'email' => $this->email
        );

        if ($withRecords) {
            $q = $this->page->db->query("
                SELECT
                    r.name,
                    r.type,
                    d.name AS domain_name
                FROM records r
                LEFT JOIN domains d
                  ON r.domain_id = d.id
                LEFT JOIN dns_records_users dru
                  ON r.id = dru.records_id
                WHERE
                  dru.user = ? AND
                  r.type IN ('A', 'AAAA', 'CNAME')
            ",
                $this->username
            );
            $user['records'] = $q->fetchAll();
        }

        return $user;
    }

    public function startSession()
    {
        session_start();
        if (array_key_exists('username', $_SESSION) && isset($_SESSION['username'])) {
            $q = $this->page->db->query("
                SELECT username, email, level
                FROM dns_users
                WHERE sessionid=? AND username=?
            ",
                session_id(),
                $_SESSION['username']
            );
            if ($q && $row = $q->fetch()) {
                $this->loadUserByName($_SESSION['username']);
            } else {
                $this->loadUserByName('anonymous');
            }
        }

        if (array_key_exists('debug', $_GET))
            $_SESSION['debug'] = $_GET['debug'];
    }

    public function requestPasswordUpdate($password)
    {
        $get = $this->page->db->query(
            "SELECT salt FROM dns_users WHERE username = ?",
            $this->page->currentUser->getUserName()
        );
        if ($get && $row = $get->fetch()) {
            $p = Users::createPassword($password, $row['salt']);
            return $this->page->email->createUpdate(
                'Passwortänderung bestätigen',
                "Bitte bestätigen Sie die Änderung ihres Passwortes für ihren Account " .
                "ggdns.de für den User " . $this->page->currentUser->getUserName() . ".\n\n",
                'password',
                $p['hashed']
            );
        }
        return FALSE;
    }

    public function confirmPasswordUpdate($user, $password)
    {
        $set = $this->page->db->query("
            UPDATE dns_users
            SET
              password = ?
            WHERE username = ?
        ",
            $password,
            $user
        );
        return ($set->rowCount() > 0);
    }

    public function requestEmailUpdate($email)
    {
        return $this->page->email->createUpdate(
            'Emailänderung bestätigen',
            "Bitte bestätigen Sie die Änderung ihres Passwortes für ihren Account bei\n" .
            "ggdns.de für den User " . $this->page->currentUser->getUserName() . " mit folgendem Link:\n\n",
            'email',
            $email
        );
    }

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

    public function forgottenRequest($name, $email)
    {
        $user = $this->page->users->getUserByName($name);
        if ($user->getEmail() == $email) {
            $this->page->email->createUpdate(
                "Passwort Zurücksetzung Token",
                "Bitte Nutzen Sie folgendes Token zum Zurücksetzen des Passwortes.",
                'vergessen',
                $email,
                $name
            );
        }
    }

    public function forgottenResponse($name, $token, $password)
    {
        if ($this->page->email->verifyUpdate($name, $token)) {
            $this->confirmPasswordUpdate($name, $password);
            return TRUE;
        }
        return FALSE;
    }

    public function update($key, $value)
    {
        if ($this->isAnonymous())
            return FALSE;

        if (!in_array($key, array('username', 'level', 'password', 'email')))
            return FALSE;

        if (
            (
                $this->page->currentUser->getUserName() == $this->getUserName() &&
                $this->page->currentUser->getLevel() == 'user' &&
                in_array($key, array('username', 'password', 'email'))
            ) ||
            (
                $this->page->currentUser->getLevel() == 'admin'
            )
        ) {
            $set = NULL;
            switch ($key) {
                case 'username':
                case 'level':
                case 'email':
                    $set = $this->page->db->query("
                        UPDATE dns_users
                        SET $key = ?
                        WHERE username = ?
                    ", $value, $this->getUserName());
                    break;
                case 'password':
                    $password = Users::createPassword($value);
                    $set = $this->page->db->query("
                        UPDATE dns_users
                        SET password = ?, salt = ?
                        WHERE username = ?
                    ", $password['password'], $password['hash'], $this->getUserName());
                    break;
            }

            return ($set && $set->rowCount() == 1);
        }
        return FALSE;
    }

    public function login($username, $password)
    {
        $u = $this->page->users->getUserByName($username);
        if ($u->checkLogin($password)) {
            $this->loadUserByName($username);
            $this->page->db->query("UPDATE dns_users SET sessionid = ? WHERE username = ?", session_id(), $u->getUserName());
            $_SESSION['username'] = $u->getUserName();
            return TRUE;
        }
        return FALSE;
    }

    public function logout()
    {
        $this->page->db->query("UPDATE dns_users SET sessionid = NULL WHERE sessionid=?", session_id());
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        $_SESSION = array();
        session_destroy();
        $this->loadUserByName('anonymous');
    }

    public function getIPs()
    {
        $ret = array($_SERVER['REMOTE_ADDR']);
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER))
            array_unshift($ret, $_SERVER['HTTP_X_FORWARDED_FOR']);
        return $ret;
    }
}
