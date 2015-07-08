<?php
require_once(__DIR__ . "/inc/page.php");
$page = new Page();

$page->setTitle(pgettext("PageTitle", "DNS - Forgot password"));

$page->addScript('js/dns.forgotten.js');

$page->renderHeader();

$page->renderTemplate('sites/vergessen.php', array());

$page->renderFooter();
