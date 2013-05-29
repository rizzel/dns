<?php

set_include_path(get_include_path() . PATH_SEPARATOR .
	dirname(realpath(__FILE__)));

function to_object($x) {
	return (is_object($x) || is_array($x)) ? json_decode(json_encode($x)) : (object)$x;
}

function fixNumber($number) {
	return $number > 0 ? ceil($number) : floor($number);
}

class DNSDB {
	private $page;
	public $handle;

	function __construct($page) {
		$this->page = $page;
		$config = $page->settings->db;
		$s = sprintf("mysql:host=%s;port=%d;dbname=%s", $config['dbHost'], $config['dbPort'], $config['dbName']);
		$this->handle = new PDO($s, $config['dbUser'], $config['dbPass']);
		$this->handle->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$this->handle->query('SET NAMES "utf-8"');
		$this->handle->query('SET CHARACTER SET "utf-8"');
	}

	public function esc($x) {
		return $this->handle->quote($x);
	}

	public function getPDOType($value) {
		if (is_int($value)) return PDO::PARAM_INT;
		if (is_bool($value)) return PDO::PARAM_BOOL;
		if (is_null($value)) return PDO::PARAM_NULL;
		return PDO::PARAM_STR;
	}

	public function query() {
		$args = func_get_args();
		$sql = array_shift($args);
		$q = $this->handle->prepare($sql);
		if (count($args) > 0) {
			if (is_array($args[0])) {
				foreach($args[0] as $key => $value) {
					if (is_int($key)) {
						$q->bindValue($key + 1, $value, $this->getPDOType($value));
					} else {
						$q->bindValue($key[0] == ':' ? $key : ':' . $key, $value, $this->getPDOType($value));
					}
				}
			} else {
				$k = 0;
				foreach($args as $value) {
					$q->bindValue(++$k, $value, $this->getPDOType($value));
				}
			}
		}
		$q->execute() || print("SQLERROR: " . $sql . print_r($args, true) . ' (' . print_r($q->errorInfo()) . ')');
		return $q;
	}

	public function getLastInsertId() {
		return $this->handle->lastInsertId();
	}
}

class DNSPage {
	private $page;
	private $title = '';
	private $metadata = array();
	private $styles = array();
	private $scripts = array();
	private $headhtml = array();
	private $bodyOnLoad = array();
	private $bodyOnUnload = array();

	public $db;
	public $settings;
	public $user;
	public $configuration;
	public $domains;
	public $email;
	public $feeds = array();
	public $queryParams;

	public $header;
	public $footer;
	public $t;

	function __construct() {
		require_once('settings.php');
		$this->settings = new DNSSettings($this);
		$this->db = new DNSDB($this);
		$this->configuration = new DNSConfiguration($this);
		$this->user = new DNSUser($this);
		$this->email = new DNSEmail($this);
		require_once('domains.php');
		$this->domains = new DNSDomains($this);

		require_once('feed.user.php');
		$this->feeds['user'] = new DNSFeedsUsers($this);

		require_once('feed.konfiguration.php');
		$this->feeds['konfiguration'] = new DNSFeedsKonfiguration($this);

		require_once('feed.domains.php');
		$this->feeds['domains'] = new DNSFeedsDomains($this);

		$this->scripts = $this->settings->defaultScripts;
		$this->styles = $this->settings->defaultStyles;
	}

	public function call404() {
		header("HTTP/1.0 404 Not Found");
		exit(0);
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function setMetaData($type, $value) {
		$this->metadata[$type] = $value;
	}

	public function addStyle($style) {
		array_push($this->styles, $style);
	}

	public function addScript($script) {
		array_push($this->scripts, $script);
	}

	public function addHeaderHTML($html) {
		array_push($this->headhtml, $html);
	}

	public function renderHeader($extras = array()) {
		header("Content-Type: text/html; charset=utf-8");
		$this->header = array_merge(array(
			'title' => $this->title,
			'metadata' => $this->metadata,
			'headhtml' => $this->headhtml,
			'scripts' => $this->getIncludeScripts(),
			'styles' => $this->getIncludeStyles(),
			'user' => get_object_vars($this->user->getCurrentUser())
		), $extras);
		include 'templates/header.php';
	}

	public function renderFooter($extras = array()) {
		$this->footer = array_merge(array(), $extras);
		include('templates/footer.php');
	}

	public function getIncludeScripts() {
		if ($this->user->getCurrentUser()->debug) {
			return $this->scripts;
		}
		return array("pingback/js");
	}

	public function getIncludeStyles() {
		if ($this->user->getCurrentUser()->debug) {
			return $this->styles;
		}
		return array("pingback/css");
	}

	public function renderTemplate($template, $vars) {
		$this->t = array_merge(array(
			'user' => get_object_vars($this->user->getCurrentUser())
		), $vars);
		include("templates/$template");
	}
}

class DNSEmail
{
	private $page;

