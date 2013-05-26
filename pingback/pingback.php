<?php
	include "../inc/functions.php";

	$page = new DNSPage();

	$args = array_map('htmlspecialchars', array_map('urldecode', preg_split('/[;&]/', $_SERVER['QUERY_STRING'])));

	$splitted = explode('/', $_SERVER['PATH_INFO']);

	if (array_key_exists('q', $_POST)) {
		$page->queryParams = json_decode($_POST['q']);
	}

	if (count($splitted) == 3) {
		array_shift($splitted);
		if (array_key_exists($splitted[0], $page->feeds)) {
			$class = $page->feeds[$splitted[0]];
			$methods = get_class_methods($class);
			$methods = array_filter($methods, function ($item) use ($splitted) {
				return strstr($item, $splitted[0] . "_");
			});
			$function = $splitted[0] . "_" . $splitted[1];
			if (!isset($page->queryParams)) {
				$page->queryParams = array();
			}
			if (array_search($function, $methods) !== FALSE) {
				call_user_func_array(array($class, $function), $page->queryParams);
				$class->printResult();
				exit(0);
			}
		}
	}

	switch($_SERVER['PATH_INFO']) {
		case '/js':
			header("Content-Type: text/javascript; charset=utf-8");
			$js = $page->settings->defaultScripts;
			foreach ($js AS $j) {
				include("../js/" . $j);
			}
			break;
		case '/css':
			header("Content-Type: text/css; charset=utf-8");
			$css = $page->settings->defaultStyles;
			foreach($css AS $c) {
				include("../css/" . $c);
			}
			break;
		default:
			header("Content-Type: text/plain");
			echo "PI: [[" . $_SERVER['PATH_INFO'] . "]]\n";
			echo "QS: [[" . $_SERVER['QUERY_STRING'] . "]]\n";
			echo "POST: [["; print_r(array_keys($_POST)); echo "]]\n";
			print_r($args);
			break;
	}
