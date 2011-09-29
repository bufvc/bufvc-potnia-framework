# $Id$
# Database schema for Fed module
# James Fryer, 5 June 10
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

# Statistics about records
CREATE TABLE RecordStats 
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    url         VARCHAR(255) NOT NULL,
    count       int unsigned NOT NULL DEFAULT 1,
    PRIMARY KEY (id), 
    UNIQUE(url)    
    );

# User votes for a record
CREATE TABLE RecordScore
    (
    id          int unsigned NOT NULL AUTO_INCREMENT,
    record_id   int unsigned NOT NULL,
    username    VARCHAR(255) NOT NULL,
    score       double NOT NULL DEFAULT 0,
    PRIMARY KEY (id), 
    INDEX (record_id),
    INDEX (username),
    INDEX (score)  
    );

# Statistics about queries
CREATE TABLE QueryStats
    (
    id          int unsigned NOT NULl AUTO_INCREMENT,
    url         VARCHAR(255) NOT NULL,
    count       int unsigned NOT NULL DEFAULT 1,
    date        datetime DEFAULT NULL,
    module      VARCHAR(50) DEFAULT NULL,
    search_table VARCHAR(50) DEFAULT NULL,
    criteria    VARCHAR(255) DEFAULT NULL,
    details     VARCHAR(255) DEFAULT NULL,
    results_count int unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE(url)
    );