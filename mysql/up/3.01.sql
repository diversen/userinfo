CREATE TABLE `userinfo` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `birthday` varchar(255) DEFAULT NULL,
  `description` text,
  `user_id` int(10) NOT NULL,
  `screenname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userinfo_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8