<?php
require_once(__DIR__ . "/inc/page.php");
$page = new Page();

$page->setTitle(pgettext("PageTitle", "DNS - Update Response"));

$page->renderHeader();

$page->renderTemplate('sites/update.php', array());

$page->renderFooter();
