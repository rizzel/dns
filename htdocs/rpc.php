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
//		case '/rpc.php/js':
//			header("Content-Type: text/javascript; charset=utf-8");
//			$js = $page->settings->defaultScripts;
//			foreach ($js AS $j) {
//                readfile(__DIR__ . "/" . $j);
//                echo ";";
//            }
//			break;
//		case '/rpc.php/css':
//			header("Content-Type: text/css; charset=utf-8");
//			$css = $page->settings->defaultStyles;
//			foreach($css AS $c)
//				readfile(__DIR__ . "/" . $c);
//			break;
		case '/rpc.php/ip':
			if ($page->domains->recordUpdateIP($args))
				exit(0);
			else
				$page->call404();
			break;
		case '/rpc.php/ip4':
			if ($page->domains->recordUpdateIP4($args))
				exit(0);
			else
				$page->call404();
			break;
		case '/rpc.php/ip6':
			if ($page->domains->recordUpdateIP6($args))
				exit(0);
			else
				$page->call404();
			break;
		case '/rpc.php/inadyn4':
			if (count($args) > 1)
			{
				$swap = $args[1];
				$args[1] = $args[0];
				$args[0] = $swap;
			}
			if ($page->domains->recordUpdateIP4($args))
				exit(0);
            break;
		case '/rpc.php/inadyn6':
			if (count($args) > 1)
			{
				$swap = $args[1];
				$args[1] = $args[0];
				$args[0] = $swap;
			}
			if ($page->domains->recordUpdateIP6($args))
				exit(0);
            break;
		case '/rpc.php/myip':
			print implode("\n", $page->currentUser->getIPs());
			break;
		default:
			$page->call404();
			header("Content-Type: text/plain");
			echo "PI: [[" . $_SERVER['PATH_INFO'] . "]]\n";
			echo "QS: [[" . $_SERVER['QUERY_STRING'] . "]]\n";
			echo "POST: [["; print_r(array_keys($_POST)); echo "]]\n";
			echo "SERVER: [["; print_r($_SERVER); echo "]]\n";
			print_r($args);
			break;
	}
