# $Id$
# Additional schema data for BUFVC user database
# James Fryer, 25 June 09
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

# User has additional identifying data
ALTER TABLE `User` ADD `institution_id` INT NOT NULL DEFAULT 0;
ALTER TABLE User ADD telephone_number VARCHAR(50) DEFAULT NULL;
# The notifications is a bitfield: bit0=receive new item messages; bit1=receive status change messages
ALTER TABLE `User` ADD `offair_notifications` INT NOT NULL DEFAULT 1; 

# The OA flags/privileges are based on these rights
INSERT INTO `Rights` (`id`, `name`, `title`) VALUES
(5, 'offair_rep', 'Offair Rep'),
(6, 'offair_admin', 'Offair Admin'),
(7, 'trilt_user', 'TRILT user');

# Off-Air Ordering application
CREATE TABLE `Media` (
`id` INT NOT NULL ,
`name` VARCHAR( 244 ) NOT NULL,
PRIMARY KEY ( `id` )
);

INSERT INTO `Media` ( `id` , `name` ) VALUES ('1', 'VHS');
INSERT INTO `Media` ( `id` , `name` ) VALUES ('2', 'CD (Windows Media)');
INSERT INTO `Media` ( `id` , `name` ) VALUES ('3', 'CD (QuickTime)');
INSERT INTO `Media` ( `id` , `name` ) VALUES ('4', 'DVD');
INSERT INTO `Media` ( `id` , `name` ) VALUES ('5', 'MP3 (Radio only)');

CREATE TABLE `OrderStatus` (
`id` INT NOT NULL ,
`name` VARCHAR( 244 ) NOT NULL,
PRIMARY KEY ( `id` )
);

INSERT INTO `OrderStatus` ( `id` , `name` ) VALUES ('1', 'Requested');
INSERT INTO `OrderStatus` ( `id` , `name` ) VALUES ('2', 'On Hold');
INSERT INTO `OrderStatus` ( `id` , `name` ) VALUES ('3', 'Ordered');
INSERT INTO `OrderStatus` ( `id` , `name` ) VALUES ('4', 'Accepted by BUFVC');
INSERT INTO `OrderStatus` ( `id` , `name` ) VALUES ('5', 'Sent by BUFVC');
INSERT INTO `OrderStatus` ( `id` , `name` ) VALUES ('6', 'Cancelled by BUFVC');
INSERT INTO `OrderStatus` ( `id` , `name` ) VALUES ('7', 'Declined');

CREATE TABLE `Queue` (
`id` INT NOT NULL ,
`name` VARCHAR( 244 ) NOT NULL,
PRIMARY KEY ( `id` )
);

INSERT INTO `Queue` ( `id` , `name` ) VALUES ('1', 'Local');
INSERT INTO `Queue` ( `id` , `name` ) VALUES ('2', 'Archived');
INSERT INTO `Queue` ( `id` , `name` ) VALUES ('3', 'Open University');

CREATE TABLE `RequestorType` (
`id` INT NOT NULL ,
`name` VARCHAR( 244 ) NOT NULL,
PRIMARY KEY ( `id` )
);

INSERT INTO `RequestorType` ( `id` , `name` ) VALUES ('1', 'Teaching Staff');
INSERT INTO `RequestorType` ( `id` , `name` ) VALUES ('2', 'Academic Related Staff');
INSERT INTO `RequestorType` ( `id` , `name` ) VALUES ('3', 'Technical Staff');
INSERT INTO `RequestorType` ( `id` , `name` ) VALUES ('4', 'Full-Time researcher');
INSERT INTO `RequestorType` ( `id` , `name` ) VALUES ('5', 'Part-Time Researcher');
INSERT INTO `RequestorType` ( `id` , `name` ) VALUES ('6', 'Postgraduate Student');
INSERT INTO `RequestorType` ( `id` , `name` ) VALUES ('7', 'Undergraduate Student');

CREATE TABLE `InstitutionType` (
`id` INT NOT NULL ,
`name` VARCHAR( 244 ) NOT NULL,
PRIMARY KEY ( `id` )
) ;

INSERT INTO `InstitutionType` ( `id` , `name` ) VALUES ('1', 'Standard');
INSERT INTO `InstitutionType` ( `id` , `name` ) VALUES ('2', 'Premier');


CREATE TABLE `Institution` (
`id` INT NOT NULL  DEFAULT 0,
`name` TEXT NOT NULL DEFAULT '' ,
`quota` INT NOT NULL DEFAULT 0,
`remaining_quota` int NOT NULL DEFAULT '0',
`type` int(11) NOT NULL DEFAULT 0,
`email_appendix` TEXT NOT NULL DEFAULT '',
`address` TEXT NOT NULL DEFAULT '',
PRIMARY KEY ( `id` )
);

INSERT INTO `Institution` ( `id` , `name` , `quota`,`remaining_quota`,`type` ) VALUES ('1', 'Test Member Institution', 20,0,1);

CREATE TABLE `Orders` (
`id` INT NOT NULL AUTO_INCREMENT ,
`institution_id` INT DEFAULT '0' NOT NULL ,
`req_user`  VARCHAR( 244 ) NOT NULL default '' ,
`req_email` varchar(244) NOT NULL default '',
`req_type` int(11) NOT NULL default '0',
`off_air_rep_id` INT DEFAULT '0' NOT NULL ,
`media` INT DEFAULT '0' NOT NULL ,
`status` INT DEFAULT '0' NOT NULL ,
`date_requested` DATE DEFAULT '0000-00-00' NOT NULL ,
`date_ordered` DATE DEFAULT '0000-00-00' NOT NULL ,
`date_pending` DATE DEFAULT '0000-00-00' NOT NULL ,
`date_fulfilled` DATE DEFAULT '0000-00-00' NOT NULL ,
`date_cancelled` DATE DEFAULT '0000-00-00' NOT NULL ,
`date_declined` DATE DEFAULT '0000-00-00' NOT NULL ,
`pending_user` VARCHAR( 244 ) NOT NULL default '' ,
`fulfilled_user` VARCHAR( 244 ) NOT NULL default '' ,
`cancelled_user` VARCHAR( 244 ) NOT NULL default '' ,
`queue` INT DEFAULT '0' NOT NULL ,
`trilt_id` VARCHAR(22) DEFAULT NULL ,
`channel` VARCHAR( 244 ) NOT NULL ,
`title` TEXT NOT NULL ,
`broadcast_id` INT DEFAULT '0' NOT NULL ,
`broadcast_date` DATE DEFAULT '0000-00-00' NOT NULL ,
`broadcast_time` TIME DEFAULT '00:00:00' NOT NULL ,
`genre` VARCHAR( 244 ) NOT NULL ,
`requestor_notes` TEXT NOT NULL ,
`admin_notes` TEXT NOT NULL ,
`tape_id` VARCHAR( 244 ) NOT NULL ,
`course_details` TEXT,
`programme_description` TEXT,
`telephone_number` TEXT,
`ad_hoc` text,
`have_dvd` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0',
PRIMARY KEY ( `id` ) ,
INDEX ( `institution_id`) , 
INDEX ( `off_air_rep_id` ), 
INDEX ( `media` ), 
INDEX ( `status` ),
INDEX ( `broadcast_id` )
) AUTO_INCREMENT=100000;
