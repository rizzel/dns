#!/usr/bin/php
<?php
/**
 * Created by PhpStorm.
 * User: pzimmer
 * Date: 7/9/15
 * Time: 11:31 AM
 */

if (!file_exists(__DIR__ . '/htdocs/inc/settings.php')) {
    echo <<<END
First, please create the file htdocs/inc/settings.php
and set your database configuration. An example configuration
should be in htdocs/inc/settings.php.default. Afterwards
run this script again.
END;
    exit(1);
}

require_once(__DIR__ . '/htdocs/inc/page.php');
$page = new Page();



echo "Creating tables...";

$page->db->query('
CREATE TABLE IF NOT EXISTS dns_users (
  username VARCHAR(45) NOT NULL PRIMARY KEY,
  level ENUM(\'admin\', \'user\', \'nobody\') DEFAULT \'user\' NOT NULL,
  password VARCHAR(45) NOT NULL,
  salt VARCHAR(12) NOT NULL,
  sessionid VARCHAR(100),
  email VARCHAR(100) NOT NULL,
  locale VARCHAR(10),
  UNIQUE email (email)
) DEFAULT CHARSET "utf8"
');

$page->db->query('
CREATE TABLE IF NOT EXISTS dns_users_update (
  username VARCHAR(45) NOT NULL,
  requesttime DATETIME NOT NULL,
  token VARCHAR(128) NOT NULL,
  `key` VARCHAR(128) NOT NULL,
  value LONGTEXT NOT NULL,
  INDEX lookup (username, token),
  INDEX `time` (requesttime)
) DEFAULT CHARSET "utf8"
');

$page->db->query('
CREATE TABLE IF NOT EXISTS dns_records_users (
  records_id INT NOT NULL PRIMARY KEY,
  password VARCHAR(128),
  user VARCHAR(45) NOT NULL
) CHARSET "utf8"
');

$page->db->query('INSERT IGNORE INTO dns_users VALUES (\'anonymous\', \'nobody\', \'\', \'\', NULL, \'\', \'en_US\')');

echo "Done.\n";

if (preg_match('/[yj]/', readline("Create admin user? [y/N] "))) {
    echo "Create first admin user.\n";
    $username = readline("Username: ");
    $email = readline("Email: ");
    $password = readline("Password: ");
    $password2 = readline("Password again: ");

    if ($password != $password2)
        die("Password don't match!");

    $page->users->registerUser($username, $password, 'admin', $email);
}