CREATE TABLE dns_users
(
    username  VARCHAR(45)                                     NOT NULL PRIMARY KEY,
    level     ENUM ('admin', 'user', 'nobody') DEFAULT 'user' NOT NULL,
    password  VARCHAR(255)                                    NOT NULL,
    salt      VARCHAR(12)                                     NOT NULL,
    sessionid VARCHAR(100),
    email     VARCHAR(100)                                    NOT NULL,
    locale    VARCHAR(10),
    UNIQUE email (email),
    UNIQUE username (username),
    INDEX sessionid (sessionid)
) DEFAULT CHARSET "utf8mb4";

CREATE TABLE dns_users_update
(
    username    VARCHAR(45)  NOT NULL,
    requesttime DATETIME     NOT NULL,
    token       VARCHAR(128) NOT NULL,
    `key`       VARCHAR(128) NOT NULL,
    value       LONGTEXT     NOT NULL,
    INDEX lookup (username, token),
    INDEX `time` (requesttime),
    FOREIGN KEY (username) REFERENCES dns_users(username) ON DELETE CASCADE
) DEFAULT CHARSET "utf8mb4";

CREATE TABLE dns_records_users
(
    records_id BIGINT       NOT NULL PRIMARY KEY,
    password   VARCHAR(128),
    user       VARCHAR(45) NOT NULL,
    INDEX user_idx(user),
    FOREIGN KEY (records_id) REFERENCES records(id) ON DELETE CASCADE,
    FOREIGN KEY (user) REFERENCES dns_users(username) ON DELETE CASCADE
) DEFAULT CHARSET "utf8mb4";

CREATE TABLE dns_records
(
    records_id  BIGINT   NOT NULL PRIMARY KEY,
    change_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (records_id) REFERENCES records(id) ON DELETE CASCADE
) DEFAULT CHARSET "utf8mb4";

CREATE TABLE IF NOT EXISTS dns_login_attempts
(
    ip           VARCHAR(45) NOT NULL,
    username     VARCHAR(45) NOT NULL,
    attempt_time DATETIME    NOT NULL,
    INDEX ip_time (ip, attempt_time),
    FOREIGN KEY (username) REFERENCES dns_users(username) ON DELETE CASCADE
) DEFAULT CHARSET "utf8mb4";

INSERT INTO dns_users
VALUES ('anonymous', 'nobody', '', '', NULL, '', 'en_US');
