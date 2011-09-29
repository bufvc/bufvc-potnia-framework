# $Id$
# Database schema for Hermes
# Phil Hansen, 30 Mar 09
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

# Title
CREATE TABLE Title
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    
    # Titles
    title       VARCHAR(255) NOT NULL, # from title.title
    alt_title   VARCHAR(255) DEFAULT NULL, # from title.alternative
    title_series VARCHAR(255) DEFAULT NULL, # normalise later - from title_av.title_series
    subtitle    VARCHAR(255) DEFAULT NULL, # from title_av.title_sub
    
    # Dates
    date_created date DEFAULT NULL,
    date        date DEFAULT NULL,
    date_released CHAR(4) DEFAULT NULL, # consolidate from date1 and created1
    date_production CHAR(4) DEFAULT NULL, # from created1 or blank if same as date1
    
    # Title info
    description TEXT DEFAULT NULL,
    
    # Technical info
    is_colour   TINYINT(1) unsigned NOT NULL default 1, # flag for colour or black and white
    is_silent   TINYINT(1) unsigned NOT NULL default 0, # flag for silent or sound (silent includes silent+music)
    language_id VARCHAR(10) DEFAULT NULL,
    
    # Online info
    online_url VARCHAR(255) DEFAULT NULL, # from legacy_AV_titles.On_line_url
    online_price VARCHAR(255) DEFAULT NULL, # from legacy_AV_titles.On_line_price
    online_format_id int unsigned DEFAULT NULL, # created from legacy_AV_titles.On_line_format
    is_online   TINYINT(1) unsigned NOT NULL DEFAULT 0, # flag for online availability
    
    # Note fields
    notes       TEXT DEFAULT NULL,
    notes_documentation TEXT DEFAULT NULL, # from title_av.doc_notes
    notes_uses  TEXT DEFAULT NULL, # from title_av.uses
    
    # Additional reference info
    distributors_ref VARCHAR(255) DEFAULT NULL,
    isbn        VARCHAR(100) DEFAULT NULL,
    shelf_ref   VARCHAR(255) DEFAULT NULL,
    ref         VARCHAR(75) DEFAULT NULL,
    physical_description TEXT DEFAULT NULL,
    price       VARCHAR(255) DEFAULT NULL,
    availability VARCHAR(150) DEFAULT NULL,
    viewfinder  smallint unsigned DEFAULT 0, # parsed from title_av.ref
    is_shakespeare TINYINT(1) unsigned NOT NULL DEFAULT 0, # flag for shakespeare records
    
    hermes_id   VARCHAR(20) DEFAULT NULL,
    director    TEXT DEFAULT NULL, # included here for easy display on search results
    producer    TEXT DEFAULT NULL, # included here for easy display on search results
    format      VARCHAR(50) DEFAULT NULL, # included here for easy display on search results
    format_summary TINYINT(1) unsigned NOT NULL DEFAULT 0, # flag for moving_image (1) or audio (2) or both (3)
    subject     TEXT DEFAULT NULL, # included here for easy display on search results
    section_title VARCHAR(255) DEFAULT NULL, # included here for use in search indexes
    distribution TEXT DEFAULT NULL, # included here for easy display on search results
    misc        TEXT DEFAULT NULL, # Collected strings from other tables to avoid joins in all field search
    
    PRIMARY KEY (id),
    INDEX (date),
    INDEX (hermes_id),
    INDEX (language_id),
    INDEX (online_url),
    INDEX (online_format_id),
    INDEX (is_online),
    INDEX (is_shakespeare),
    INDEX (format),
    INDEX (format_summary),
    FULLTEXT (title,title_series,description,alt_title,subtitle,misc),
    FULLTEXT (title,title_series,alt_title,section_title),
    FULLTEXT (description),
    FULLTEXT (title_series)
    );

