<?php
require_once(__DIR__ . '/inc/page.php');

$page = new Page();

if (!isset($_REQUEST['password'])) {
    $page->call404();
}
$password = $_REQUEST['password'];
$content = isset($_REQUEST['content']) ? $_REQUEST['content'] : null;

if (isset($_REQUEST['recordid'])) {
    $page->domains->recordUpdateIP([$_REQUEST['recordid'], $password, $content]);
    exit(0);
} elseif ($_REQUEST['recordname'] && isset($_REQUEST['addrtype'])) {
    if ($_REQUEST['addrtype'] == 'ipv4') {
        $page->domains->recordUpdateIP4([$_REQUEST['recordname'], $password, $content]);
        exit(0);
    } elseif ($_REQUEST['addrtype'] == 'ipv6') {
        $page->domains->recordUpdateIP6([$_REQUEST['recordname'], $password, $content]);
        exit(0);
    }
}

$page->call404();