	function __construct($page)
	{
		$this->page = $page;
	}

	public function send($to, $subject, $message, $additional_headers = null, $additional_parameters = null)
	{
		return mail($to, $subject, $message, $additional_headers, $additional_parameters);
	}
}

class DNSUser {
	private $page;
	private $user;
	private $userlevels;

	function __construct($page) {
		$this->page = $page;
		$this->startSession();
	}

	public function isLoggedIn()
	{
		return $this->getCurrentUser()->level != 'nobody';
	}

	private function getAnonymousUser() {
		return array(
			'level' => 'nobody',
			'username' => 'anonymous',
			'email' => ''
		);
	}

	public function getCurrentUser() {
		if (isset($this->user) && isset($this->user['username'])) {
			return $this->getCleanUser($this->user);
		}
		return $this->getCleanUser($this->getAnonymousUser());
	}

	private function getCleanUser($user) {
		$ret = (object)(array(
			'username' => $user['username'],
			'level' => $user['level'],
			//'debug' => (array_key_exists('debug', $_SESSION) ? $_SESSION['debug'] : false)
			'debug' => true,
			'email' => $user['email']
		));
		return $ret;
	}

	private function startSession() {
		session_start();
		if (array_key_exists('username', $_SESSION) && isset($_SESSION['username'])) {
			$q = $this->page->db->query(
				"SELECT * FROM dns_users u
					WHERE u.sessionid=? AND u.username=?",
				session_id(), $_SESSION['username']
			);
			$this->user = $q->fetch();
			$this->user = $this->user === FALSE ? NULL : $this->user;
		}
		if (array_key_exists('debug', $_GET)) $_SESSION['debug'] = $_GET['debug'];
	}

	public function getUserByName($name) {
		return $this->getCleanUser($this->_getUserByName($name));
	}

	private function _getUserByName($name) {
		$q = $this->page->db->query("SELECT * FROM dns_users WHERE username=?", $name);
		$f = $q->fetch();
		return $f === FALSE ? NULL : $f;
	}

	public function getUserList() {
		if ($this->getCurrentUser()->level != 'admin')
			return;
		$q = $this->page->db->query("SELECT * FROM dns_users u");
		$result = array();
		while ($r = $q->fetch()) {
			array_push($result, $this->getCleanUser($r));
		}
		foreach ($result AS &$user)
		{
			$q = $this->page->db->query(
				"SELECT r.name, r.type, d.name AS domain_name
					FROM records r
					INNER JOIN domains d
					ON r.domain_id = d.id
					WHERE user = ? AND
						r.type IN ('A', 'AAAA', 'CNAME')",
				array($user->username)
			);
			$user->records = $q->fetchall();
		}
		return $result;
	}

	public function registerUser($username, $password, $level, $email) {
		if ($this->getCurrentUser()->level == 'admin') {
			$salt = base_convert(rand(10e16, 10e20), 10, 36);
			$q = $this->page->db->query(
				"INSERT INTO dns_users
					(username, password, salt, level, email) VALUES
					(?, ?, ?, ?, ?)",
				$username, sha1($password . $salt), $salt, $level, $email
			);
			return TRUE;
		}
		return FALSE;
	}

	public function requestUpdate($name, $password, $level, $email)
	{
		$user = $this->getCurrentUser();
		if (!isset($name))
			$name = $user->username;
		if ($name == 'anonymous') return false;
		$u = $this->_getUserByName($name);
		if (!isset($u)) return false;
		if (($user->username == $name && $user->level != 'nobody') ||
			$user->level == 'admin')
		{
			$sql = "INSERT INTO dns_user_update VALUES (?, NOW(), ?, ?, ?)";
			$token = '';
			$possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			for ($i = 0; $i < 32; $i++)
			{
				$token .= $possible[mt_rand(1, length($possible)) - 1];
			}
			$change = array();
			if (isset($password))
				$change['password'] = $password;
			if (isset($level))
				$change['level'] = $level;
			if (isset($email))
				$change['email'] = $email;
			$this->page->db->query($sql, array($name, $token, json_encode($change), $user->username));

			$this->page->mail->send($user->email, 'Änderung von Parametern',
				"Bitte bestätigen Sie die Änderung folgender Parameter:\r\n\t- " .
				implode("\r\n\t- ", array_keys($change)) .
				"\r\nvia folgender URL:\r\n" .
				$_SERVER['SERVER_PROTOCOL'] . '://' . $_SERVER['SERVER_NAME'] . '/uu?' . $token,
				null,
				'-f dns@' . $_SERVER['SERVER_NAME']
			);
		}
	}

