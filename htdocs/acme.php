<?php
require_once(__DIR__ . '/inc/page.php');

$page = new Page();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.0 400 Bad Request");
    exit(0);
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!is_array($data) || !isset($data['action'], $data['name'], $data['password'])
    || !is_string($data['action']) || !is_string($data['name']) || !is_string($data['password'])) {
    header("HTTP/1.0 400 Bad Request");
    exit(0);
}

switch ($data['action']) {
    case 'set':
        if (!isset($data['token']) || !is_string($data['token'])) {
            header("HTTP/1.0 400 Bad Request");
            exit(0);
        }
        if ($page->domains->setAcmeChallenge($data['name'], $data['password'], $data['token']))
            exit(0);
        header("HTTP/1.0 403 Forbidden");
        exit(0);

    case 'clear':
        if ($page->domains->clearAcmeChallenge($data['name'], $data['password']))
            exit(0);
        header("HTTP/1.0 403 Forbidden");
        exit(0);

    default:
        header("HTTP/1.0 400 Bad Request");
        exit(0);
}
