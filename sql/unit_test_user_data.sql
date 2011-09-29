--
-- Dumping data for table `Users`
--

INSERT INTO `User` (`id`, `login`, `email`, `name`, `root`) VALUES
(1, 'guest', '', 'Guest User', 0),
(2, 'user', '', 'User', 0),
(3, 'editor', '', 'Editor', 0);

--
-- UserRight data
--

INSERT INTO `UserRight` (`user_id`, `right_id`) VALUES
(2, 1),
(3, 1),
(3, 2),
(3, 3),
# This keeps the listings test working -- editor has trilt_user right
(3, 7);

# Sequence tables to help Pear::DB_Table
CREATE TABLE `User_seq` (
  `sequence` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`sequence`)
);
INSERT INTO `User_seq` VALUES (3);
