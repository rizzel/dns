<?php
// powerdns hat db backend
// tinydns
require_once("inc/functions.php");
$page = new DNSPage();

$page->setTitle('DNS');

$page->addScript('js/dns.main.js');

$page->renderHeader();

$page->renderTemplate('sites/main.php', array());

$page->renderFooter();
