-- Migration: Security improvements
-- - Widen password column for bcrypt hashes (60+ chars)
-- - Add login rate limiting table

ALTER TABLE dns_users MODIFY password VARCHAR(255) NOT NULL;

CREATE TABLE IF NOT EXISTS dns_login_attempts (
  ip VARCHAR(45) NOT NULL,
  username VARCHAR(45) NOT NULL,
  attempt_time DATETIME NOT NULL,
  INDEX ip_time (ip, attempt_time)
) DEFAULT CHARSET "utf8";
