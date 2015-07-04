<?php
require_once(__DIR__ . "/inc/page.php");
$page = new Page();

$page->setTitle('DNS - Passwort vergessen');

$page->addScript('js/dns.forgotten.js');

$page->renderHeader();

$page->renderTemplate('sites/vergessen.php', array());

$page->renderFooter();
