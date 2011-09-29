# $Id$
# Convert old TRILT user database to user module
# James Fryer, 25 June 09
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

# You might need this:
# RENAME TABLE authtype TO AuthType;
# RENAME TABLE databaseversion TO DatabaseVersion;
# RENAME TABLE institution TO Institution;
# RENAME TABLE institutiontype TO InstitutionType;
# RENAME TABLE media TO Media;
# RENAME TABLE orders TO Orders;
# RENAME TABLE orderstatus TO OrderStatus;
# RENAME TABLE privilege TO Privilege;
# RENAME TABLE queue TO Queue;
# RENAME TABLE requestortype TO RequestorType;
# RENAME TABLE status TO Status;
# RENAME TABLE user TO User;
# RENAME TABLE user_seq TO User_Seq;

# Add new tables
# Rights
CREATE TABLE Rights
(
    id          int unsigned NOT NULL AUTO_INCREMENT,
    name        VARCHAR(50) NOT NULL,
    title       VARCHAR(200) NOT NULL default '',
    PRIMARY KEY (id),
    UNIQUE(name)
);

# User Rights
CREATE TABLE UserRight
(
    user_id     int unsigned NOT NULL,
    right_id    int unsigned NOT NULL,
    PRIMARY KEY (user_id, right_id)
);

# User Data
CREATE TABLE UserData
(
    id          int unsigned NOT NULL AUTO_INCREMENT,
    user_id     int unsigned NOT NULL,
    name        VARCHAR(200) NOT NULL,
    value       LONGTEXT NOT NULL default '',
    PRIMARY KEY (id)
);

# User events
CREATE TABLE UserEvent
(
    id          int unsigned NOT NULL AUTO_INCREMENT,
    user_id     int unsigned NOT NULL,
    date        DATETIME NOT NULL,
    event       VARCHAR(20) NOT NULL,
    PRIMARY KEY (id)
);

# List of available user rights
INSERT INTO `Rights` (`id`, `name`, `title`) VALUES
(1, 'save_data', 'Save data'),
(2, 'edit_record', 'Edit records'),
(3, 'play_audio', 'Play audio'),
(4, 'user_admin', 'User Admin'),
(5,'offair_rep','Offair Rep'),
(6,'offair_admin','Offair Admin'),
(7,'trilt_user','TRILT user');

# Convert the user table
ALTER TABLE 
    User
ADD 
    root tinyint(1) unsigned NOT NULL default '0' AFTER name
;
# Admin users become root users
UPDATE 
    User
SET
    root = 1
WHERE
    priv_id = 5
;    

# Editors
INSERT INTO 
    UserRight
SELECT 
    id, 2
FROM 
    User    
WHERE    
    User.priv_id IN (3,4)
;    

# OA admins
INSERT INTO 
    UserRight
SELECT 
    id, 6
FROM 
    User    
WHERE    
    User.priv_id = 4
;    

# OA reps
INSERT INTO 
    UserRight
SELECT 
    id, 5
FROM 
    User    
WHERE    
    User.offair_rep <> 0
;    

# OA notification flags
ALTER TABLE 
    User
CHANGE 
    offair_rep_notifications offair_notifications int(11) NOT NULL default '1'
;
UPDATE 
    User
SET
    offair_notifications = offair_notifications | 2
WHERE     
    offair_rep_status_change_notifications <> 0
;

# Other user data
ALTER TABLE 
    User
CHANGE 
    login_name login varchar(200) NOT NULL,
CHANGE 
    email email varchar(200) NOT NULL default '',
CHANGE 
    name name varchar(200) NOT NULL default '',
CHANGE 
    telephone_number telephone_number varchar(50) default NULL,
DROP
    priv_id,
DROP
    status_id,
DROP
    auth_id,
DROP
    password,
DROP
    comment,
### DROP
###    data, ### TEMP! Data needs to be converted
DROP
    offair_rep,
DROP
    offair_rep_status_change_notifications,
ADD 
###    UNIQUE(login)
    INDEX(login)
;    

# Add fields to Orders table
ALTER TABLE `Orders` 
ADD `broadcast_id` INT NOT NULL DEFAULT '0' AFTER `title` ,
ADD INDEX ( broadcast_id ) ,
ADD `pending_user` VARCHAR( 244 ) NOT NULL DEFAULT '' AFTER `date_declined` ,
ADD `fulfilled_user` VARCHAR( 244 ) NOT NULL DEFAULT '' AFTER `pending_user` ,
ADD `cancelled_user` VARCHAR( 244 ) NOT NULL DEFAULT '' AFTER `fulfilled_user`,
ADD `have_dvd` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' ;

# Remove tables we don't need
DROP TABLE AuthType;
DROP TABLE DatabaseVersion;
DROP TABLE Privilege;
DROP TABLE Status;

# Add seq tables if they don't exist
CREATE TABLE IF NOT EXISTS `UserData_seq` (
  `sequence` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`sequence`)
);
