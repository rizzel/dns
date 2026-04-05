<?PHP

class User
{
    /**
     * @var Page The base page instance
     */
    private Page $page;

    /**
     * @var string The name of the user.
     */
    public string $username {
        get {
            return $this->username;
        }
    }

    /**
     * @var string The level of the user.
     */
    public string $level {
        get {
            return $this->level;
        }
    }

    /**
     * @var string|null The name of the user or null for session-loading.
     */
    public ?string $email {
        get {
            return $this->email;
        }
    }

    public string $locale;
    public ?string $textDomainFolder;

    function __construct(Page $page, ?string $userToLoad)
    {
        $this->page = $page;
        $this->locale = $this->getCurrentLocale();
        $this->loadUser($userToLoad);
    }

    public function isAnonymous(): bool
    {
        return $this->username === 'anonymous';
    }

    private function loadUser(?string $userToLoad): void
    {
        if ($userToLoad === null)
            $this->startSession();
        else
            if (!$this->loadUserByName($userToLoad))
                if (!$this->loadUserByEmail($userToLoad))
                    $this->loadUserByName('anonymous');
    }

    private function loadUserByName(string $username): bool
    {
        return $this->loadUserBy('username', $username);
    }

    private function loadUserByEmail(string $userEmail): bool
    {
        return $this->loadUserBy('email', $userEmail);
    }

    private function loadUserBy(string $field, string $value): bool
    {
        $load = $this->page->db->query(
            "SELECT username, level, email, locale FROM dns_users WHERE $field = ?",
            $value
        );
        if ($row = $load->fetch()) {
            $this->username = $row['username'];
            $this->level = $row['level'];
            $this->email = $row['email'];
            $this->fixLocale($row['locale']);
            return true;
        }
        $this->username = $this->level = $this->email = null;
        return false;
    }

    private function fixLocale(mixed $locale): void
    {
        $this->locale = $locale;
        if (empty($this->locale))
            $this->locale = self::getCurrentLocale();
    }


    public function checkLogin(string $password): bool
    {
        $load = $this->page->db->query(
            'SELECT password, salt FROM dns_users WHERE username = ?',
            $this->username
        );
        if ($row = $load->fetch()) {
            if (!isset($row['password']) || strlen($row['password']) <= 1)
                return false;

            if (password_verify($password, $row['password'])) {
                return true;
            }

            if (Users::verifyLegacyPassword($password, $row['password'], $row['salt'])) {
                $hash = Users::createPassword($password);
                $this->page->db->query(
                    "UPDATE dns_users SET password = ?, salt = '' WHERE username = ?",
                    $hash,
                    $this->username
                );
                return true;
            }
        }
        return false;
    }


    public function isLoggedIn(): bool
    {
        return in_array($this->level, array('user', 'admin'));
    }

