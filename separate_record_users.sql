CREATE TABLE dns_records_users (
  records_id INT NOT NULL PRIMARY KEY,
  password VARCHAR(128),
  user VARCHAR(45) NOT NULL
) CHARSET "utf8";

INSERT INTO dns_records_users SELECT id, password, user FROM records;

ALTER TABLE records DROP `password`, DROP `user`;

DROP TABLE IF EXISTS dns_config;