# Section (a sub-part of a title)
CREATE TABLE Section
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    title_id    int unsigned NOT NULL,
    
    # Title info
    title       VARCHAR(255) NOT NULL, # from title.title
    description TEXT DEFAULT NULL,
    notes       TEXT DEFAULT NULL,
    
    # Technical information
    duration    VARCHAR(50) DEFAULT NULL, # use seconds
    is_colour   TINYINT(1) unsigned NOT NULL default 1, # flag for colour or black and white
    is_silent   TINYINT(1) unsigned NOT NULL default 0, # flag for silent or sound (silent includes silent+music)
    
    # Additional reference info
    distributors_ref VARCHAR(255) DEFAULT NULL,
    isbn        VARCHAR(100) DEFAULT NULL,
    number_in_series int unsigned DEFAULT NULL,
    
    hermes_id   VARCHAR(20) DEFAULT NULL,
    
    PRIMARY KEY (id),
    INDEX (hermes_id),
    FULLTEXT (title,description),
    FULLTEXT (description)
    );
    
# Person
CREATE TABLE Person
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    date_created date DEFAULT NULL,
    name        VARCHAR(255) NOT NULL,
    notes       TEXT DEFAULT NULL,
    hermes_id   VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX (hermes_id),
    FULLTEXT (name)
    );

# Organisation
CREATE TABLE Organisation
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    name        VARCHAR(255) NOT NULL,
    date_created date DEFAULT NULL,
    notes       TEXT DEFAULT NULL,
    
    # Contact details
    contact_name VARCHAR(150) DEFAULT NULL,
    contact_position VARCHAR(150) DEFAULT NULL,
    email       VARCHAR(255) DEFAULT NULL,
    web_url     VARCHAR(255) DEFAULT NULL,
    telephone   VARCHAR(75) DEFAULT NULL,
    fax         VARCHAR(75) DEFAULT NULL,
    
    # Address
    address_1   VARCHAR(150) DEFAULT NULL,
    address_2   VARCHAR(150) DEFAULT NULL,
    address_3   VARCHAR(150) DEFAULT NULL,
    address_4   VARCHAR(150) DEFAULT NULL,
    town        VARCHAR(75) DEFAULT NULL,
    county      VARCHAR(75) DEFAULT NULL,
    postcode    VARCHAR(75) DEFAULT NULL,
    country     VARCHAR(75) DEFAULT NULL,
    
    hermes_id   VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX (hermes_id),
    FULLTEXT (name)
    );

# Participation - links Title and Person
CREATE TABLE Participation
    (
    title_id    int unsigned NOT NULL,
    person_id   int unsigned NOT NULL,
    role_id     int unsigned NOT NULL,
    date_created date DEFAULT NULL,
    PRIMARY KEY (title_id, person_id, role_id),
    INDEX (title_id,role_id),
    INDEX (person_id),
    INDEX (role_id)
    );

# Section Participation - links Section and Person
CREATE TABLE SectionParticipation
    (
    section_id    int unsigned NOT NULL,
    person_id   int unsigned NOT NULL,
    role_id     int unsigned NOT NULL,
    date_created date DEFAULT NULL,
    PRIMARY KEY (section_id, person_id, role_id),
    INDEX (section_id,role_id),
    INDEX (person_id),
    INDEX (role_id)
    );

# Role - a person's role in a title
CREATE TABLE Role
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    is_technical TINYINT(1) unsigned NOT NULL default 0,
    title       VARCHAR(100) DEFAULT NULL,
    PRIMARY KEY (id),
    FULLTEXT (title)
    );

# Title Format (e.g. Video, CD-ROM, Radio, etc) 
CREATE TABLE TitleFormat
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    title       VARCHAR(50) NOT NULL,
    PRIMARY KEY (id)
    );

# TitleFormat link table
CREATE TABLE TitleFormatLink
    (
    title_id    int unsigned NOT NULL,
    format_id   int unsigned NOT NULL,
    PRIMARY KEY (title_id, format_id),
    INDEX (format_id)
    );

# OnlineFormat (e.g. Streaming, Download, Streaming/Download)
CREATE TABLE OnlineFormat
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    title       VARCHAR(50) NOT NULL,
    PRIMARY KEY (id)
    );

# TitleRelation link table
CREATE TABLE TitleRelation
    (
    title1_id   int unsigned NOT NULL,
    title2_id   int unsigned NOT NULL,
    PRIMARY KEY (title1_id, title2_id)
    );

