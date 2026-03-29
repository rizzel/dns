<?php
	require_once(__DIR__ . '/inc/page.php');

	$page = new Page();

	$name = explode('?', $_SERVER['REQUEST_URI'])[0];
	$args = array_map('htmlspecialchars', array_map('urldecode', preg_split('/[;&]/', $_SERVER['QUERY_STRING'])));

	$split = array();
	if (array_key_exists('PATH_INFO', $_SERVER))
		$split = explode('/', $_SERVER['PATH_INFO']);

	if (array_key_exists('q', $_POST))
		$page->queryParams = json_decode($_POST['q']);

	if (count($split) == 3 && strlen($split[2]) > 0) {
		if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) ||
			!hash_equals($_SESSION['csrf_token'] ?? '', $_SERVER['HTTP_X_CSRF_TOKEN'])) {
			header("HTTP/1.0 403 Forbidden");
			exit(0);
		}
		array_shift($split);
		if (array_key_exists($split[0], $page->feeds)) {
			$class = $page->feeds[$split[0]];
			$methods = get_class_methods($class);
			$methods = array_filter($methods, function ($item) use ($split) {
				return strstr($item, $split[0] . "_");
			});

			$function = $split[0] . "_" . $split[1];
			if (!isset($page->queryParams))
				$page->queryParams = $args;

			if (array_search($function, $methods) !== FALSE) {
				call_user_func_array(array($class, $function), $page->queryParams);
				$class->printResult();
				exit(0);
			}
		}
	}

	switch($name) {
		case '/ip':
			if (count($args) >= 2 && $page->domains->recordUpdateIP($args[0], $args[1], isset($args[2]) ? $args[2] : NULL))
				exit(0);
			else
				$page->call404();
			break;
		case '/ip4':
			if (count($args) >= 2 && $page->domains->recordUpdateByName($args[0], $args[1], 'A', isset($args[2]) ? $args[2] : NULL))
				exit(0);
			else
				$page->call404();
			break;
		case '/ip6':
			if (count($args) >= 2 && $page->domains->recordUpdateByName($args[0], $args[1], 'AAAA', isset($args[2]) ? $args[2] : NULL))
				exit(0);
			else
				$page->call404();
			break;
		case '/inadyn4':
			if (count($args) >= 2 && $page->domains->recordUpdateByName($args[1], $args[0], 'A', isset($args[2]) ? $args[2] : NULL))
				exit(0);
            break;
		case '/inadyn6':
			if (count($args) >= 2 && $page->domains->recordUpdateByName($args[1], $args[0], 'AAAA', isset($args[2]) ? $args[2] : NULL))
				exit(0);
            break;
		case '/myip':
			print implode("\n", $page->currentUser->getIPs());
			break;
		default:
			error_log(sprintf("DNS 404: PATH_INFO=%s QS=%s",
				isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '',
				$_SERVER['QUERY_STRING']
			));
			$page->call404();
			break;
	}
