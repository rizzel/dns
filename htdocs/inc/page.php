<?php

use JetBrains\PhpStorm\NoReturn;

if (!function_exists('pgettext')) {
    function pgettext($context, $msgId)
    {
        $contextString = "$context\004$msgId";
        $translation = dcgettext(textdomain(), $contextString, LC_MESSAGES);
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
	private string $title = '';
    /**
     * @var array HTML metadata tags.
     */
	private array $metadata = array();
    /**
     * @var array Used css styles.
     */
	private array $styles;
    /**
     * @var array Used javascript.
     */
	private array $scripts;

    /**
     * @var DB The DB instance.
     */
	public DB $db;
    /**
     * @var array The settings array.
     */
	public array $settings;
    /**
     * @var Users The User instance.
     */
	public Users $users;
    /**
     * @var Domains The Domains instance.
     */
	public Domains $domains;
    /**
     * @var Email The email instance.
     */
	public Email $email;
    /**
     * @var array Instances of all feeds.
     */
	public array $feeds = array();
	public mixed $queryParams;

    /**
     * @var User The currently logged in user.
     */
    public User $currentUser;

    /**
     * @var array The variables to render the header.
     */
	public array $header;
    /**
     * @var array The variables to render the footer.
     */
	public array $footer;
    /**
     * @var array Template variables.
     */
	public array $t;

	function __construct() {
		$this->settings = require(__DIR__ . '/settings.php');

		if ($this->settings['debug']) {
			error_reporting(E_ALL);
			ini_set('display_errors', '1');
			ini_set('display_startup_errors', '1');
		}

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

		$this->scripts = $this->settings['scripts'];
		$this->styles = $this->settings['styles'];

		header('X-Content-Type-Options: nosniff');
		header('X-Frame-Options: SAMEORIGIN');
		header('X-XSS-Protection: 1; mode=block');
		header('Referrer-Policy: strict-origin-when-cross-origin');
		if (isset($_SERVER['HTTPS'])) {
			header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
		}
	}

    /**
     * Return a 404 and exit.
     */
	#[NoReturn]
    public function call404(): void
    {
		header("HTTP/1.0 404 Not Found");
		exit(0);
	}

    /**
     * Redirect to / and exit.
     */
	#[NoReturn]
    public function redirectIndex(): void
    {
		header("HTTP/1.0 302 Back to home");
		header("Location: /");
		exit(0);
	}

    /**
     * Set the HTML title.
     *
     * @param string $title The title.
     */
	public function setTitle(string $title): void
    {
		$this->title = $title;
	}

    /**
     * Set a specific metadata.
     *
     * @param string $type The key of the metadata.
     * @param string $value The value of the metadata.
     */
	public function setMetaData(string $type, string $value): void
    {
		$this->metadata[$type] = $value;
	}

    /**
     * Add a specific CSS style.
     *
     * @param string $style The CSS style.
     */
	public function addStyle(string $style): void
    {
		$this->styles[] = $style;
	}

    /**
     * Add a specific javascript file.
     *
     * @param string $script The javascript file.
     */
	public function addScript(string $script): void {
		$this->scripts[] = $script;
	}

    /**
     * Render the header.
     *
     * @param array $extras Optional extra values for rendering the header.
     */
	public function renderHeader(array $extras = array()): void
    {
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
	public function renderFooter(array $extras = array()): void
    {
		$this->footer = array_merge(array(), $extras);
		include(__DIR__ . '/../templates/footer.php');
	}

    /**
     * Returns a list of javascript-files to include.
     *
     * @return array The javascript to include.
     */
	public function getIncludeScripts(): array
    {
		return $this->scripts;
	}

    /**
     * Returns a list of CSS style to include.
     *
     * @return array The CSS styles to include.
     */
	public function getIncludeStyles(): array
    {
		return $this->styles;
	}

    /**
     * Render a specific template.
     *
     * @param string $template The template to render (relative to templates-folder).
     * @param array $vars Extra variables for rendering the template.
     */
	public function renderTemplate(string $template, array $vars = array()): void
    {
		$this->t = array_merge(array(
			'user' => $this->currentUser->getPrintableUser()
		), $vars);
		include(__DIR__ . "/../templates/$template");
	}

    public function toObject($x): mixed
    {
        return (is_object($x) || is_array($x)) ? json_decode(json_encode($x)) : (object)$x;
    }
}
