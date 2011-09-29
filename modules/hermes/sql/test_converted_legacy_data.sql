# $Id$
# Hermes Database expected data for testing
# Phil Hansen, 31 Mar 09
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

# This data depends on the new schema being loaded already

# Keyword
INSERT INTO `Keyword` (`id`, `title`, `date_created`, `hermes_id`) VALUES
(1, 'business firms', '2001-12-13', '960'),
(2, 'control systems', '2001-12-13', '1590'),
(3, 'manufacturing industries', '2001-12-13', '4283');

# Category
INSERT INTO `Category` (`id`, `title`, `date_created`, `hermes_id`) VALUES
(1, 'Business studies', '2001-12-13', '16'),
(2, 'Engineering', '2001-12-13', '35');

# Country
INSERT INTO `Country` (`id`, `title`) VALUES
('gb', 'Great Britain'),
('jp', 'Japan'),
('us', 'United States');

# Language
INSERT INTO `Language` (`id`, `title`) VALUES
('en', 'English');

# Title
INSERT INTO `Title` (`title`, `alt_title`, `title_series`, `subtitle`, `date_created`, `date`, `date_released`, `date_production`, `description`, `is_colour`, `is_silent`, `language_id`, `online_url`, `online_price`, `online_format_id`, `is_online`, `notes`, `notes_documentation`, `notes_uses`, `distributors_ref`, `isbn`, `shelf_ref`, `ref`, `physical_description`, `price`, `availability`, `viewfinder`, `is_shakespeare`, `hermes_id`, `director`, `producer`, `format`, `format_summary`, `subject`, `section_title`, `distribution`, `misc`) VALUES
('3m''s Bedford Distribution Centre', NULL, 'Connect for Better Business, Series', NULL, '1999-09-09', '1995-00-00', '1995', NULL, 'A case study showing how a large-sized business has used good business practices to enhance its competitive edge. 3M Bedford Distribution Centre, with 4400 employees it its UK organisation, is an important part of the 3M UK operation and is changing to offer customers tailored services that fit the new requirements of low inventory and flow-through product.', 0, 0, 'en', 'www.emol.ac.uk', 'free to UK HE and FE', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'eng trilt VF25T vf37', 'Videocassette. VHS. col. 10 min.', '1999 sale: £39.50 (+VAT +p&p)', 'Sale', 25, 0, '1', 'Sherry Moran','Sherry Moran','Video', 1, 'Business studies; Engineering','Performing a workplace scan @@ S-1: sort @@ S-2: set in order','Sale, DVD (Region 2 NTSC, Region 0, VCD, 67 minutes), $9.99',' @@ English @@ Great Britain @@ United States @@ Video @@ business firms @@ Business studies @@ Engineering @@ Sherry Moran @@ Department of Trade and Industry, Best Practice Division @@ Tribeca @@ Performing a workplace scan @@ S-1: sort @@ S-2: set in order @@  @@ '),
('5S: Visual Control Systems', NULL, NULL, NULL, '1997-09-23', '0199-00-00', '0199', NULL, 'Looks in detail at the visual control of the workplace covering the storage, operations, equipment, quality and safety. Offers concrete examples of visual control in action in Japanese manufacturng plants.', 0, 0, 'en', 'http://environment.uwe.ac.uk/video/wmv.html', '£34.99', 3, 1, NULL, 'Accompanying instructor''s guide.', NULL, NULL, NULL, NULL, 'vf32++', '3 videocassettes. VHS. col. 90 min.', '1997 sale: £750.00 (+VAT)', 'Sale', 32, 0, '10', '', '', 'Audio, Video', 3, 'Engineering', 'Better team performance @@ Creating and effective team @@ Picking the right people', 'Sale, 3 videocassettes. VHS. col. 90 min., 1997 sale: £750.00 (+VAT)', ' @@ Accompanying instructor\'s guide. @@ English @@ Japan @@ Audio @@ Video @@ control systems @@ manufacturing industries @@ Engineering @@ Productivity Europe Ltd @@ Better team performance @@ Creating and effective team @@ Picking the right people @@ 1: Training for leaders in the skills and determination to steer their teams in the right direction and improve overall performance. @@ 2: Building a team of diverse personalities with different roles who can work together with good communication and under pressure. @@ 3: Importance of the right staff, techniques for selecting team members, and interview skills. @@ '),
('1930 to Tomorrow', NULL, 'Together (2 Parts)', NULL, '1993-04-02', NULL, NULL, NULL, NULL, 0, 0, 'en', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '100', '', '', NULL, 0, NULL, NULL, NULL, ' @@ English'),
('A Man in a Room Gambling', NULL, NULL, NULL, '2002-01-09', '1997-00-00', '1997', NULL, 'An audio recording of a performance, originally broadcast on BBC radio, in which British composer Gavin Bryars and Spanish sculptor Juan Muñoz conceived a series of five-minute pieces about card tricks. Muñoz narrates each trick from the preparation of the pack to the manipulation and revelation, whilst Bryars'' music orchestrates the deception and intensifies the duplicity Muñoz describes. Performed by The Gavin Bryars Ensemble and released on Phillip Glass''s Point Music label, the CD features a number of other compositions by Bryars, including the North Downs and the South Downs, for piano, cello and viola. The CD includes sleeve notes by Gavin Bryars. 73 minutes', 0, 0, 'en', NULL, NULL, NULL, 0, NULL, NULL, NULL, 'AA31', NULL, NULL, 'vf 47', 'Compact disc.', NULL, 'Sale', 47, 0, 'AV34238', '', '', 'Audio', 2, NULL, 'A Man in a room gambling', 'Sale, Audio (CD), £14.95 (inclusive)',' @@ English @@ Great Britain @@ Audio @@ A Man in a room gambling @@ description @@ note\'s @@ business firms @@ Business studies @@ Gavin Bryars @@ Juan Munoz');