    public function getPrintableUser(bool $withRecords = false): array
    {
        $user = array(
            'username' => $this->username,
            'level' => $this->level,
            'email' => $this->email,
            'locale' => $this->locale
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

    public function startSession(): void
    {
        session_set_cookie_params([
            'path' => '/',
            'httponly' => true,
            'secure' => isset($_SERVER['HTTPS']),
            'samesite' => 'Lax'
        ]);
        session_start();
        if (empty($_SESSION['csrf_token']))
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        if (array_key_exists('username', $_SESSION) && isset($_SESSION['username'])) {
            $q = $this->page->db->query("
                SELECT username, email, level
                FROM dns_users
                WHERE sessionid=? AND username=?
            ",
                session_id(),
                $_SESSION['username']
            );
            if ($q->fetch()) {
                $this->loadUserByName($_SESSION['username']);
            } else {
                $this->loadUserByName('anonymous');
            }
        } else {
            $this->loadUserByName('anonymous');
        }

        setlocale(LC_MESSAGES, $this->locale);
        putenv("LANG=" . $this->locale);
        $absPath = bindtextdomain('php', __DIR__ . '/../locale');
        $this->textDomainFolder = str_replace($_SERVER['DOCUMENT_ROOT'], '', $absPath) . '/' . $this->locale . '/LC_MESSAGES';
        if (function_exists('bind_textdomain_codeset')) {
            bind_textdomain_codeset('php', 'UTF-8');
        }
        textdomain('php');
    }

    public function requestPasswordUpdate(string $password): bool
    {
        $hash = Users::createPassword($password);
        return $this->page->email->createUpdate(
            _('Confirm the change of password'),
            sprintf(
                _(
                    'Please confirm the requested change of your password for your account on
                    %1$s for the user %2$s'
                ) . "\n\n",
                $_SERVER['SERVER_NAME'],
                $this->page
            ),
            'password',
            $hash
        );
    }

    public function confirmPasswordUpdate(string $username, string $password, bool $alreadyHashed = false): bool
    {
        $hash = $alreadyHashed ? $password : Users::createPassword($password);
        $set = $this->page->db->query("
            UPDATE dns_users
            SET
              password = ?,
              salt = ''
            WHERE username = ?
        ",
            $hash,
            $username
        );
        return ($set->rowCount() > 0);
    }

    public function requestEmailUpdate(string $email): bool
    {
        return $this->page->email->createUpdate(
            _('Confirm the change of email'),
            sprintf(
                _(
                    'Please confirm the requested change of your email address for your account on
                     %1$s for the user %2$s'
                ) . "\n\n",
                $_SERVER['SERVER_NAME'],
                $this->page
            ),
            'email',
            $email
        );
    }

    public function confirmEmailUpdate(string $username, string $email): bool
    {
        $set = $this->page->db->query("UPDATE dns_users SET email = ? WHERE username = ?", $email, $username);
        if ($set->rowCount() > 0) {
            $this->page->email->sendTo(
                $email,
                pgettext("emailUpdate", "email updated"),
                sprintf(_("The email address has been successfully changed for user %s."), $username)
            );
            return true;
        }
        return false;
    }

    public function forgottenRequest(string $name, string $email): void
    {
        $user = $this->page->users->getUserByName($name);
        if ($user->email == $email) {
            $this->page->email->createUpdate(
                _("Password reset token"),
                _("Please use the following token to reset your password."),
                'vergessen',
                $email,
                $name
            );
        }
    }

    public function forgottenResponse(string $name, string $token, string $password): bool
    {
        if ($this->page->email->verifyUpdate($name, $token)) {
            $this->confirmPasswordUpdate($name, $password);
            return true;
        }
        return false;
    }

    public function update(string $key, mixed $value): bool
    {
        if ($this->isAnonymous())
            return false;

        if (!in_array($key, array('username', 'level', 'password', 'email')))
            return false;

        if (
            (
                $this->page == $this->username &&
                $this->page == 'user' &&
                in_array($key, array('username', 'password', 'email'))
            ) ||
            (
                $this->page == 'admin'
            )
        ) {
            $set = null;
            switch ($key) {
                case 'username':
                case 'level':
                case 'email':
                    $set = $this->page->db->query("
                        UPDATE dns_users
                        SET $key = ?
                        WHERE username = ?
                    ", $value, $this->username);
                    break;
                case 'password':
                    $hash = Users::createPassword($value);
                    $set = $this->page->db->query("
                        UPDATE dns_users
                        SET password = ?, salt = ''
                        WHERE username = ?
                    ", $hash, $this->username);
                    break;
            }

            return ($set && $set->rowCount() == 1);
        }
        return false;
    }

    public function login(string $username, string $password): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $check = $this->page->db->query(
            "SELECT COUNT(*) AS c FROM dns_login_attempts WHERE ip = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            $ip
        );
        $row = $check->fetch();
        if ($row && $row['c'] >= 10)
            return false;

        $u = $this->page->users->getUserByName($username);
        if ($u->checkLogin($password)) {
            $this->page->db->query("DELETE FROM dns_login_attempts WHERE ip = ?", $ip);
            $this->loadUserByName($username);
            session_regenerate_id(true);
            $this->page->db->query(
                "UPDATE dns_users SET sessionid = ? WHERE username = ?",
                session_id(),
                $u->username
            );
            $_SESSION['username'] = $u->username;
            return true;
        }

        $this->page->db->query(
            "INSERT INTO dns_login_attempts (ip, username, attempt_time) VALUES (?, ?, NOW())",
            $ip, $username
        );
        return false;
    }

    public function logout(): void
    {
        $this->page->db->query("UPDATE dns_users SET sessionid = NULL WHERE sessionid=?", session_id());
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        $_SESSION = array();
        session_destroy();
        $this->loadUserByName('anonymous');
    }

    public function getIPs(): array
    {
        $ret = array();
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP))
                    $ret[] = $ip;
            }
        }
        $ret[] = $_SERVER['REMOTE_ADDR'];
        return array_unique($ret);
    }

    public function getIPv4(): ?string
    {
        return array_find($this->getIPs(), fn($ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
    }

    public function getIPv6(): ?string
    {
        return array_find($this->getIPs(), fn($ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
    }

    public function setLocale(string $locale): bool
    {
        if ($this->isAnonymous())
            return false;
        $this->page->db->query(
            "UPDATE dns_users SET locale = ? WHERE username = ?",
            $locale,
            $this->username
        );
        return true;
    }

    public static function getAvailableLocales(): array
    {
        $ret = array();
        $basePath = __DIR__ . "/../locale";
        $d = opendir($basePath);
        while ($path = readdir($d))
            if (is_dir("$basePath/$path") && !preg_match('/\./', $path) && $path != 'templates')
                $ret[] = $path;
        return $ret;
    }

    public static function getCurrentLocale(): string
    {
        $locales = User::getAvailableLocales();
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) {
                $langLower = strtolower($lang);
                list($langLower) = explode(';', $langLower);
                list($shortLangLower) = explode('-', $langLower);
                $langLower = str_replace('-', '_', $langLower);
                foreach ($locales as $locale) {
                    $localeLower = strtolower($locale);
                    list($shortLocaleLower) = explode('_', $localeLower);
                    if ($langLower == $localeLower || $shortLangLower == $shortLocaleLower) {
                        return $locale;
                    }
                }
            }
        }

        return 'en_US';
    }
}