# TitleCountry link table
CREATE TABLE TitleCountry
    (
    title_id    int unsigned NOT NULL,
    country_id  VARCHAR(10) NOT NULL,
    PRIMARY KEY (title_id, country_id),
    INDEX (country_id)
    );

# Keyword
CREATE TABLE Keyword
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    title       VARCHAR(255) NOT NULL,
    date_created date DEFAULT NULL,
    hermes_id   VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX (hermes_id),
    FULLTEXT (title)
    );

# Category
CREATE TABLE Category
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    title       VARCHAR(255) NOT NULL,
    date_created date DEFAULT NULL,
    hermes_id   VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX (hermes_id),
    FULLTEXT (title)
    );

# Title keyword link table
CREATE TABLE TitleKeyword
    (
    title_id    int unsigned NOT NULL,
    keyword_id  int unsigned NOT NULL,
    date_created date DEFAULT NULL,
    PRIMARY KEY (title_id, keyword_id),
    INDEX (keyword_id)
    );

# Title category link table
CREATE TABLE TitleCategory
    (
    title_id    int unsigned NOT NULL,
    category_id int unsigned NOT NULL,
    date_created date DEFAULT NULL,
    PRIMARY KEY (title_id, category_id),
    INDEX (category_id)
    );

# Section keyword link table
CREATE TABLE SectionKeyword
    (
    section_id  int unsigned NOT NULL,
    keyword_id  int unsigned NOT NULL,
    date_created date DEFAULT NULL,
    PRIMARY KEY (section_id, keyword_id),
    INDEX (keyword_id)
    );

# Section category link table
CREATE TABLE SectionCategory
    (
    section_id  int unsigned NOT NULL,
    category_id int unsigned NOT NULL,
    date_created date DEFAULT NULL,
    PRIMARY KEY (section_id, category_id),
    INDEX (category_id)
    );

# Organisation type (e.g. Publishing, Production, Distribution, etc)
CREATE TABLE OrganisationType
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    title       VARCHAR(50) NOT NULL,
    PRIMARY KEY (id)
    );

# Organisation relation (e.g. Distributor, Archive, Publisher, etc)
CREATE TABLE OrganisationRelation
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    title       VARCHAR(50) NOT NULL,
    PRIMARY KEY (id)
    );

# OrganisationType link table
CREATE TABLE OrganisationTypeLink
    (
    org_id      int unsigned NOT NULL,
    org_type_id int unsigned NOT NULL,
    PRIMARY KEY (org_id, org_type_id),
    INDEX (org_type_id)
    );

# Organisation Participation - links Title and Organisation
CREATE TABLE OrganisationParticipation
    (
    title_id    int unsigned NOT NULL,
    org_id      int unsigned NOT NULL,
    org_relation_id int unsigned NOT NULL,
    date_created date DEFAULT NULL,
    PRIMARY KEY (title_id, org_id),
    INDEX (org_id),
    INDEX (org_relation_id)
    );

# DistributionMedia
CREATE TABLE DistributionMedia
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    title_id    int unsigned NOT NULL, # FK back to title
    type        VARCHAR(50) NOT NULL, # media type, e.g. Audio, CD-ROM, DVD
    format      VARCHAR(100) DEFAULT NULL, # format of media, e.g. Cassette (audio), PAL (DVD)
    price       VARCHAR(255) DEFAULT NULL, 
    availability VARCHAR(100) DEFAULT NULL, 
    length      VARCHAR(255) DEFAULT NULL, # e.g. time duration, number of slides, number of CDs
    year        CHAR(4) DEFAULT NULL,
    PRIMARY KEY (id)
    );

# Language
CREATE TABLE Language
    (
    id          VARCHAR(10) NOT NULL,
    title       VARCHAR(50) NOT NULL,
    PRIMARY KEY (id)
    );

# Country
CREATE TABLE Country
    (
    id          VARCHAR(10) NOT NULL,
    title       VARCHAR(50) NOT NULL,
    PRIMARY KEY (id)
    );
