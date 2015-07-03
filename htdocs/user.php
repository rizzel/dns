<?php
// powerdns hat db backend
// tinydns
require_once(__DIR__ . "/inc/page.php");
$page = new Page();

$page->setTitle('DNS');

$page->addScript('js/dns.user.js');

$page->renderHeader();

$page->renderTemplate('sites/user.php', array());

$page->renderFooter();