	public function updateUser($name, $username, $password, $level) {
		if (!isset($name))
			$name = $this->getCurrentUser()->username;
		if ($name == 'anonymous') return FALSE;
		$user = $this->getCurrentUser();
		$u = $this->_getUserByName($name);
		if (!isset($u)) return FALSE;
		if (($user->username == $name && $user->level != 'nobody') ||
			$user->level == 'admin') {
			$sql = "UPDATE dns_users SET ";
			$toUpdate = array();
			$toUpdateVal = array();
			$salt = base_convert(rand(10e16, 10e20), 10, 36);
			if (isset($username))
			{
				array_push($toUpdate, "username=?");
				array_push($toUpdateVal, $username);
			}
			if (isset($password)) {
				array_push($toUpdate, "password=?", "salt=?");
				$password = sha1($password . $salt);
				array_push($toUpdateVal, $password, $salt);
			}
			if (isset($level) && $user->level == 'admin')
			{
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

	public function unregisterUser($name) {
		if ($name == 'anonymous') return FALSE;
		$user = $this->getCurrentUser();
		if (($user->username == $name && $user->level != 'nobody') ||
			$user->level == 'admin') {
			$this->page->db->query("DELETE FROM dns_users WHERE username=?", $name);
			return TRUE;
		}
		return FALSE;
	}

	public function login($username, $password) {
		$u = $this->_getUserByName($username);
		if (
			isset($u) &&
			array_key_exists('salt', $u) &&
			array_key_exists('password', $u) &&
			sha1($password . $u['salt']) == $u['password']
		) {
			$q = $this->page->db->query(
				"UPDATE dns_users SET sessionid=? WHERE username=?",
				session_id(), $u['username']
			);
			$_SESSION['username'] = $u['username'];
			$this->user = $u;
			return TRUE;
		}
		return FALSE;
	}

	public function logout() {
		$q = $this->page->db->query("UPDATE dns_users SET sessionid = NULL WHERE sessionid=?",session_id());
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		}
		$_SESSION = array();
		session_destroy();
		$this->user = $this->getAnonymousUser();
	}

	public function getIPs()
	{
		$ret = array($_SERVER['REMOTE_ADDR']);
		if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER))
			array_push($ret, $_SERVER['HTTP_X_FORWARDED_FOR']);
		return $ret;
	}
}

class DNSConfiguration {
	private $page;

	function __construct($page) {
		$this->page = $page;
	}

	public function setConfig($name, $value) {
		$this->page->db->query(
			"INSERT INTO dns_config
				(name, value) VALUES
				(:name, :value1)
				ON DUPLICATE KEY UPDATE value=:value2",
			array("name" => $name, "value1" => $value, "value2" => $value)
		);
	}

	public function getConfig($name) {
		$get = $this->page->db->query(
			"SELECT value FROM dns_config WHERE name=:name",
			array("name" => $name)
		);
		$row = $get->fetch();
		if ($row !== FALSE && array_key_exists('value', $row)) {
			return $row['value'];
		} else {
			return NULL;
		}
	}

	public function updateConfig($oldname, $name, $value) {
		$q = $this->page->db->query(
			"UPDATE dns_config SET name=:name, value=:value WHERE name=:oldname",
			array(
				"name" => $name,
				"value" => $value,
				"oldname" => $oldname
			)
		);
		return ($q->rowCount() == 1);
	}

	public function getPublicConfig() {
		$get = $this->page->db->query(
			"SELECT * FROM dns_config
				WHERE name IN ('mangaupdatesManga', 'mangaupdatesPeople', 'mangaupdatesPublisher')
				ORDER BY name ASC"
		);
		$ret = array();
		while($r = $get->fetch()) {
			$ret[$r['name']] = $r['value'];
		}
		return $ret;
	}

	public function getAllConfig() {
		$get = $this->page->db->query("SELECT * FROM dns_config ORDER BY name ASC");
		$get->execute();
		return $get->fetchall();
	}
}

abstract class DNSFeeds {
	protected $page;
	protected $specialHeader;
	protected $result;
	protected $rawResult;

	function __construct($page) {
		$this->page = $page;
		$this->result = array('status' => 'error');
	}

	public function printResult() {
		if (isset($this->specialHeader)) {
			if (strlen($this->specialHeader) > 0) {
				header($this->specialHeader);
			}
		} else {
			header("Content-Type: application/json; charset=utf-8;");
		}
		if ($this->result) {
			if ($this->rawResult) {
				echo $this->result['data'];
			} else {
				$this->result['user'] = $this->page->user->getCurrentUser();
				echo json_encode($this->result);
			}
		}
	}

	public function setSpecialHeader($header = '') {
		$this->specialHeader = $header;
	}

	public function setResult($data = NULL, $status = 'ok', $raw = 0) {
		$this->result = array('data' => $data, 'status' => $status);
		$this->rawResult = $raw;
	}

	public function getParameterAsArray($parameter) {
		return (!is_array($parameter)) ? array($parameter) : $parameter;
	}

	public function checkGlobalHeaders() {
		if (array_search('listfilter', $this->page->queryParams) !== FALSE) {
			$this->page->user->updateListFilter(json_decode($this->page->queryParams['listfilter']));
		}
	}
}

?>