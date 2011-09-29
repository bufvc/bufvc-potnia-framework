# $Id$
# Convert sequence tables from DB to MDB2 style
# Phil Hansen, 03 Mar 2010
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

# Pear::DB sequence tables use a column named 'id'
# Pear::MDB2 sequence tables use a column named 'sequence'
# This script renames the columns in existing sequence tables
# Databases have to be manually entered here, but existing sequence tables will be queried from DB


USE hermes_test;

DELIMITER //
DROP PROCEDURE IF EXISTS process_db //
CREATE PROCEDURE process_db()
BEGIN 
    declare t_name VARCHAR(50);
    declare v_notfound BOOLEAN default FALSE;
    declare cursor_update CURSOR FOR
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME LIKE '%_seq' AND TABLE_SCHEMA='hermes_test';
    declare continue handler for not found
        set v_notfound = TRUE;
    declare exit handler
        for sqlexception
        close cursor_update;
    open cursor_update;
    cursor_loop: loop
        fetch cursor_update into t_name;
        if v_notfound then
            leave cursor_loop;
        end if;
        SET @sql = CONCAT("ALTER TABLE ",t_name," CHANGE id sequence int unsigned NOT NULL AUTO_INCREMENT");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
    end loop;
    close cursor_update;
end;
//
call process_db//

USE ilrsharing_test//

DROP PROCEDURE IF EXISTS process_db //
CREATE PROCEDURE process_db()
BEGIN 
    declare t_name VARCHAR(50);
    declare v_notfound BOOLEAN default FALSE;
    declare cursor_update CURSOR FOR
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME LIKE '%_seq' AND TABLE_SCHEMA='ilrsharing_test';
    declare continue handler for not found
        set v_notfound = TRUE;
    declare exit handler
        for sqlexception
        close cursor_update;
    open cursor_update;
    cursor_loop: loop
        fetch cursor_update into t_name;
        if v_notfound then
            leave cursor_loop;
        end if;
        SET @sql = CONCAT("ALTER TABLE ",t_name," CHANGE id sequence int unsigned NOT NULL AUTO_INCREMENT");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
    end loop;
    close cursor_update;
end;
//
call process_db//

USE ilrsouth_test//

DROP PROCEDURE IF EXISTS process_db //
CREATE PROCEDURE process_db()
BEGIN 
    declare t_name VARCHAR(50);
    declare v_notfound BOOLEAN default FALSE;
    declare cursor_update CURSOR FOR
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME LIKE '%_seq' AND TABLE_SCHEMA='ilrsouth_test';
    declare continue handler for not found
        set v_notfound = TRUE;
    declare exit handler
        for sqlexception
        close cursor_update;
    open cursor_update;
    cursor_loop: loop
        fetch cursor_update into t_name;
        if v_notfound then
            leave cursor_loop;
        end if;
        SET @sql = CONCAT("ALTER TABLE ",t_name," CHANGE id sequence int unsigned NOT NULL AUTO_INCREMENT");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
    end loop;
    close cursor_update;
end;
//
call process_db//

USE lbc_test//

DROP PROCEDURE IF EXISTS process_db //
CREATE PROCEDURE process_db()
BEGIN 
    declare t_name VARCHAR(50);
    declare v_notfound BOOLEAN default FALSE;
    declare cursor_update CURSOR FOR
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME LIKE '%_seq' AND TABLE_SCHEMA='lbc_test';
    declare continue handler for not found
        set v_notfound = TRUE;
    declare exit handler
        for sqlexception
        close cursor_update;
    open cursor_update;
    cursor_loop: loop
        fetch cursor_update into t_name;
        if v_notfound then
            leave cursor_loop;
        end if;
        SET @sql = CONCAT("ALTER TABLE ",t_name," CHANGE id sequence int unsigned NOT NULL AUTO_INCREMENT");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
    end loop;
    close cursor_update;
end;
//
call process_db//

USE mig_test//