# Section
INSERT INTO `Section` (`title_id`, `title`, `description`, `notes`, `duration`, `is_colour`, `is_silent`, `distributors_ref`, `isbn`, `number_in_series`, `hermes_id`) VALUES
(1,'Performing a workplace scan',NULL,NULL,NULL,0,0,NULL,NULL,'1','3'),
(1,'S-1: sort',NULL,NULL,NULL,0,0,NULL,NULL,'2','5'),
(1,'S-2: set in order',NULL,NULL,NULL,0,0,NULL,NULL,'3','7'),
(2,'Better team performance','1: Training for leaders in the skills and determination to steer their teams in the right direction and improve overall performance.',NULL,NULL,0,0,NULL,NULL,'1','28'),
(2,'Creating and effective team','2: Building a team of diverse personalities with different roles who can work together with good communication and under pressure.',NULL,NULL,0,0,NULL,NULL,'2','30'),
(2,'Picking the right people','3: Importance of the right staff, techniques for selecting team members, and interview skills.',NULL,NULL,0,0,NULL,NULL,'3','32'),
(4, 'A Man in a room gambling', 'description', 'note''s', 4380, 1, 1, 'FHEV 1453 (video)'' FHED 1454', '1 887943 57 9', '1', '49584');

# Person
INSERT INTO `Person` (`date_created`, `name`, `notes`, `hermes_id`) VALUES  
('2001-12-13', 'Gavin Bryars', NULL, '1956'),
('2001-12-13', 'Sherry Moran', NULL, '9888'),
('2002-01-31', 'Juan Munoz', NULL, 'N39643');

