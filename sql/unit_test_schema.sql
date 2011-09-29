# $Id$
# Database schema for test datasource
# James Fryer, 21 Aug 08
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

# To prevent clashes with other databases all tables are prefixed with 'Test_'

## Database version table
#CREATE TABLE DatabaseVersion
#(
#    version int unsigned NOT NULL
#);
#INSERT INTO DatabaseVersion VALUES (1);

# -------------------------------------------------------------------
# Title
# Some titled object: publication, programme, etc.
CREATE TABLE Test_Title
(
    id              int unsigned NOT NULL AUTO_INCREMENT,
    token           VARCHAR(255) NOT NULL, # Public identifier
    title           TEXT NOT NULL,
    description     TEXT DEFAULT '',
    dataset_id      int unsigned DEFAULT 0,    # Test M:1 field
    hidden          TINYINT(1) unsigned NOT NULL DEFAULT 0, # flag for hidden record
    restricted      TINYINT(1) unsigned NOT NULL DEFAULT 0, # restricted record
    PRIMARY KEY (id),
    UNIQUE(token(100))
    # , FULLTEXT(title,description) # Commented out for test
);

# A file
CREATE TABLE Test_Media
(
    id          int unsigned NOT NULL AUTO_INCREMENT,
    url             VARCHAR(255) NOT NULL, ### temp -- should use ID???
    title       TEXT NOT NULL, # Descriptive title
    title_id    int unsigned DEFAULT NULL,    # FK back to segment
    location         VARCHAR(255) NOT NULL, # Can be relative or file://
    content_type VARCHAR(100) DEFAULT '',     # MIME type, should be sub-table
    size        int unsigned DEFAULT NULL,  # File size, NULL means 'unknown'
    PRIMARY KEY (id) ### ### no other indexes.... yet...
    ### INDEX(title_id)
);

# Person
CREATE TABLE Test_Person
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    url         VARCHAR(255) NOT NULL, ### temp
    name        VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    FULLTEXT (name)
    );

# Participation - links Title and Person
CREATE TABLE Test_Participation
    (
    title_id    int unsigned NOT NULL,
    person_id   int unsigned NOT NULL,
    ### role_id     int unsigned NOT NULL,
    ###date_created date DEFAULT NULL,
    PRIMARY KEY (title_id, person_id), ###, role_id),
    INDEX (person_id)
    );
    
# Dataset
CREATE TABLE Test_Dataset
    (
    id          int unsigned, ###AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)
    );

# Keyword
CREATE TABLE Test_Keyword
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    title       VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
    );

# Title keyword link table
CREATE TABLE Test_TitleKeyword
    (
    title_id    int unsigned NOT NULL,
    keyword_id  int unsigned NOT NULL,
    PRIMARY KEY (title_id, keyword_id),
    INDEX (keyword_id)
    );

#
## Trilt Listings test tables
#
# Broadcast
CREATE TABLE Test_Broadcast
    (
    `id` int(10) unsigned NOT NULL default '0',
    `date` datetime default NULL,
    `end_date` datetime default NULL,
    `bds_id` char(22) NOT NULL default '',
    `prog_id` int(10) unsigned NOT NULL default '0',
    PRIMARY KEY  (`id`),
    KEY `date` (`date`),
    KEY `end_date` (`end_date`),
    KEY `bds_id` (`bds_id`),
    KEY `prog_id` (`prog_id`)
    );

# Channel
CREATE TABLE Test_Channel
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)
    );

# Broadcast channel link table
CREATE TABLE Test_BroadcastChannel
    (
    `bcast_id` int(10) unsigned NOT NULL default '0',
    `channel_id` int(10) unsigned NOT NULL default '0',
    PRIMARY KEY  (`bcast_id`,`channel_id`),
    KEY `channel_id` (`channel_id`)
    );

# Programme
CREATE TABLE Test_Programme
    (
    `id` int(10) unsigned NOT NULL default '0',
    `title` varchar(255) NOT NULL default '',
    `description` text NOT NULL,
    `bds_id` varchar(22) default NULL,
    `is_highlighted` tinyint(1) unsigned NOT NULL default '0',
    PRIMARY KEY  (`id`),
    KEY `bds_id` (`bds_id`)
    );
