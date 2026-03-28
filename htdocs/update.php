<?php
require_once(__DIR__ . '/inc/page.php');

$page = new Page();

if (!isset($_REQUEST['password'])) {
    $page->call404();
}
$password = $_REQUEST['password'];
$content = isset($_REQUEST['content']) ? $_REQUEST['content'] : null;

if (isset($_REQUEST['recordid'])) {
    $page->domains->recordUpdateIP($_REQUEST['recordid'], $password, $content);
    exit(0);
} elseif (isset($_REQUEST['recordname']) && isset($_REQUEST['addrtype'])) {
    $recordType = $_REQUEST['addrtype'] == 'ipv6' ? 'AAAA' : 'A';
    $page->domains->recordUpdateByName($_REQUEST['recordname'], $password, $recordType, $content);
    exit(0);
}

$page->call404();