DROP PROCEDURE IF EXISTS process_db //
CREATE PROCEDURE process_db()
BEGIN 
    declare t_name VARCHAR(50);
    declare v_notfound BOOLEAN default FALSE;
    declare cursor_update CURSOR FOR
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME LIKE '%_seq' AND TABLE_SCHEMA='mig_test';
    declare continue handler for not found
        set v_notfound = TRUE;
    declare exit handler
        for sqlexception
        close cursor_update;
    open cursor_update;
    cursor_loop: loop
        fetch cursor_update into t_name;
        if v_notfound then
            leave cursor_loop;
        end if;
        SET @sql = CONCAT("ALTER TABLE ",t_name," CHANGE id sequence int unsigned NOT NULL AUTO_INCREMENT");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
    end loop;
    close cursor_update;
end;
//
call process_db//

USE shk_test//

DROP PROCEDURE IF EXISTS process_db //
CREATE PROCEDURE process_db()
BEGIN 
    declare t_name VARCHAR(50);
    declare v_notfound BOOLEAN default FALSE;
    declare cursor_update CURSOR FOR
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME LIKE '%_seq' AND TABLE_SCHEMA='shk_test';
    declare continue handler for not found
        set v_notfound = TRUE;
    declare exit handler
        for sqlexception
        close cursor_update;
    open cursor_update;
    cursor_loop: loop
        fetch cursor_update into t_name;
        if v_notfound then
            leave cursor_loop;
        end if;
        SET @sql = CONCAT("ALTER TABLE ",t_name," CHANGE id sequence int unsigned NOT NULL AUTO_INCREMENT");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
    end loop;
    close cursor_update;
end;
//
call process_db//

USE trilt_test//

DROP PROCEDURE IF EXISTS process_db //
CREATE PROCEDURE process_db()
BEGIN 
    declare t_name VARCHAR(50);
    declare v_notfound BOOLEAN default FALSE;
    declare cursor_update CURSOR FOR
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME LIKE '%_seq' AND TABLE_SCHEMA='trilt_test';
    declare continue handler for not found
        set v_notfound = TRUE;
    declare exit handler
        for sqlexception
        close cursor_update;
    open cursor_update;
    cursor_loop: loop
        fetch cursor_update into t_name;
        if v_notfound then
            leave cursor_loop;
        end if;
        SET @sql = CONCAT("ALTER TABLE ",t_name," CHANGE id sequence int unsigned NOT NULL AUTO_INCREMENT");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
    end loop;
    close cursor_update;
end;
//
call process_db//

USE user_test//

DROP PROCEDURE IF EXISTS process_db //
CREATE PROCEDURE process_db()
BEGIN 
    declare t_name VARCHAR(50);
    declare v_notfound BOOLEAN default FALSE;
    declare cursor_update CURSOR FOR
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME LIKE '%_seq' AND TABLE_SCHEMA='user_test';
    declare continue handler for not found
        set v_notfound = TRUE;
    declare exit handler
        for sqlexception
        close cursor_update;
    open cursor_update;
    cursor_loop: loop
        fetch cursor_update into t_name;
        if v_notfound then
            leave cursor_loop;
        end if;
        SET @sql = CONCAT("ALTER TABLE ",t_name," CHANGE id sequence int unsigned NOT NULL AUTO_INCREMENT");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
    end loop;
    close cursor_update;
end;
//
call process_db//

USE demo_test//

DROP PROCEDURE IF EXISTS process_db //
CREATE PROCEDURE process_db()
BEGIN 
    declare t_name VARCHAR(50);
    declare v_notfound BOOLEAN default FALSE;
    declare cursor_update CURSOR FOR
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME LIKE '%_seq' AND TABLE_SCHEMA='demo_test';
    declare continue handler for not found
        set v_notfound = TRUE;
    declare exit handler
        for sqlexception
        close cursor_update;
    open cursor_update;
    cursor_loop: loop
        fetch cursor_update into t_name;
        if v_notfound then
            leave cursor_loop;
        end if;
        SET @sql = CONCAT("ALTER TABLE ",t_name," CHANGE id sequence int unsigned NOT NULL AUTO_INCREMENT");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
    end loop;
    close cursor_update;
end;
//
call process_db//

DELIMITER ;

