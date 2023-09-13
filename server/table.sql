CREATE TABLE `entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `subject` varchar(255) NOT NULL,
  `repeats` varchar(63) NOT NULL,
  `calendar` varchar(63) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB 