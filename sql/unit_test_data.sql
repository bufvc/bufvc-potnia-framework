# $Id$
# Fake data for unit tests
# James Fryer, 6 Jan 09
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

INSERT INTO `Test_Title` VALUES (1,'single','single','Test item', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many000','manymany 000','Test item 000', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many001','manymany 001','Test item 001', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many002','manymany 002','Test item 002', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many003','manymany 003','Test item 003', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many004','manymany 004','Test item 004', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many005','manymany 005','Test item 005', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many006','manymany 006','Test item 006', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many007','manymany 007','Test item 007', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many008','manymany 008','Test item 008', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many009','manymany 009','Test item 009', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many010','manymany 010','Test item 010', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many011','manymany 011','Test item 011', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many012','manymany 012','Test item 012', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many013','manymany 013','Test item 013', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many014','manymany 014','Test item 014', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many015','manymany 015','Test item 015', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many016','manymany 016','Test item 016', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many017','manymany 017','Test item 017', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many018','manymany 018','Test item 018', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many019','manymany 019','Test item 019', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many020','manymany 020','Test item 020', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many021','manymany 021','Test item 021', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many022','manymany 022','Test item 022', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many023','manymany 023','Test item 023', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','many024','manymany 024','Test item 024', 0, 0, 0);
INSERT INTO `Test_Title` VALUES ('','hidden','hidden','Hidden test item', 0, 1, 0);
INSERT INTO `Test_Title` VALUES ('','restricted','restricted','Restricted test item', 0, 0, 1);

# To keep PEAR::DB_Table quiet
CREATE TABLE `Test_Title_seq` (
  `sequence` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`sequence`)
);
INSERT INTO `Test_Title_seq` VALUES (100);

# Keyword test
INSERT INTO Test_Title SET 
    id = 50,
    token = 'kwtest',
    title = '### temp test for keywords ###'
    ;
INSERT INTO Test_Keyword (id, title) VALUES (1, 'Test 1'), (2, 'Test 2');
INSERT INTO Test_TitleKeyword (title_id, keyword_id) VALUES (50, 1), (50, 2);

# Test data for Trilt Listings
INSERT INTO `Test_Broadcast` (`id`, `date`, `end_date`, `bds_id`, `prog_id`) VALUES (1, '2009-06-12 08:00:00', '2009-06-12 08:59:00', '123', 1);
INSERT INTO `Test_Broadcast` (`id`, `date`, `end_date`, `bds_id`, `prog_id`) VALUES (2, '2009-06-12 09:45:00', '2009-06-12 10:59:00', '456', 2);
INSERT INTO `Test_Broadcast` (`id`, `date`, `end_date`, `bds_id`, `prog_id`) VALUES (3, '2009-06-12 23:00:00', '2009-06-13 00:59:00', '7', 2);
INSERT INTO `Test_Broadcast` (`id`, `date`, `end_date`, `bds_id`, `prog_id`) VALUES (4, '2000-06-12 08:00:00', '2000-06-12 08:59:00', '8', 2);
INSERT INTO `Test_Channel` (`id`, `name`) VALUES (1, 'Test'), (54, 'BBC1 London'),(68, 'BBC2 London'),(106, 'Channel 4'),(138, 'Five'),(175, 'ITV1 London');
INSERT INTO `Test_BroadcastChannel` (`bcast_id`, `channel_id`) VALUES (1, 54),(1, 68),(2, 54),(3,54),(4,54);
INSERT INTO Test_Programme (id, title, description, bds_id) VALUES (1, 'Test Programme', 'Prog description', '000B2D66');
INSERT INTO Test_Programme (id, title, description, bds_id, is_highlighted) VALUES (2, 'Test Programme2', 'Prog description', '000B2D67', 1);

# Dataset test
INSERT INTO Test_Dataset (id, name) VALUES (0, 'Unknown'), (1, 'Test');
# To keep PEAR::DB_Table quiet
CREATE TABLE `Test_Dataset_seq` (
  `sequence` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`sequence`)
);
INSERT INTO `Test_Dataset_seq` VALUES (100);
