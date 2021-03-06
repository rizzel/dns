CREATE TABLE dns_users (
  username VARCHAR(45) NOT NULL PRIMARY KEY,
  level ENUM('admin', 'user', 'nobody') DEFAULT 'user' NOT NULL,
  password VARCHAR(45) NOT NULL,
  salt VARCHAR(12) NOT NULL,
  sessionid VARCHAR(100),
  email VARCHAR(100) NOT NULL,
  locale VARCHAR(10),
  UNIQUE email (email)
) DEFAULT CHARSET "utf8";

CREATE TABLE dns_users_update (
  username VARCHAR(45) NOT NULL,
  requesttime DATETIME NOT NULL,
  token VARCHAR(128) NOT NULL,
  `key` VARCHAR(128) NOT NULL,
  value LONGTEXT NOT NULL,
  INDEX lookup (username, token),
  INDEX `time` (requesttime)
) DEFAULT CHARSET "utf8";

CREATE TABLE dns_records_users (
  records_id INT NOT NULL PRIMARY KEY,
  password VARCHAR(128),
  user VARCHAR(45) NOT NULL
) CHARSET "utf8";

INSERT INTO dns_users VALUES ('anonymous', 'nobody', '', '', NULL, '', 'en_US');
