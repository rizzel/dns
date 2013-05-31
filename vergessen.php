<?php
require_once("inc/functions.php");
$page = new DNSPage();

$page->setTitle('DNS - Passwort vergessen');

$page->addScript('js/dns.vergessen.js');

$page->renderHeader();

$page->renderTemplate('sites/vergessen.php', array());

$page->renderFooter();
