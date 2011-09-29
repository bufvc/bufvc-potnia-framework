# $Id: migration_user_unit_test.sql,v 1.3 2008/07/25 10:47:32 jim Exp $
# Some unit test data for migration of user accounts
# James Fryer, 23 July 08, 26 Oct 09
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

# Test 1: User not found
# No entry in the database

# Test2: New account not found
# Single entry
INSERT INTO User SET 
    id = 100, 
    login = 'test2', 
    email = 'unique_entry@example.com'
    ;

# Test3: Multiple accounts
# More than two accounts with the same email address
# Not in ID order, to test sorting
# Out of 2111 live accounts, 1927 have only one entry
INSERT INTO User SET 
    id = 101, 
    login = 'test3.1', 
    email = 'too_many_accounts@example.com'
    ;
INSERT INTO User SET 
    id = 103, 
    login = 'test3.3', 
    email = 'too_many_accounts@example.com'
    ;
INSERT INTO User SET 
    id = 102, 
    login = 'test3.2', 
    email = 'too_many_accounts@example.com'
    ;

# Test 4: Normal user
INSERT INTO User SET 
    id = 110, 
    login = 'test4.old', 
    email = 'normal@example.com', 
    institution_id = 123
    ;
INSERT INTO UserData SET
    user_id = 110,
    name = 'test',
    value = 'foo'
    ;
INSERT INTO User SET 
    id = 111, 
    login = 'test4.new', 
    email = 'normal@example.com'
    ;

# Test 5a: Admin user
INSERT INTO User SET 
    id = 120, 
    root = 1, # Admin
    login = 'test5a.old', 
    email = 'admin@example.com', 
    institution_id = 456
    ;
INSERT INTO UserData SET
    user_id = 120,
    name = 'test',
    value = 'ba\'r'
    ;
INSERT INTO User SET 
    id = 121, 
    login = 'test5a.new', 
    email = 'admin@example.com'
    ;

# Test 5b: Editor 
INSERT INTO User SET 
    id = 122, 
    login = 'test5b.old', 
    email = 'editor@example.com'
    ;
INSERT INTO UserRight SET
    user_id = 122,
    right_id = 2 # Editor
    ;
INSERT INTO User SET 
    id = 123, 
    login = 'test5b.new', 
    email = 'editor@example.com'
    ;

# Test 5c: Off-air admin
INSERT INTO User SET 
    id = 124, 
    login = 'test5c.old', 
    email = 'offairadmin@example.com'
    ;
INSERT INTO UserRight SET
    user_id = 124,
    right_id = 6 # OA Admin
    ;
INSERT INTO User SET 
    id = 125, 
    login = 'test5c.new', 
    email = 'offairadmin@example.com'
    ;

# Test 6 off-air rep, with orders...
INSERT INTO User SET 
    id = 130, 
    login = 'test6.old', 
    email = 'offairrep@example.com', 
    institution_id = 789,
    telephone_number = '012345',
    offair_notifications = 255
    ;
INSERT INTO UserRight SET
    user_id = 130,
    right_id = 5 # OA Rep
    ;
INSERT INTO User SET 
    id = 131, 
    login = 'test6.new', 
    email = 'offairrep@example.com'
    ;

# Test 7: Already migrated
INSERT INTO User SET 
    id = 140, 
    login = 'test7.old', 
    email = 'migrated@example.com', 
    name = 'MIGRATED'
    ;
INSERT INTO User SET 
    id = 141, 
    login = 'test7.new', 
    email = 'migrated@example.com'
    ;

# The first two orders are owned by the rep and should have the rep ID changed;
# the last one is a dummy rep ID that should be unchanged.
INSERT INTO Orders SET 
    id = 1,
    off_air_rep_id = 130,
    channel = 'dummy',
    title = 'dummy',
    genre = 'dummy',
    requestor_notes = 'dummy',
    admin_notes = 'dummy',
    tape_id = 'dummy',
    course_details = 'dummy',
    programme_description = 'dummy',
    telephone_number = 'dummy',
    ad_hoc = 'dummy'
    ;
INSERT INTO Orders SET 
    id = 2,
    off_air_rep_id = 130,
    channel = 'dummy',
    title = 'dummy',
    genre = 'dummy',
    requestor_notes = 'dummy',
    admin_notes = 'dummy',
    tape_id = 'dummy',
    course_details = 'dummy',
    programme_description = 'dummy',
    telephone_number = 'dummy',
    ad_hoc = 'dummy'
    ;
INSERT INTO Orders SET 
    id = 3,
    off_air_rep_id = 999,
    channel = 'dummy',
    title = 'dummy',
    genre = 'dummy',
    requestor_notes = 'dummy',
    admin_notes = 'dummy',
    tape_id = 'dummy',
    course_details = 'dummy',
    programme_description = 'dummy',
    telephone_number = 'dummy',
    ad_hoc = 'dummy'
    ;

# Test 8a: Existing data is overwritten on target
INSERT INTO User SET 
    id = 150, 
    login = 'test8a.old', 
    email = 'test8a@example.com', 
    institution_id = 123
    ;
INSERT INTO UserData SET
    user_id = 150,
    name = 'test',
    value = 'quux'
    ;
INSERT INTO User SET 
    id = 151, 
    login = 'test8a.new', 
    email = 'test8a@example.com'
    ;
INSERT INTO UserData SET
    user_id = 151,
    name = 'test',
    value = 'overwritten'
    ;

# Test 8b: Existing rights are overwritten on target
INSERT INTO User SET 
    id = 152, 
    login = 'test8b.old', 
    email = 'test8b@example.com', 
    institution_id = 123
    ;
INSERT INTO UserRight SET
    user_id = 152,
    right_id = 2 # Editor
    ;
INSERT INTO UserRight SET
    user_id = 152,
    right_id = 6 # OA Admin
    ;
INSERT INTO User SET 
    id = 153, 
    login = 'test8b.new', 
    email = 'test8b@example.com'
    ;
INSERT INTO UserRight SET
    user_id = 153,
    right_id = 2 # Editor
    ;
