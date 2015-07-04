<?php
// powerdns hat db backend
// tinydns
require_once(__DIR__ . "/inc/page.php");
$page = new Page();

if ($page->currentUser->getLevel() != 'admin')
	$page->redirectIndex();

$page->setTitle('DNS administration');

$page->addScript('js/dns.admin.js');

$page->renderHeader();

$page->renderTemplate('sites/admin.php', array(
	'ips' => $page->currentUser->getIPs()
));

$page->renderFooter();
