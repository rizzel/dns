CREATE TABLE `comments` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `domain_id` int(11) NOT NULL,
                            `name` varchar(255) NOT NULL,
                            `type` varchar(10) NOT NULL,
                            `modified_at` int(11) NOT NULL,
                            `account` varchar(40) NOT NULL,
                            `comment` varchar(64000) NOT NULL,
                            PRIMARY KEY (`id`),
                            KEY `comments_domain_id_idx` (`domain_id`),
                            KEY `comments_name_type_idx` (`name`,`type`),
                            KEY `comments_order_idx` (`domain_id`,`modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `cryptokeys` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `domain_id` int(11) NOT NULL,
                              `flags` int(11) NOT NULL,
                              `active` tinyint(1) DEFAULT NULL,
                              `content` text DEFAULT NULL,
                              `published` tinyint(1) DEFAULT 1,
                              PRIMARY KEY (`id`),
                              KEY `domainidindex` (`domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `dns_records_users` (
                                     `records_id` int(11) NOT NULL,
                                     `password` varchar(128) DEFAULT NULL,
                                     `user` varchar(45) NOT NULL,
                                     PRIMARY KEY (`records_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `dns_users` (
                             `username` varchar(45) NOT NULL,
                             `level` enum('nobody','user','admin') NOT NULL DEFAULT 'user',
                             `password` varchar(45) NOT NULL,
                             `salt` varchar(20) NOT NULL,
                             `sessionid` varchar(100) DEFAULT NULL,
                             `email` varchar(100) NOT NULL,
                             `locale` varchar(10) DEFAULT NULL,
                             PRIMARY KEY (`username`),
                             UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `dns_users_update` (
                                    `username` varchar(45) NOT NULL,
                                    `requesttime` datetime /* mariadb-5.3 */ NOT NULL,
                                    `token` varchar(128) NOT NULL,
                                    `key` varchar(128) NOT NULL,
                                    `value` text NOT NULL,
                                    KEY `lookup` (`username`,`token`),
                                    KEY `time` (`requesttime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `domainmetadata` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `domain_id` int(11) NOT NULL,
                                  `kind` varchar(32) DEFAULT NULL,
                                  `content` text DEFAULT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `domainmetadata_idx` (`domain_id`,`kind`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `domains` (
                           `id` int(11) NOT NULL AUTO_INCREMENT,
                           `name` varchar(255) NOT NULL,
                           `master` varchar(128) DEFAULT NULL,
                           `last_check` int(11) DEFAULT NULL,
                           `type` varchar(6) NOT NULL,
                           `notified_serial` int(11) DEFAULT NULL,
                           `account` varchar(40) DEFAULT NULL,
                           `options` text DEFAULT NULL,
                           `catalog` varchar(255) DEFAULT NULL,
                           PRIMARY KEY (`id`),
                           UNIQUE KEY `name_index` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

CREATE TABLE `records` (
                           `id` int(11) NOT NULL AUTO_INCREMENT,
                           `domain_id` int(11) DEFAULT NULL,
                           `name` varchar(255) DEFAULT NULL,
                           `type` varchar(10) DEFAULT NULL,
                           `content` mediumtext DEFAULT NULL,
                           `ttl` int(11) DEFAULT NULL,
                           `prio` int(11) DEFAULT NULL,
                           `change_date` int(11) DEFAULT NULL,
                           `disabled` tinyint(1) DEFAULT 0,
                           `ordername` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
                           `auth` tinyint(1) DEFAULT 1,
                           PRIMARY KEY (`id`),
                           KEY `nametype_index` (`name`,`type`),
                           KEY `domain_id` (`domain_id`),
                           KEY `recordorder` (`domain_id`,`ordername`),
                           CONSTRAINT `records_ibfk_1` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=234 DEFAULT CHARSET=utf8;

CREATE TABLE `supermasters` (
                                `ip` varchar(64) NOT NULL,
                                `nameserver` varchar(255) NOT NULL,
                                `account` varchar(40) DEFAULT NULL,
                                PRIMARY KEY (`ip`,`nameserver`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tsigkeys` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) DEFAULT NULL,
                            `algorithm` varchar(50) DEFAULT NULL,
                            `secret` varchar(255) DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `namealgoindex` (`name`,`algorithm`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
