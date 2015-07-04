DROP TABLE IF EXISTS `hours`;
CREATE TABLE `hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `uploaded` int(10) unsigned NOT NULL,
  `downloaded` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hourly_user` (`date`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `raw_hours`;
CREATE TABLE `raw_hours` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(10) unsigned NOT NULL,
  `src_addr` varchar(39) NOT NULL,
  `dst_addr` varchar(39) NOT NULL,
  `bytes` int(10) unsigned NOT NULL,
  `packets` int(10) unsigned NOT NULL,
  `src_user_name` varchar(32) DEFAULT NULL,
  `dst_user_name` varchar(32) DEFAULT NULL,
  `router_hostname` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `cn` varchar(255) NOT NULL,
  `lastip` varchar(39) NOT NULL,
  `firstseen` int(10) unsigned NOT NULL,
  `lastseen` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
