<?php
// powerdns hat db backend
// tinydns
require_once("inc/functions.php");
$page = new DNSPage();

$page->setTitle('DNS');

$page->addScript('js/dns.user.js');

$page->renderHeader();

$page->renderTemplate('sites/user.php', array());

$page->renderFooter();
