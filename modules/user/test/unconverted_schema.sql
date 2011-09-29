# $Id: user_schema.php,v 919ddfae9b56 2009/06/19 15:13:12 jim $
# Old TRILT/OA schema
# James Fryer, 20 May 03, 25 June 09
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

# Database version table
CREATE TABLE DatabaseVersion
(
    value int unsigned NOT NULL
);

CREATE TABLE User
(
    id              int unsigned NOT NULL,
    priv_id         int unsigned NOT NULL,
    status_id       int unsigned NOT NULL,
    auth_id         int unsigned NOT NULL,
    login_name      text NOT NULL,    ### should not be 'text'???
    password        text NOT NULL,         
    email           text NOT NULL,        
    name            text NOT NULL,
    comment         text NOT NULL,
    data            blob default NULL,
    PRIMARY KEY (id)
);

## Admin user
#INSERT INTO User VALUES ('1', '4', '2', '1', 'admin', 
#                '48ab1243dfc4a8c7b971e0f45b70a9d8',  # 'Hedgehog'
#                '$CONF[admin_email]', 'System Administrator', '', '');

## Anon user
#INSERT INTO User VALUES ('2', '1', '2', '1', 'guest', 
#                'd41d8cd98f00b204e9800998ecf8427e', # Empty string
#                '$CONF[admin_email]', 'Guest User', '', '');

ALTER TABLE `User` ADD `institution_id` INT NOT NULL DEFAULT 0 ;
ALTER TABLE `User` ADD `offair_rep` INT NOT NULL DEFAULT 0;
ALTER TABLE `User` ADD `telephone_number` TEXT NOT NULL;
ALTER TABLE `User` ADD `offair_rep_notifications` INT NOT NULL DEFAULT 1;
ALTER TABLE `User` ADD `offair_rep_status_change_notifications` INT NOT NULL DEFAULT 0;

# Privilege -- what is user allowed to do?
CREATE TABLE Privilege
(
    id              int unsigned NOT NULL,
    name            varchar(255) NOT NULL,
    description     text NOT NULL,
    PRIMARY KEY (id)
);

# Status -- what is user's status on system?
CREATE TABLE Status
(
    id              int unsigned NOT NULL,
    name            varchar(255) NOT NULL,
    description     text NOT NULL,
    PRIMARY KEY (id)
);

# Auth Type, either Local or Athens
CREATE TABLE AuthType
(
    id              int unsigned NOT NULL,
    name            varchar(20) NOT NULL,
    PRIMARY KEY (id)
);

INSERT INTO DatabaseVersion VALUES ('2');

INSERT INTO Privilege VALUES ('1', 'Guest', 'Guest user');
INSERT INTO Privilege VALUES ('2', 'User', 'Normal user');
INSERT INTO Privilege VALUES ('3', 'Editor', 'Trilt Editor');
INSERT INTO Privilege VALUES ('4', 'Off-Air Admin', '');
INSERT INTO Privilege VALUES ('5', 'Admin', 'System Administrator');
INSERT INTO Status VALUES ('1', 'Pending', 'New account');
INSERT INTO Status VALUES ('2', 'Active', 'Active account');
INSERT INTO Status VALUES ('3', 'Closed', 'Inactive account');
INSERT INTO Status VALUES ('4', 'Banned', 'Banned account');
INSERT INTO AuthType VALUES ('1', 'Local');
INSERT INTO AuthType VALUES ('2', 'Athens');

CREATE TABLE `Media` (
`id` INT NOT NULL ,
`name` VARCHAR( 244 ) NOT NULL,
PRIMARY KEY ( `id` )
) ;

INSERT INTO `Media` ( `id` , `name` )VALUES ('1', 'VHS');
INSERT INTO `Media` ( `id` , `name` )VALUES ('2', 'CD (Windows Media)');
INSERT INTO `Media` ( `id` , `name` )VALUES ('3', 'CD (QuickTime)');
INSERT INTO `Media` ( `id` , `name` )VALUES ('4', 'DVD');
INSERT INTO `Media` ( `id` , `name` ) VALUES ('5', 'MP3 (Radio only)');                      

CREATE TABLE `OrderStatus` (
`id` INT NOT NULL ,
`name` VARCHAR( 244 ) NOT NULL,
PRIMARY KEY ( `id` )
) ;

INSERT INTO `OrderStatus` ( `id` , `name` )VALUES ('1', 'Requested');
INSERT INTO `OrderStatus` ( `id` , `name` )VALUES ('2', 'On Hold');
INSERT INTO `OrderStatus` ( `id` , `name` )VALUES ('3', 'Ordered');
INSERT INTO `OrderStatus` ( `id` , `name` )VALUES ('4', 'Accepted by BUFVC');
INSERT INTO `OrderStatus` ( `id` , `name` )VALUES ('5', 'Sent by BUFVC');
INSERT INTO `OrderStatus` ( `id` , `name` )VALUES ('6', 'Cancelled by BUFVC');
INSERT INTO `OrderStatus` ( `id` , `name` )VALUES ('7', 'Declined');

CREATE TABLE `Queue` (
`id` INT NOT NULL ,
`name` VARCHAR( 244 ) NOT NULL,
PRIMARY KEY ( `id` )
) ;

INSERT INTO `Queue` ( `id` , `name` )VALUES ('1', 'Local');
INSERT INTO `Queue` ( `id` , `name` )VALUES ('2', 'Archived');
INSERT INTO `Queue` ( `id` , `name` )VALUES ('3', 'Open University');

CREATE TABLE `RequestorType` (
`id` INT NOT NULL ,
`name` VARCHAR( 244 ) NOT NULL,
PRIMARY KEY ( `id` )
) ;

INSERT INTO `RequestorType` ( `id` , `name` )VALUES ('1', 'Teaching Staff');
INSERT INTO `RequestorType` ( `id` , `name` )VALUES ('2', 'Academic Related Staff');
INSERT INTO `RequestorType` ( `id` , `name` )VALUES ('3', 'Technical Staff');
INSERT INTO `RequestorType` ( `id` , `name` )VALUES ('4', 'Full-Time researcher');
INSERT INTO `RequestorType` ( `id` , `name` )VALUES ('5', 'Part-Time Researcher');
INSERT INTO `RequestorType` ( `id` , `name` )VALUES ('6', 'Postgraduate Student');
INSERT INTO `RequestorType` ( `id` , `name` )VALUES ('7', 'Undergraduate Student');

CREATE TABLE `InstitutionType` (
`id` INT NOT NULL ,
`name` VARCHAR( 244 ) NOT NULL,
PRIMARY KEY ( `id` )
) ;

INSERT INTO `InstitutionType` ( `id` , `name` )VALUES ('1', 'Standard');
INSERT INTO `InstitutionType` ( `id` , `name` )VALUES ('2', 'Premier');


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
`queue` INT DEFAULT '0' NOT NULL ,
`trilt_id` VARCHAR(22) DEFAULT NULL ,
`channel` VARCHAR( 244 ) NOT NULL ,
`title` TEXT NOT NULL ,
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

PRIMARY KEY ( `id` ) ,
INDEX ( `institution_id`) , 
INDEX ( `off_air_rep_id` ), 
INDEX ( `media` ), 
INDEX ( `status` )
) AUTO_INCREMENT=100000 ;