# Organisation
INSERT INTO `Organisation` (`name`, `date_created`, `notes`, `contact_name`, `contact_position`, `email`, `web_url`, `telephone`, `fax`, `address_1`, `address_2`, `address_3`, `address_4`, `town`, `county`, `postcode`, `country`, `hermes_id`) VALUES
('Department of Trade and Industry, Best Practice Division', '2001-12-13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1924'),
('Productivity Europe Ltd', '2001-12-13', NULL, NULL, NULL, NULL, 'http://www.productivityeurope.co.uk', '01234 713311', '01234 713930', 'Osborns Court', 'Olney', 'Bucks', 'MK46 4AG', NULL, NULL, NULL, NULL, '5003'),
('Tribeca', '2001-12-13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '6222');

# TitleFormat
INSERT INTO `TitleFormat` (`id`, `title`) VALUES
(1, 'Audio'),
(2, 'Film'),
(3, 'Multimedia'),
(4, 'Radio'),
(5, 'Television'),
(6, 'Video');

# TitleFormatLink
INSERT INTO `TitleFormatLink` (`title_id`, `format_id`) VALUES
(1, 6),
(2, 1),
(2, 6),
(4, 1);

# OnlineFormat
INSERT INTO `OnlineFormat` (`id`, `title`) VALUES
(1, 'Streamed'),
(2, 'Download'),
(3, 'Streamed/Download');

# TitleRelation
INSERT INTO `TitleRelation` (`title1_id`, `title2_id`) VALUES
(1, 2);

# TitleCountry
INSERT INTO `TitleCountry` (`title_id`, `country_id`) VALUES
(1, 'gb'),
(1, 'us'),
(2, 'jp'),
(4, 'gb');

# TitleKeyword link
INSERT INTO `TitleKeyword` (`title_id`, `keyword_id`, `date_created`) VALUES
(1, 1, '2008-02-14'),
(2, 2, '2003-08-19'),
(2, 3, '2003-08-19');

# TitleCategory link
INSERT INTO `TitleCategory` (`title_id`, `category_id`, `date_created`) VALUES
(1, 1, '2008-02-14'),
(1, 2, '2008-02-14'),
(2, 2, '2003-04-07');

# SectionKeyword link
INSERT INTO `SectionKeyword` (`section_id`, `keyword_id`, `date_created`) VALUES
(7, 1, '2006-10-24');

# SectionCategory link
INSERT INTO `SectionCategory` (`section_id`, `category_id`, `date_created`) VALUES
(7, 1, '2005-11-07');

# Role
INSERT INTO `Role` (`id`, `is_technical`, `title`) VALUES
(1, 1, 'Adaptor for Radio'),
(2, 1, 'Adaptor for Television'),
(3, 1, 'Writer'),
(4, 0, 'Performer'),
(5, 1, 'Editor'),
(6, 1, 'Producer'),
(7, 1, 'Director'),
(8, 1, 'Contributor'),
(9, 1, 'Composer'),
(10, 1, 'Music'),
(11, 1, 'Music Director'),
(12, 1, 'Art Direction'),
(13, 1, 'Cinematographer'),
(14, 1, 'Screenplay'),
(15, 1, 'Costume'),
(16, 1, 'Choreographer'),
(17, 1, 'Animator'),
(18, 1, 'Production Design');

# Participation
INSERT INTO `Participation` (`title_id`, `person_id`, `role_id`, `date_created`) VALUES
(1, 2, 3, '2008-02-14'),
(1, 2, 6, '2008-02-14'),
(1, 2, 7, '2008-02-14');

# SectionParticipation
INSERT INTO `SectionParticipation` (`section_id`, `person_id`, `role_id`, `date_created`) VALUES
(7, 1, 4, '2006-01-24'),
(7, 3, 3, '2006-01-24'),
(7, 3, 4, '2006-01-24');

# OrganisationType
INSERT INTO `OrganisationType` (`id`, `title`) VALUES
(1, 'Publishing'),
(2, 'Production'),
(3, 'Distribution'),
(4, 'Sponsor');

# OrganisationRelation
INSERT INTO `OrganisationRelation` (`id`, `title`) VALUES
(1, 'Related'),
(2, 'Archive'),
(3, 'Distributor'),
(4, 'Distributor (DVD)'),
(5, 'Distributor (VHS)'),
(6, 'Distributor (Hire)'),
(7, 'Distributor (Sale)'),
(8, 'Online Retailer'),
(9, 'Production Company'),
(10, 'Publisher'),
(11, 'Publisher''s ref. no.'),
(12, 'Sponsor');

# OrganisationTypeLink
INSERT INTO `OrganisationTypeLink` (`org_id`, `org_type_id`) VALUES
(1, 3);

# OrganisationParticipation
INSERT INTO `OrganisationParticipation` (`title_id`, `org_id`, `org_relation_id`, `date_created`) VALUES
(1, 1, 12, '2008-02-14'),
(1, 3, 3, '2008-02-14'),
(2, 2, 3, '2002-05-08');

# DistributionMedia
INSERT INTO `DistributionMedia` (`id`, `title_id`, `type`, `format`, `price`, `availability`, `length`, `year`) VALUES
(1, 1, 'Audio', 'Cassette', '£9.99', 'Sale', '150 mimutes', '2004'),
(2, 1, 'CD-ROM', 'Hybrid', '£99.00 - £250.00 each', 'Sale', '10 discs', '2005'),
(3, 1, 'DVD', 'Region 2 NTSC, Region 0, VCD', '$9.99', 'Sale', '67 minutes', '2003'),
(4, 1, 'Film', '16mm', '£30.00', 'Hire', '92 minutes', '2004'),
(5, 1, 'Slide', 'Slide Set', '£80.00', NULL, '80', '2005'),
(6, 1, 'VHS', 'PAL', '£13.00 (hire)£80.00 (sale)', 'SaleHire', '2 x 55 minutes', '2005'),
(7, 4, 'Audio', 'CD', '£14.95 (inclusive)', 'Sale', NULL, '2002');