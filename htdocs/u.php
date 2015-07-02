<?php
require_once("inc/functions.php");
$page = new DNSPage();

$page->setTitle('DNS - Update Antwort');

$page->renderHeader();

$page->renderTemplate('sites/update.php', array());

$page->renderFooter();
