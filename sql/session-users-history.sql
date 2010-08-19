CREATE TABLE `session` (
  `session_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `php_session_id` varchar(32) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_id` (`session_id`),
  UNIQUE KEY `php_session_id` (`php_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `session_params` (
  `session_param_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) NOT NULL,
  `param_name` varchar(255) NOT NULL,
  `param_value` varchar(255) NOT NULL,
  PRIMARY KEY (`session_param_id`),
  UNIQUE KEY `session_param_id` (`session_param_id`),
  UNIQUE KEY `session_param` (`session_id`,`param_name`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `session_params_session_id_fk` FOREIGN KEY (`session_id`) REFERENCES `session` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `history` (
  `history_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) NOT NULL,
  `page_controller` varchar(255) NOT NULL,
  `page_action` varchar(255) NOT NULL,
  `page_params` text NOT NULL,
  `page_title` varchar(255) NOT NULL,
  `page_type` tinyint(4) NOT NULL DEFAULT '0',
  `referer` varchar(255) DEFAULT NULL,
  `user_ip` varchar(15) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`history_id`),
  UNIQUE KEY `history_id` (`history_id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `history_session_id_fk` FOREIGN KEY (`session_id`) REFERENCES `session` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `user_groups` (
  `user_group_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `grant_access` tinyint(1) NOT NULL DEFAULT '0',
  `dash_access` tinyint(1) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`user_group_id`),
  UNIQUE KEY `user_group_id` (`user_group_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `user_groups` (`user_group_id`, `name`, `desc`, `grant_access`, `dash_access`, `date_created`, `date_modified`) VALUES
  (1, 'Admins', 'The Gods of dbdAdmin', 1, 1, now(), now());

CREATE TABLE `users` (
  `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_group_id` bigint(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `nick_name` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `user_group_id` (`user_group_id`),
  CONSTRAINT `users_fk` FOREIGN KEY (`user_group_id`) REFERENCES `user_groups` (`user_group_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `users` (`user_id`, `user_group_id`, `email`, `password`, `first_name`, `last_name`, `nick_name`, `photo`, `date_created`, `date_modified`) VALUES
  (1, 1, 'will@dontblinkdesign.com', 'b82547753be00b2271061514029f22d45400ee1e5e1c68efaead630dddf410d7fa34c84228c9528afb1eb107ea984496dda7d39325ea1a2e07b61839d9370e38', 'Will', 'Mason', 'dubhunter', NULL, now(), now());