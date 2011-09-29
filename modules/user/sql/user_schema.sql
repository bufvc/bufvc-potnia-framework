# $Id$
# Database schema for users
# Phil Hansen, 25 Sept 08
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

# User
CREATE TABLE User
(
    id          int unsigned NOT NULL AUTO_INCREMENT,
    login       VARCHAR(200) NOT NULL,
    email       VARCHAR(200) NOT NULL default '',
    name        VARCHAR(200) NOT NULL default '',
    root        TINYINT(1) unsigned NOT NULL default 0,
    PRIMARY KEY (id),
###    UNIQUE(login) ### Some entries in BUFVC user database have duplicate keys, so this is changed until that problem is resolved
    INDEX(login)
);

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
CREATE TABLE `UserData_seq` (
  `sequence` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`sequence`)
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

#
# List of available user rights

INSERT INTO `Rights` (`id`, `name`, `title`) VALUES
(1, 'save_data', 'Save data'),
(2, 'edit_record', 'Edit records'),
(3, 'play_audio', 'Play audio'),
(4, 'user_admin', 'User Admin');
