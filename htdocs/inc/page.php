<?php

if (!function_exists('pgettext')) {
    function pgettext($context, $msgId)
    {
        $contextString = "{$context}\004{$msgId}";
        $translation = dcgettext('messages', $contextString, LC_MESSAGES);
        if ($translation == $contextString)
            return $msgId;
        else
            return $translation;
    }
}


/**
 * Class Page
 *
 * The main class for this project.
 * Does everything.
 */
class Page {
    /**
     * @var string The HTML title.
     */
	private $title = '';
    /**
     * @var array HTML metadata tags.
     */
	private $metadata = array();
    /**
     * @var array Used css styles.
     */
	private $styles = array();
    /**
     * @var array Used javascript.
     */
	private $scripts = array();

    /**
     * @var DB The DB instance.
     */
	public $db;
    /**
     * @var Settings The Settings instance.
     */
	public $settings;
    /**
     * @var User The User instance.
     */
	public $users;
    /**
     * @var Domains The Domains instance.
     */
	public $domains;
    /**
     * @var Email The email instance.
     */
	public $email;
    /**
     * @var array Instances of all feeds.
     */
	public $feeds = array();
	public $queryParams;

    /**
     * @var User The currently logged in user.
     */
    public $currentUser;

    /**
     * @var array The variables to render the header.
     */
	public $header;
    /**
     * @var array The variables to render the footer.
     */
	public $footer;
    /**
     * @var array Template variables.
     */
	public $t;

	function __construct() {
		require_once(__DIR__ . '/settings.php');
		$this->settings = new Settings();

        require_once(__DIR__ . '/db.php');
		$this->db = new DB($this);

        require_once(__DIR__ . '/users.php');
		$this->users = new Users($this);

        require_once(__DIR__ . '/user.php');
        $this->currentUser = $this->users->getUserByName();

        require_once(__DIR__ . '/email.php');
		$this->email = new Email($this);

		require_once(__DIR__ . '/domains.php');
		$this->domains = new Domains($this);

		require_once(__DIR__ . '/feed.user.php');
		$this->feeds['user'] = new FeedsUsers($this);

		require_once(__DIR__ . '/feed.domains.php');
		$this->feeds['domains'] = new FeedsDomains($this);

		$this->scripts = $this->settings->defaultScripts;
		$this->styles = $this->settings->defaultStyles;
	}

    /**
     * Return a 404 and exit.
     */
	public function call404() {
		header("HTTP/1.0 404 Not Found");
		exit(0);
	}

    /**
     * Redirect to / and exit.
     */
	public function redirectIndex() {
		header("HTTP/1.0 302 Back to home");
		header("Location: /");
		exit(0);
	}

    /**
     * Set the HTML title.
     *
     * @param $title The title.
     */
	public function setTitle($title) {
		$this->title = $title;
	}

    /**
     * Set a specific metadata.
     *
     * @param string $type The key of the metadata.
     * @param string $value The value of the metadata.
     */
	public function setMetaData($type, $value) {
		$this->metadata[$type] = $value;
	}

    /**
     * Add a specific CSS style.
     *
     * @param string $style The CSS style.
     */
	public function addStyle($style) {
		array_push($this->styles, $style);
	}

    /**
     * Add a specific javascript file.
     *
     * @param string $script The javascript file.
     */
	public function addScript($script) {
		array_push($this->scripts, $script);
	}

    /**
     * Render the header.
     *
     * @param array $extras Optional extra values for rendering the header.
     */
	public function renderHeader($extras = array()) {
		header("Content-Type: text/html; charset=utf-8");
		$this->header = array_merge(array(
			'title' => $this->title,
			'metadata' => $this->metadata,
			'scripts' => $this->getIncludeScripts(),
			'styles' => $this->getIncludeStyles(),
			'user' => $this->currentUser->getPrintableUser()
		), $extras);
		include(__DIR__ . '/../templates/header.php');
	}

    /**
     * Render the footer.
     *
     * @param array $extras Optional extra values for rendering the footer.
     */
	public function renderFooter($extras = array()) {
		$this->footer = array_merge(array(), $extras);
		include(__DIR__ . '/../templates/footer.php');
	}

    /**
     * Returns a list of javascript-files to include.
     *
     * @return array The javascript to include.
     */
	public function getIncludeScripts() {
//		if ($this->currentUser->getDebug()) {
			return $this->scripts;
//		}
//		return array("/rpc.php/js");
	}

    /**
     * Returns a list of CSS style to include.
     *
     * @return array The CSS styles to include.
     */
	public function getIncludeStyles() {
//		if ($this->currentUser->getDebug()) {
			return $this->styles;
//		}
//		return array("/rpc.php/css");
	}

    /**
     * Render a specific template.
     *
     * @param array $template The template to render (relative to templates-folder).
     * @param array $vars Extra variables for rendering the template.
     */
	public function renderTemplate($template, $vars = array()) {
		$this->t = array_merge(array(
			'user' => $this->currentUser->getPrintableUser()
		), $vars);
		include(__DIR__ . "/../templates/$template");
	}

    public function toObject($x) {
        return (is_object($x) || is_array($x)) ? json_decode(json_encode($x)) : (object)$x;
    }
}
