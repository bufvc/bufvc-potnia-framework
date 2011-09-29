-- phpMyAdmin SQL Dump
-- version 2.11.8.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 31, 2009 at 01:56 PM
-- Server version: 5.0.67
-- PHP Version: 5.2.6-2ubuntu4.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `bufvc_core`
--

-- --------------------------------------------------------

--
-- Table structure for table `example_title_dataset`
--

CREATE TABLE IF NOT EXISTS `example_title_dataset` (
  `id` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `example_val_table`
--

CREATE TABLE IF NOT EXISTS `example_val_table` (
  `value` int(11) NOT NULL auto_increment,
  `label` varchar(50) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `keyword`
--

CREATE TABLE IF NOT EXISTS `keyword` (
  `id` int(11) NOT NULL auto_increment,
  `data_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `data_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `data_status` int(1) NOT NULL default '5',
  `data_author` varchar(40) default NULL,
  `data_set` varchar(60) default NULL,
  `term` varchar(255) NOT NULL,
  `description` text,
  `legacy_id` varchar(20) default NULL,
  PRIMARY KEY  (`id`),
  KEY `legacy_id` (`legacy_id`),
  KEY `data_status` (`data_status`),
  KEY `term` (`term`),
  KEY `data_set` (`data_set`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9440 ;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_AV_Parts`
--

CREATE TABLE IF NOT EXISTS `legacy_AV_Parts` (
  `unique_id` int(11) NOT NULL auto_increment,
  `Part_ID` text,
  `AV_ID` text,
  `Part_title` text,
  `series_title` text,
  `Part_unnumbered_series_title` text,
  `Part_number_in_series` text,
  `Part_numbered_part_title` text,
  `Part_unnumbered_part_title` text,
  `Part_numbered_part` text,
  `Playing_time` text,
  `Colour_BW` text,
  `Sound_Mute` text,
  `Synopsis_part` text,
  `Part_Notes` text,
  `contributor_name` text,
  `contribution_type` text,
  `recognition_field` text,
  `av_title` text,
  `global_orginal_code` text,
  `global_new_code` text,
  `constant` text,
  `tag` text,
  `creation_date` text,
  `modification_date` text,
  `w_freetext_general` text,
  `w_freetext_title` text,
  `w_freetext_synopsis` text,
  `w_cTextSumCalcFreetextGeneral` text,
  `w_cTextSumCalcFreetextTitle` text,
  `w_cTextSumCalcFreetextsynopsis` text,
  `parts_check` text,
  `Distributor_reference` text,
  `Part_ISBN` text,
  `AV_Parts_production_date` text,
  `last_mod_date_global` text,
  `sh_constant` text,
  `sh_transmission_date` text,
  `sh_transmission_date_accuracy` text,
  `sh_transmission_date_calc` text,
  `sh_recording_date` text,
  `sh_recording_date_accuracy` text,
  `sh_recording_date_calc` text,
  `sh_transmission_time` text,
  `sh_duration_mins` text,
  `sh_duration_feet` text,
  `g_part_no_in_series` text,
  `g_part_synopsis` text,
  `Creator` text,
  `part_count` text,
  PRIMARY KEY  (`unique_id`),
  KEY `AV_ID` (`AV_ID`(10)),
  KEY `Part_ID` (`Part_ID`(10))
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=53019 ;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_AV_titles`
--

CREATE TABLE IF NOT EXISTS `legacy_AV_titles` (
  `Audio_format` text,
  `Audio_Price` text,
  `Audio_Sale_Hire` text,
  `Audio_time` text,
  `Audio_Year` text,
  `Audio_Yes` text,
  `AV_Alternative_title` text,
  `AV_availability` text,
  `AV_Catalogued_date` text,
  `AV_Catalogued_Time` text,
  `AV_Cataloguers_notes` text,
  `AV_country` text,
  `AV_distributors_ref_no` text,
  `AV_documentation_included` text,
  `AV_documentation_notes` text,
  `AV_ID` text,
  `AV_indexed_notes` text,
  `AV_ISBN_Number` text,
  `AV_Language` text,
  `AV_Medium` text,
  `AV_modification_date` text,
  `AV_Notes` text,
  `AV_physical_description` text,
  `AV_price` text,
  `AV_production_company_companies_link` text,
  `AV_Production_Date` text,
  `AV_Release_date` text,
  `AV_Series_Title` text,
  `AV_Shelf_Reference` text,
  `AV_Source` text,
  `AV_Synopsis` text,
  `AV_title` text,
  `AV_Uses` text,
  `Recovered_3` text,
  `cd_rom_price` text,
  `Recovered_11` text,
  `cd_rom_year` text,
  `Recovered_7` text,
  `CDROM_time` text,
  `Colour_BW` text,
  `constant` text,
  `Recovered_2` text,
  `dvd_price` text,
  `Recovered_10` text,
  `DVD_time` text,
  `dvd_year` text,
  `Recovered_6` text,
  `DVD_format2` text,
  `DVD_format3` text,
  `Recovered_4` text,
  `film_price` text,
  `Film_Sale_Hire` text,
  `Film_time` text,
  `film_year` text,
  `Recovered_8` text,
  `flat_companies` text,
  `flat_individuals` text,
  `flat_parts_freetext_general` text,
  `flat_parts_freetext_synopsis` text,
  `flat_parts_freetext_title` text,
  `flat_subject_terms` text,
  `flat_thesaurus_terms` text,
  `global_new_code` text,
  `global_original_code` text,
  `online` text,
  `Slide_Tape_Format` text,
  `Slide_Tape_number` text,
  `Slide_Tape_Price` text,
  `Slide_Tape_Sale_Hire` text,
  `Slide_Tape_time` text,
  `Slide_Tape_Year` text,
  `Slide_Tape_Yes` text,
  `Sound_Mute` text,
  `subtitle` text,
  `tag` text,
  `Recovered_1` text,
  `video_price` text,
  `Recovered_9` text,
  `Video_time` text,
  `Video_year` text,
  `Recovered_5` text,
  `w_format_calc` text,
  `w_individuals` text,
  `w_synopsis` text,
  `Foundset_summary` text,
  `video_price_vat_pp` text,
  `cd_rom_price_vat_pp` text,
  `dvd_price_vat_pp` text,
  `flm_price_vat_pp` text,
  `audio_price_vat_pp` text,
  `slide_tape_price_vat_pp` text,
  `last_mod_date_global` text,
  `web_tag` text,
  `On_line_yes` text,
  `On_line_format` text,
  `On_line_player` text,
  `On_line_url` text,
  `On_line_price` text,
  `tinlib` text,
  `av_title_CAPS` text,
  `thesaurus_input_filter` text,
  `Categories_tag_field` text,
  `tagged_author_calc` text,
  `sh_duration_mins` text,
  `sh_duration_feet` text,
  `sh_play_cat` text,
  `sh_period` text,
  `sh_transmission_date` text,
  `sh_transmission_date_accuracy` text,
  `sh_recording_date_accuracy` text,
  `sh_recording_date_calc` text,
  `sh_transmission_time` text,
  `sh_reviews` text,
  `sh_reviews_global` text,
  `Sh_constant` text,
  `sh_play_id` text,
  `sh_history` text,
  `sh_theatre_company` text,
  `sh_theatre` text,
  `sh_textual_info` text,
  `sh_figures` text,
  `sh_awards` text,
  `sh_stills` text,
  `sh_general_notes` text,
  `sh_user_comments` text,
  `sh_prod_type` text,
  `sh_indiv_director` text,
  `sh_transmission_date_calc` text,
  `sh_recording_date` text,
  `creator` text,
  `part_count` text,
  `record_count` text,
  `sh_broadcast_channel` text,
  `record_nav_global` text,
  `sh_title_calc` text,
  `sh_title_overide` text,
  `sh_approx_date_flag` text,
  `sh_sort_field` text,
  `sh_sort_field_without_carriage_returns` text,
  KEY `AV_ID` (`AV_ID`(10)),
  KEY `Sh_constant` (`Sh_constant`(1))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_companies`
--

CREATE TABLE IF NOT EXISTS `legacy_companies` (
  `Company_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Company_Name` text,
  `C_address_1` text,
  `C_address_2` text,
  `C_address_3` text,
  `C_address_4` text,
  `C_town` text,
  `C_county` text,
  `C_postcode` text,
  `C_Country` text,
  `C_Telephone` text,
  `C_Fax` text,
  `C_contact_name` text,
  `C_contact_position` text,
  `C_email` text,
  `C_web` text,
  `C_Notes` text,
  `C_company_type` text,
  `company_name_with_address` text,
  `old_full_address` text,
  `full_address` text,
  `tag` text,
  KEY `Company_ID` (`Company_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_country`
--

CREATE TABLE IF NOT EXISTS `legacy_country` (
  `code` text,
  `country_name` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_individuals`
--

CREATE TABLE IF NOT EXISTS `legacy_individuals` (
  `Individual_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Individual_Full_name` text,
  `First_Name` text,
  `Middle_Name` text,
  `Surname` text,
  `Salutation` text,
  `Individual_Notes` text,
  `Individual_Classification` text,
  `Author_editor_names` text,
  `classification_for_list_view` text,
  `Del` text,
  `global` text,
  KEY `Individual_ID` (`Individual_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_language`
--

CREATE TABLE IF NOT EXISTS `legacy_language` (
  `code` text,
  `language` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_L_comp`
--

CREATE TABLE IF NOT EXISTS `legacy_L_comp` (
  `Company_Link_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Company_Name` text,
  `Company_ID` text,
  `Position` text,
  `BKS_ID` text,
  `AV_ID` text,
  `Linked_book_title` text,
  `Linked_AV_Title` text,
  `final_titile` text,
  `book_type` text,
  `final_type` text,
  `final_ID` text,
  `Company_ID_Copy` text,
  `constant` text,
  `Linked_full_address` text,
  `flat_companies` text,
  `cSumTextCalc` text,
  `sh_tag` text,
  KEY `Company_Link_ID` (`Company_Link_ID`(10)),
  KEY `Company_ID` (`Company_ID`(10)),
  KEY `AV_ID` (`AV_ID`(10)),
  KEY `BKS_ID` (`BKS_ID`(10)),
  KEY `final_ID` (`final_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_L_Country`
--

CREATE TABLE IF NOT EXISTS `legacy_L_Country` (
  `av_id` text,
  `country_code` text,
  `country` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_L_Indiv`
--

CREATE TABLE IF NOT EXISTS `legacy_L_Indiv` (
  `Individual_Link_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Name` text,
  `Individual_ID` text,
  `Position` text,
  `BKS_ID` text,
  `AV_partID` text,
  `Linked_book_title` text,
  `Linked_AV_Part_Title` text,
  `book_type` text,
  `final_type` text,
  `final_titile` text,
  `final_ID` text,
  `AV_title_id` text,
  `Linked_AV_main_Title` text,
  `constant` text,
  `flat_info` text,
  `cSumTextCalc` text,
  `global` text,
  `Article_ID` text,
  `Linked_article_title` text,
  `play_id` text,
  `role` text,
  `sh_director_calc` text,
  `sh_cast_name` text,
  `Recovered_1` text,
  `Recovered_2` text,
  `creation_time` text,
  `sh_tag` text,
  KEY `Individual_Link_ID` (`Individual_Link_ID`(10)),
  KEY `Individual_ID` (`Individual_ID`(10)),
  KEY `AV_title_id` (`AV_title_id`(10)),
  KEY `Article_ID` (`Article_ID`(10)),
  KEY `BKS_ID` (`BKS_ID`(10)),
  KEY `AV_partID` (`AV_partID`(10)),
  KEY `final_ID` (`final_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_L_Shakespeare`
--

CREATE TABLE IF NOT EXISTS `legacy_L_Shakespeare` (
  `av_id1` text,
  `av_id2` text,
  `title` text,
  `constant` text,
  KEY `av_id1` (`av_id1`(10)),
  KEY `av_id2` (`av_id2`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_L_Subjects`
--

CREATE TABLE IF NOT EXISTS `legacy_L_Subjects` (
  `AV_Subject_ID` text,
  `BJC_Subject_ID` text,
  `TRILT_ID` text,
  `MIG_ID` text,
  `RGO_ID` text,
  `BUND_ID` text,
  `Final_ID_Subject` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Related_format_BJC__subject` text,
  `Related_Format_Final_subject` text,
  `Related_subject_title_BJC` text,
  `Related_title_final_subject` text,
  `Term` text,
  `Subect_Link_ID` text,
  `Subject_ID` text,
  `constant` text,
  `cSumTextCalc_subjects` text,
  `AV_title` text,
  `TRILT_title` text,
  `RGO_title` text,
  `MIG_title` text,
  `Article_ID` text,
  `Article_title` text,
  `AV_Part_Subject_ID` text,
  `AV_Part_title` text,
  `Viefinder_ID` text,
  KEY `AV_Subject_ID` (`AV_Subject_ID`(10)),
  KEY `TRILT_ID` (`TRILT_ID`(10)),
  KEY `MIG_ID` (`MIG_ID`(10)),
  KEY `RGO_ID` (`RGO_ID`(10)),
  KEY `BUND_ID` (`BUND_ID`(10)),
  KEY `BJC_Subject_ID` (`BJC_Subject_ID`(10)),
  KEY `Subect_Link_ID` (`Subect_Link_ID`(10)),
  KEY `Subject_ID` (`Subject_ID`(10)),
  KEY `Article_ID` (`Article_ID`(10)),
  KEY `Final_ID_Subject` (`Final_ID_Subject`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_L_Thes_Titles`
--

CREATE TABLE IF NOT EXISTS `legacy_L_Thes_Titles` (
  `Article_ID` text,
  `AV_ID` text,
  `AV_title` text,
  `BJC_ID` text,
  `constant` text,
  `cSumTextCalc_thes` text,
  `Final_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Related_format_BJC` text,
  `Related_Format_Final` text,
  `Related_title_BJC` text,
  `Related_title_final` text,
  `Term` text,
  `Thes_Link_ID` text,
  `Thesaurus_ID` text,
  `VF_calc_ID` text,
  `VF_item_ID` text,
  `VF_item_subject_ID` text,
  `VF_item_title` text,
  `VF_item_title_subject` text,
  `Related_title_Article` text,
  `Part_ID` text,
  `AV_Part_title` text,
  `custom_sort_tag` text,
  `Viewfinder_ID` text,
  `Tagged_authors_calc` text,
  KEY `Thesaurus_ID` (`Thesaurus_ID`(10)),
  KEY `Thes_Link_ID` (`Thes_Link_ID`(10)),
  KEY `AV_ID` (`AV_ID`(10)),
  KEY `Article_ID` (`Article_ID`(10)),
  KEY `BJC_ID` (`BJC_ID`(10)),
  KEY `Part_ID` (`Part_ID`(10)),
  KEY `Final_ID` (`Final_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_ShakespearePlays`
--

CREATE TABLE IF NOT EXISTS `legacy_ShakespearePlays` (
  `id` text,
  `play_title` text,
  `roles` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_Subjects`
--

CREATE TABLE IF NOT EXISTS `legacy_Subjects` (
  `Subject_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Term` text,
  KEY `Subject_ID` (`Subject_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_thesaurus`
--

CREATE TABLE IF NOT EXISTS `legacy_thesaurus` (
  `Thesaurus_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Term` text,
  `Del` text,
  `global` text,
  KEY `Thesaurus_ID` (`Thesaurus_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `org`
--

CREATE TABLE IF NOT EXISTS `org` (
  `id` int(11) NOT NULL auto_increment,
  `data_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `data_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `data_status` int(1) NOT NULL default '5',
  `data_set` varchar(60) default NULL,
  `data_author` varchar(40) default NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(40) default NULL,
  `description` text,
  `notes` text,
  `contact_name` varchar(150) default NULL,
  `contact_position` varchar(150) default NULL,
  `email` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `telephone` varchar(75) default NULL,
  `fax` varchar(75) default NULL,
  `address_1` varchar(150) default NULL,
  `address_2` varchar(150) default NULL,
  `address_3` varchar(150) default NULL,
  `address_4` varchar(150) default NULL,
  `address_town` varchar(75) default NULL,
  `address_county` varchar(75) default NULL,
  `address_country` varchar(75) default NULL,
  `address_postcode` varchar(75) default NULL,
  `legacy_id` varchar(20) default NULL,
  PRIMARY KEY  (`id`),
  KEY `legacy_id` (`legacy_id`),
  KEY `data_status` (`data_status`),
  KEY `name` (`name`),
  KEY `data_set` (`data_set`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=34723 ;

-- --------------------------------------------------------

--
-- Table structure for table `org_hrmcomp`
--

CREATE TABLE IF NOT EXISTS `org_hrmcomp` (
  `id` int(11) NOT NULL,
  `legacy_full_address` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pers`
--

CREATE TABLE IF NOT EXISTS `pers` (
  `id` int(11) NOT NULL auto_increment,
  `data_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `data_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `data_status` int(1) NOT NULL default '5',
  `data_author` varchar(40) default NULL,
  `data_set` varchar(60) default NULL,
  `name_last` varchar(255) NOT NULL,
  `name_other` varchar(50) default NULL,
  `name_first` varchar(50) default NULL,
  `name_salutation` varchar(15) default NULL,
  `type` varchar(40) default NULL,
  `date_birth` date default NULL,
  `date_death` date default NULL,
  `description` text,
  `notes` text,
  `legacy_id` varchar(20) default NULL,
  PRIMARY KEY  (`id`),
  KEY `legacy_id` (`legacy_id`),
  KEY `data_status` (`data_status`),
  KEY `name_last` (`name_last`),
  KEY `data_set` (`data_set`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=135577 ;

-- --------------------------------------------------------

--
-- Table structure for table `pers_hrmindiv`
--

CREATE TABLE IF NOT EXISTS `pers_hrmindiv` (
  `id` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `relation`
--

CREATE TABLE IF NOT EXISTS `relation` (
  `id` int(11) NOT NULL auto_increment,
  `data_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `data_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `data_status` int(1) NOT NULL default '5',
  `data_author` varchar(40) default NULL,
  `data_set` varchar(60) default NULL,
  `id1` int(11) NOT NULL,
  `id1_entity` varchar(20) NOT NULL,
  `id2` int(11) NOT NULL,
  `id2_entity` varchar(20) NOT NULL,
  `description` varchar(20) NOT NULL,
  `description_val` varchar(100) NOT NULL,
  `bidir` tinyint(1) NOT NULL default '1',
  `legacy_id` varchar(20) default NULL,
  PRIMARY KEY  (`id`),
  KEY `legacy_id` (`legacy_id`),
  KEY `id1_entity` (`id1_entity`,`id1`),
  KEY `id2_entity` (`id2_entity`,`id2`),
  KEY `data_set` (`data_set`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=313083 ;

-- --------------------------------------------------------

--
-- Table structure for table `relation_indiv_shk`
--

CREATE TABLE IF NOT EXISTS `relation_indiv_shk` (
  `id` int(11) NOT NULL,
  `play` int(11) default NULL,
  `role` int(11) default NULL,
  `cast_name` varchar(150) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(11) NOT NULL auto_increment,
  `id1` int(11) NOT NULL,
  `id1_entity` varchar(20) NOT NULL,
  `tag` varchar(20) default NULL,
  `data_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `data_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `data_status` int(1) NOT NULL default '5',
  `data_author` varchar(40) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id1` (`id1`,`id1_entity`),
  KEY `data_status` (`data_status`),
  KEY `tag` (`tag`),
  KEY `id1_2` (`id1`,`id1_entity`,`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `temp_avtitle_shk_notshk`
--

CREATE TABLE IF NOT EXISTS `temp_avtitle_shk_notshk` (
  `AV_ID` text,
  `AV_title` text,
  `sh_approx_date_flag` text,
  `sh_awards` text,
  `sh_broadcast_channel` text,
  `Sh_constant` text,
  `sh_duration_feet` text,
  `sh_duration_mins` text,
  `sh_figures` text,
  `sh_general_notes` text,
  `sh_history` text,
  `sh_period` text,
  `sh_play_cat` text,
  `sh_play_id` text,
  `sh_prod_type` text,
  `sh_recording_date` text,
  `sh_recording_date_accuracy` text,
  `sh_reviews` text,
  `sh_reviews_global` text,
  `sh_stills` text,
  `sh_textual_info` text,
  `sh_theatre` text,
  `sh_theatre_company` text,
  `sh_title_overide` text,
  `sh_transmission_date` text,
  `sh_transmission_date_accuracy` text,
  `sh_transmission_time` text,
  `sh_user_comments` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_l_indiv_shk_multiplaynorole`
--

CREATE TABLE IF NOT EXISTS `temp_l_indiv_shk_multiplaynorole` (
  `Individual_Link_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Name` text,
  `Individual_ID` text,
  `Position` text,
  `BKS_ID` text,
  `AV_partID` text,
  `Linked_book_title` text,
  `Linked_AV_Part_Title` text,
  `book_type` text,
  `final_type` text,
  `final_titile` text,
  `final_ID` text,
  `AV_title_id` text,
  `Linked_AV_main_Title` text,
  `constant` text,
  `flat_info` text,
  `cSumTextCalc` text,
  `global` text,
  `Article_ID` text,
  `Linked_article_title` text,
  `play_id` text,
  `role` text,
  `sh_director_calc` text,
  `sh_cast_name` text,
  `Recovered_1` text,
  `Recovered_2` text,
  `creation_time` text,
  `sh_tag` text,
  KEY `Individual_Link_ID` (`Individual_Link_ID`(10)),
  KEY `Individual_ID` (`Individual_ID`(10)),
  KEY `AV_title_id` (`AV_title_id`(10)),
  KEY `Article_ID` (`Article_ID`(10)),
  KEY `BKS_ID` (`BKS_ID`(10)),
  KEY `AV_partID` (`AV_partID`(10)),
  KEY `final_ID` (`final_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_l_indiv_shk_nosinglerolematch`
--

CREATE TABLE IF NOT EXISTS `temp_l_indiv_shk_nosinglerolematch` (
  `Individual_Link_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Name` text,
  `Individual_ID` text,
  `Position` text,
  `BKS_ID` text,
  `AV_partID` text,
  `Linked_book_title` text,
  `Linked_AV_Part_Title` text,
  `book_type` text,
  `final_type` text,
  `final_titile` text,
  `final_ID` text,
  `AV_title_id` text,
  `Linked_AV_main_Title` text,
  `constant` text,
  `flat_info` text,
  `cSumTextCalc` text,
  `global` text,
  `Article_ID` text,
  `Linked_article_title` text,
  `play_id` text,
  `role` text,
  `sh_director_calc` text,
  `sh_cast_name` text,
  `Recovered_1` text,
  `Recovered_2` text,
  `creation_time` text,
  `sh_tag` text,
  KEY `Individual_Link_ID` (`Individual_Link_ID`(10)),
  KEY `Individual_ID` (`Individual_ID`(10)),
  KEY `AV_title_id` (`AV_title_id`(10)),
  KEY `Article_ID` (`Article_ID`(10)),
  KEY `BKS_ID` (`BKS_ID`(10)),
  KEY `AV_partID` (`AV_partID`(10)),
  KEY `final_ID` (`final_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_l_indiv_shk_notshk`
--

CREATE TABLE IF NOT EXISTS `temp_l_indiv_shk_notshk` (
  `Individual_Link_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Name` text,
  `Individual_ID` text,
  `Position` text,
  `BKS_ID` text,
  `AV_partID` text,
  `Linked_book_title` text,
  `Linked_AV_Part_Title` text,
  `book_type` text,
  `final_type` text,
  `final_titile` text,
  `final_ID` text,
  `AV_title_id` text,
  `Linked_AV_main_Title` text,
  `constant` text,
  `flat_info` text,
  `cSumTextCalc` text,
  `global` text,
  `Article_ID` text,
  `Linked_article_title` text,
  `play_id` text,
  `role` text,
  `sh_director_calc` text,
  `sh_cast_name` text,
  `Recovered_1` text,
  `Recovered_2` text,
  `creation_time` text,
  `sh_tag` text,
  KEY `Individual_Link_ID` (`Individual_Link_ID`(10)),
  KEY `Individual_ID` (`Individual_ID`(10)),
  KEY `AV_title_id` (`AV_title_id`(10)),
  KEY `Article_ID` (`Article_ID`(10)),
  KEY `BKS_ID` (`BKS_ID`(10)),
  KEY `AV_partID` (`AV_partID`(10)),
  KEY `final_ID` (`final_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_orphaned_avpart_indiv`
--

CREATE TABLE IF NOT EXISTS `temp_orphaned_avpart_indiv` (
  `Individual_Link_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Name` text,
  `Individual_ID` text,
  `Position` text,
  `BKS_ID` text,
  `AV_partID` text,
  `Linked_book_title` text,
  `Linked_AV_Part_Title` text,
  `book_type` text,
  `final_type` text,
  `final_titile` text,
  `final_ID` text,
  `AV_title_id` text,
  `Linked_AV_main_Title` text,
  `constant` text,
  `flat_info` text,
  `cSumTextCalc` text,
  `global` text,
  `Article_ID` text,
  `Linked_article_title` text,
  `play_id` text,
  `role` text,
  `sh_director_calc` text,
  `sh_cast_name` text,
  `Recovered_1` text,
  `Recovered_2` text,
  `creation_time` text,
  `sh_tag` text,
  KEY `Individual_Link_ID` (`Individual_Link_ID`(10)),
  KEY `Individual_ID` (`Individual_ID`(10)),
  KEY `AV_title_id` (`AV_title_id`(10)),
  KEY `Article_ID` (`Article_ID`(10)),
  KEY `BKS_ID` (`BKS_ID`(10)),
  KEY `AV_partID` (`AV_partID`(10)),
  KEY `final_ID` (`final_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_orphaned_avtitle_avpart`
--

CREATE TABLE IF NOT EXISTS `temp_orphaned_avtitle_avpart` (
  `unique_id` int(11) NOT NULL auto_increment,
  `Part_ID` text,
  `AV_ID` text,
  `Part_title` text,
  `series_title` text,
  `Part_unnumbered_series_title` text,
  `Part_number_in_series` text,
  `Part_numbered_part_title` text,
  `Part_unnumbered_part_title` text,
  `Part_numbered_part` text,
  `Playing_time` text,
  `Colour_BW` text,
  `Sound_Mute` text,
  `Synopsis_part` text,
  `Part_Notes` text,
  `contributor_name` text,
  `contribution_type` text,
  `recognition_field` text,
  `av_title` text,
  `global_orginal_code` text,
  `global_new_code` text,
  `constant` text,
  `tag` text,
  `creation_date` text,
  `modification_date` text,
  `w_freetext_general` text,
  `w_freetext_title` text,
  `w_freetext_synopsis` text,
  `w_cTextSumCalcFreetextGeneral` text,
  `w_cTextSumCalcFreetextTitle` text,
  `w_cTextSumCalcFreetextsynopsis` text,
  `parts_check` text,
  `Distributor_reference` text,
  `Part_ISBN` text,
  `AV_Parts_production_date` text,
  `last_mod_date_global` text,
  `sh_constant` text,
  `sh_transmission_date` text,
  `sh_transmission_date_accuracy` text,
  `sh_transmission_date_calc` text,
  `sh_recording_date` text,
  `sh_recording_date_accuracy` text,
  `sh_recording_date_calc` text,
  `sh_transmission_time` text,
  `sh_duration_mins` text,
  `sh_duration_feet` text,
  `g_part_no_in_series` text,
  `g_part_synopsis` text,
  `Creator` text,
  `part_count` text,
  PRIMARY KEY  (`unique_id`),
  KEY `AV_ID` (`AV_ID`(10)),
  KEY `Part_ID` (`Part_ID`(10))
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=52954 ;

-- --------------------------------------------------------

--
-- Table structure for table `temp_orphaned_avtitle_indiv`
--

CREATE TABLE IF NOT EXISTS `temp_orphaned_avtitle_indiv` (
  `Individual_Link_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Name` text,
  `Individual_ID` text,
  `Position` text,
  `BKS_ID` text,
  `AV_partID` text,
  `Linked_book_title` text,
  `Linked_AV_Part_Title` text,
  `book_type` text,
  `final_type` text,
  `final_titile` text,
  `final_ID` text,
  `AV_title_id` text,
  `Linked_AV_main_Title` text,
  `constant` text,
  `flat_info` text,
  `cSumTextCalc` text,
  `global` text,
  `Article_ID` text,
  `Linked_article_title` text,
  `play_id` text,
  `role` text,
  `sh_director_calc` text,
  `sh_cast_name` text,
  `Recovered_1` text,
  `Recovered_2` text,
  `creation_time` text,
  `sh_tag` text,
  KEY `Individual_Link_ID` (`Individual_Link_ID`(10)),
  KEY `Individual_ID` (`Individual_ID`(10)),
  KEY `AV_title_id` (`AV_title_id`(10)),
  KEY `Article_ID` (`Article_ID`(10)),
  KEY `BKS_ID` (`BKS_ID`(10)),
  KEY `AV_partID` (`AV_partID`(10)),
  KEY `final_ID` (`final_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_orphaned_l_comp`
--

CREATE TABLE IF NOT EXISTS `temp_orphaned_l_comp` (
  `Company_Link_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Company_Name` text,
  `Company_ID` text,
  `Position` text,
  `BKS_ID` text,
  `AV_ID` text,
  `Linked_book_title` text,
  `Linked_AV_Title` text,
  `final_titile` text,
  `book_type` text,
  `final_type` text,
  `final_ID` text,
  `Company_ID_Copy` text,
  `constant` text,
  `Linked_full_address` text,
  `flat_companies` text,
  `cSumTextCalc` text,
  `sh_tag` text,
  KEY `Company_Link_ID` (`Company_Link_ID`(10)),
  KEY `Company_ID` (`Company_ID`(10)),
  KEY `AV_ID` (`AV_ID`(10)),
  KEY `BKS_ID` (`BKS_ID`(10)),
  KEY `final_ID` (`final_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_orphaned_l_country`
--

CREATE TABLE IF NOT EXISTS `temp_orphaned_l_country` (
  `av_id` text,
  `country_code` text,
  `country` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_orphaned_l_subjects`
--

CREATE TABLE IF NOT EXISTS `temp_orphaned_l_subjects` (
  `AV_Subject_ID` text,
  `BJC_Subject_ID` text,
  `TRILT_ID` text,
  `MIG_ID` text,
  `RGO_ID` text,
  `BUND_ID` text,
  `Final_ID_Subject` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Related_format_BJC__subject` text,
  `Related_Format_Final_subject` text,
  `Related_subject_title_BJC` text,
  `Related_title_final_subject` text,
  `Term` text,
  `Subect_Link_ID` text,
  `Subject_ID` text,
  `constant` text,
  `cSumTextCalc_subjects` text,
  `AV_title` text,
  `TRILT_title` text,
  `RGO_title` text,
  `MIG_title` text,
  `Article_ID` text,
  `Article_title` text,
  `AV_Part_Subject_ID` text,
  `AV_Part_title` text,
  `Viefinder_ID` text,
  KEY `AV_Subject_ID` (`AV_Subject_ID`(10)),
  KEY `TRILT_ID` (`TRILT_ID`(10)),
  KEY `MIG_ID` (`MIG_ID`(10)),
  KEY `RGO_ID` (`RGO_ID`(10)),
  KEY `BUND_ID` (`BUND_ID`(10)),
  KEY `BJC_Subject_ID` (`BJC_Subject_ID`(10)),
  KEY `Subect_Link_ID` (`Subect_Link_ID`(10)),
  KEY `Subject_ID` (`Subject_ID`(10)),
  KEY `Article_ID` (`Article_ID`(10)),
  KEY `Final_ID_Subject` (`Final_ID_Subject`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_orphaned_l_thes_titles`
--

CREATE TABLE IF NOT EXISTS `temp_orphaned_l_thes_titles` (
  `Article_ID` text,
  `AV_ID` text,
  `AV_title` text,
  `BJC_ID` text,
  `constant` text,
  `cSumTextCalc_thes` text,
  `Final_ID` text,
  `Record_creation_date` text,
  `Record_modification_date` text,
  `Related_format_BJC` text,
  `Related_Format_Final` text,
  `Related_title_BJC` text,
  `Related_title_final` text,
  `Term` text,
  `Thes_Link_ID` text,
  `Thesaurus_ID` text,
  `VF_calc_ID` text,
  `VF_item_ID` text,
  `VF_item_subject_ID` text,
  `VF_item_title` text,
  `VF_item_title_subject` text,
  `Related_title_Article` text,
  `Part_ID` text,
  `AV_Part_title` text,
  `custom_sort_tag` text,
  `Viewfinder_ID` text,
  `Tagged_authors_calc` text,
  KEY `Thesaurus_ID` (`Thesaurus_ID`(10)),
  KEY `Thes_Link_ID` (`Thes_Link_ID`(10)),
  KEY `AV_ID` (`AV_ID`(10)),
  KEY `Article_ID` (`Article_ID`(10)),
  KEY `BJC_ID` (`BJC_ID`(10)),
  KEY `Part_ID` (`Part_ID`(10)),
  KEY `Final_ID` (`Final_ID`(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_val_country`
--

CREATE TABLE IF NOT EXISTS `temp_val_country` (
  `label` varchar(100) default NULL,
  `value` varchar(50) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_val_language`
--

CREATE TABLE IF NOT EXISTS `temp_val_language` (
  `label` varchar(100) default NULL,
  `value` varchar(50) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_val_pers_type`
--

CREATE TABLE IF NOT EXISTS `temp_val_pers_type` (
  `value` int(11) default NULL,
  `label` varchar(100) default NULL,
  `newlabel` varchar(100) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_val_title_type`
--

CREATE TABLE IF NOT EXISTS `temp_val_title_type` (
  `value` int(11) default NULL,
  `label` varchar(100) default NULL,
  `newlabel` varchar(100) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `title`
--

CREATE TABLE IF NOT EXISTS `title` (
  `id` int(11) NOT NULL auto_increment,
  `data_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `data_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `data_status` int(1) NOT NULL default '5',
  `data_author` varchar(40) default NULL,
  `data_set` varchar(60) default NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(40) default NULL,
  `date1` date default NULL,
  `date2` date default NULL,
  `legacy_date` varchar(100) default NULL,
  `created1` date default NULL,
  `created2` date default NULL,
  `legacy_created` varchar(100) default NULL,
  `language` varchar(50) default NULL,
  `description` text,
  `notes` text,
  `legacy_id` varchar(20) default NULL,
  `alternative` varchar(255) default NULL,
  `coverage` varchar(50) default NULL,
  `rights` varchar(255) default NULL,
  `source` varchar(255) default NULL,
  `misc` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `legacy_id` (`legacy_id`),
  KEY `data_status` (`data_status`),
  KEY `title` (`title`),
  KEY `data_set` (`data_set`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=85881 ;

-- --------------------------------------------------------

--
-- Table structure for table `title_av`
--

CREATE TABLE IF NOT EXISTS `title_av` (
  `id` int(11) NOT NULL,
  `country` varchar(50) default NULL,
  `distributors_ref` varchar(255) default NULL,
  `isbn` varchar(100) default NULL,
  `shelf_ref` varchar(255) default NULL,
  `doc_included` tinyint(1) default NULL,
  `doc_separate` tinyint(1) default NULL,
  `doc_notes` text,
  `uses` varchar(255) default NULL,
  `ref` varchar(75) default NULL,
  `title_series` varchar(255) default NULL,
  `title_sub` varchar(255) default NULL,
  `video_colour` tinyint(1) default NULL,
  `video_bw` tinyint(1) default NULL,
  `audio_sound` tinyint(1) default NULL,
  `audio_silent` tinyint(1) default NULL,
  `audio_silentmusic` tinyint(1) default NULL,
  `indexed_notes` varchar(255) default NULL,
  `legacy_price` varchar(255) default NULL,
  `legacy_physical_description` varchar(255) default NULL,
  `availability` varchar(150) default NULL,
  `director` text NOT NULL,
  `producer` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `title_avpart`
--

CREATE TABLE IF NOT EXISTS `title_avpart` (
  `id` int(11) NOT NULL,
  `isbn` varchar(50) default NULL,
  `number_in_series` varchar(20) default NULL,
  `legacy_contributor_name` varchar(255) default NULL,
  `legacy_contribution_type` varchar(255) default NULL,
  `duration` varchar(20) default NULL,
  `video_colour` tinyint(1) default NULL,
  `video_bw` tinyint(1) default NULL,
  `audio_sound` tinyint(1) default NULL,
  `audio_silent` tinyint(1) default NULL,
  `audio_silentmusic` tinyint(1) default NULL,
  `distributors_ref` varchar(255) default NULL,
  `legacy_part_id` varchar(20) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `title_avpart_shk`
--

CREATE TABLE IF NOT EXISTS `title_avpart_shk` (
  `id` int(11) NOT NULL,
  `duration_feet` varchar(50) default NULL,
  `duration_mins` varchar(50) default NULL,
  `date_transmission` date default NULL,
  `transmission_time` char(5) default NULL,
  `date_recording` date default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `title_av_shk`
--

CREATE TABLE IF NOT EXISTS `title_av_shk` (
  `id` int(11) NOT NULL,
  `duration_feet` varchar(50) default NULL,
  `duration_mins` varchar(50) default NULL,
  `date_transmission` date default NULL,
  `time_transmission` time default NULL,
  `play` varchar(255) default NULL,
  `date_recording` date default NULL,
  `play_cat` int(11) default NULL,
  `reviews` text,
  `notes_history` text,
  `notes_figures` text,
  `notes_awards` text,
  `notes_stills` text,
  `notes_general` text,
  `notes_textual_info` text,
  `notes_user_comments` text,
  `theatre_company` varchar(255) default NULL,
  `theatre` varchar(255) default NULL,
  `prod_type` int(11) default NULL,
  `broadcast_channel` int(11) default NULL,
  `title_override` varchar(255) default NULL,
  `date_approx` tinyint(1) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `val_catalogue_type`
--

CREATE TABLE IF NOT EXISTS `val_catalogue_type` (
  `value` int(11) NOT NULL auto_increment,
  `label` varchar(100) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `val_country`
--

CREATE TABLE IF NOT EXISTS `val_country` (
  `value` char(10) NOT NULL,
  `label` varchar(50) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `val_data_set`
--

CREATE TABLE IF NOT EXISTS `val_data_set` (
  `value` char(10) NOT NULL,
  `label` varchar(50) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `val_language`
--

CREATE TABLE IF NOT EXISTS `val_language` (
  `value` char(10) NOT NULL,
  `label` varchar(50) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `val_org_relation`
--

CREATE TABLE IF NOT EXISTS `val_org_relation` (
  `value` int(11) NOT NULL auto_increment,
  `label` varchar(100) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Table structure for table `val_org_type`
--

CREATE TABLE IF NOT EXISTS `val_org_type` (
  `value` int(11) NOT NULL auto_increment,
  `label` varchar(100) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `val_pers_type`
--

CREATE TABLE IF NOT EXISTS `val_pers_type` (
  `value` int(11) NOT NULL auto_increment,
  `label` varchar(100) default NULL,
  `data_set` varchar(60) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=25 ;

-- --------------------------------------------------------

--
-- Table structure for table `val_reldesc`
--

CREATE TABLE IF NOT EXISTS `val_reldesc` (
  `value` varchar(50) NOT NULL,
  `label` varchar(50) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `val_shk_broadcastchannel`
--

CREATE TABLE IF NOT EXISTS `val_shk_broadcastchannel` (
  `value` int(11) NOT NULL auto_increment,
  `label` varchar(100) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=203 ;

-- --------------------------------------------------------

--
-- Table structure for table `val_shk_period`
--

CREATE TABLE IF NOT EXISTS `val_shk_period` (
  `value` int(11) NOT NULL auto_increment,
  `label` varchar(100) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=27 ;

-- --------------------------------------------------------

--
-- Table structure for table `val_shk_play`
--

CREATE TABLE IF NOT EXISTS `val_shk_play` (
  `value` int(11) NOT NULL,
  `label` varchar(50) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `val_shk_playcat`
--

CREATE TABLE IF NOT EXISTS `val_shk_playcat` (
  `value` int(11) NOT NULL auto_increment,
  `label` text,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Table structure for table `val_shk_playrole`
--

CREATE TABLE IF NOT EXISTS `val_shk_playrole` (
  `value` int(11) NOT NULL auto_increment,
  `label` varchar(50) default NULL,
  `play` int(11) NOT NULL,
  PRIMARY KEY  (`value`),
  UNIQUE KEY `prevent_duplicates` (`label`,`play`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1251 ;

-- --------------------------------------------------------

--
-- Table structure for table `val_shk_productiontype`
--

CREATE TABLE IF NOT EXISTS `val_shk_productiontype` (
  `value` int(11) NOT NULL auto_increment,
  `label` varchar(100) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Table structure for table `val_shk_reviews`
--

CREATE TABLE IF NOT EXISTS `val_shk_reviews` (
  `value` int(11) NOT NULL auto_increment,
  `label` text,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Table structure for table `val_title_type`
--

CREATE TABLE IF NOT EXISTS `val_title_type` (
  `value` int(11) NOT NULL auto_increment,
  `label` varchar(100) default NULL,
  PRIMARY KEY  (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=24 ;


#####
# Legacy data for testing
#####

# Title
INSERT INTO `title` (`id`, `data_modified`, `data_created`, `data_status`, `data_author`, `data_set`, `title`, `type`, `date1`, `date2`, `legacy_date`, `created1`, `created2`, `legacy_created`, `language`, `description`, `notes`, `legacy_id`, `alternative`, `coverage`, `rights`, `source`, `misc`) VALUES
(1, '2008-12-17 16:47:09', '1999-09-09 06:00:00', 5, 'Legacy', 'av', '3M''S BEDFORD DISTRIBUTION CENTRE', '19', NULL, NULL, '', '1995-00-00', NULL, '1995', 'en', 'A case study showing how a large-sized business has used good business practices to enhance its competitive edge. 3M Bedford Distribution Centre, with 4400 employees it its UK organisation, is an important part of the 3M UK operation and is changing to offer customers tailored services that fit the new requirements of low inventory and flow-through product.', '', '1', '', NULL, NULL, NULL, ' @@ 3M''S BEDFORD DISTRIBUTION CENTRE @@ A case study showing how a large-sized business has used good business practices to enhance its competitive edge. 3M Bedford Distribution Centre, with 4400 employees it its UK organisation, is an important part of the 3M UK operation and is changing to offer customers tailored services that fit the new requirements of low inventory and flow-through product. @@  @@  @@ Video @@ English @@ Connect for better business, series @@  Moran, Sherry  @@ Business studies @@ business firms'),
(2, '2008-12-17 16:47:09', '1997-09-23 06:00:00', 5, 'Legacy', 'av', '5S: VISUAL CONTROL SYSTEMS', '19,15', NULL, NULL, '', '0199-00-00', NULL, '199', 'en-gb', 'Looks in detail at the visual control of the workplace covering the storage, operations, equipment, quality and safety. Offers concrete examples of visual control in action in Japanese manufacturng plants.', '', '10', '', NULL, NULL, NULL, ' @@ 5S: VISUAL CONTROL SYSTEMS @@ Looks in detail at the visual control of the workplace covering the storage, operations, equipment, quality and safety. Offers concrete examples of visual control in action in Japanese manufacturng plants. @@  @@  @@ Video @@ English (Great Britain) @@  @@ Japan @@ manufacturing industries @@ Engineering @@ control systems'),
(3, '2008-12-17 16:46:37', '1993-04-02 06:00:00', 5, 'Legacy', 'av,av_shk', '1930 TO TOMORROW', NULL, NULL, NULL, '', NULL, NULL, '', 'en-gb', '', '', '100', '', NULL, NULL, NULL, ' @@ 1930 TO TOMORROW @@  @@  @@  @@ English (Great Britain) @@ Together (2 parts)'),
(24188, '2008-12-17 16:47:09', '2002-01-09 07:00:00', 5, 'Legacy', 'av', 'A MAN IN A ROOM GAMBLING', '15', NULL, NULL, '', '1997-00-00', NULL, '1997', 'en-gb', 'An audio recording of a performance, originally broadcast on BBC radio, in which British composer Gavin Bryars and Spanish sculptor Juan Muñoz conceived a series of five-minute pieces about card tricks. Muñoz narrates each trick from the preparation of the pack to the manipulation and revelation, whilst Bryars'' music orchestrates the deception and intensifies the duplicity Muñoz describes. Performed by The Gavin Bryars Ensemble and released on Phillip Glass''s Point Music label, the CD features a number of other compositions by Bryars, including the North Downs and the South Downs, for piano, cello and viola. The CD includes sleeve notes by Gavin Bryars. 73 minutes', '', 'AV34238', '', NULL, NULL, NULL, ' @@ A MAN IN A ROOM GAMBLING @@ An audio recording of a performance, originally broadcast on BBC radio, in which British composer Gavin Bryars and Spanish sculptor Juan Muñoz conceived a series of five-minute pieces about card tricks. Muñoz narrates each trick from the preparation of the pack to the manipulation and revelation, whilst Bryars'' music orchestrates the deception and intensifies the duplicity Muñoz describes. Performed by The Gavin Bryars Ensemble and released on Phillip Glass''s Point Music label, the CD features a number of other compositions by Bryars, including the North Downs and the South Downs, for piano, cello and viola. The CD includes sleeve notes by Gavin Bryars. 73 minutes @@  @@  @@ Audio recording @@ English (Great Britain) @@  @@ Great Britain @@ Art @@ radio @@ installation art @@ Music'),
(32863, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '36350', NULL, NULL, NULL, NULL, ' @@  @@  @@ '),
(62575, '2008-12-17 16:47:00', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', 'A Man in a room gambling', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'description', 'note''s', '49584', NULL, NULL, NULL, NULL, ' @@ A Man in a room gambling @@  @@  @@  Munoz, Juan  @@  Bryars, Gavin '),
(62576, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '49585', NULL, NULL, NULL, NULL, ' @@  @@  @@ ');

# Title - Part records needing special parsing
INSERT INTO `title` (`id`, `data_modified`, `data_created`, `data_status`, `data_author`, `data_set`, `title`, `type`, `date1`, `date2`, `legacy_date`, `created1`, `created2`, `legacy_created`, `language`, `description`, `notes`, `legacy_id`, `alternative`, `coverage`, `rights`, `source`, `misc`) VALUES
(66011, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '2', NULL, NULL, NULL, NULL, ' @@ 1 @@  @@ '),
(66012, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', 'Performing a workplace scan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '3', NULL, NULL, NULL, NULL, ' @@ Performing a workplace scan @@  @@ '),
(66013, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '4', NULL, NULL, NULL, NULL, ' @@ 2 @@  @@ '),
(66014, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', 'S-1: sort', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '5', NULL, NULL, NULL, NULL, ' @@ S-1: sort @@  @@ '),
(66015, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '6', NULL, NULL, NULL, NULL, ' @@ 3 @@  @@ '),
(66016, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', 'S-2: set in order', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '7', NULL, NULL, NULL, NULL, ' @@ S-2: set in order @@  @@ '),
(66023, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '14', NULL, NULL, NULL, NULL, ' @@ 7 @@  @@ '),
(58556, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1: Training for leaders in the skills and determination to steer their teams in the right direction and improve overall performance.', '', '45565', NULL, NULL, NULL, NULL, ' @@  @@ 1: Training for leaders in the skills and determination to steer their teams in the right direction and improve overall performance. @@ '),
(58557, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2: Building a team of diverse personalities with different roles who can work together with good communication and under pressure.', '', '45566', NULL, NULL, NULL, NULL, ' @@  @@ 2: Building a team of diverse personalities with different roles who can work together with good communication and under pressure. @@ '),
(58558, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3: Importance of the right staff, techniques for selecting team members, and interview skills.', '', '45567', NULL, NULL, NULL, NULL, ' @@  @@ 3: Importance of the right staff, techniques for selecting team members, and interview skills. @@ '),
(66037, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', 'Better team performance', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '28', NULL, NULL, NULL, NULL, ' @@ Better team performance @@  @@ '),
(66038, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '29', NULL, NULL, NULL, NULL, ' @@ 1 @@  @@ '),
(66039, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', 'Creating and effective team', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '30', NULL, NULL, NULL, NULL, ' @@ Creating and effective team @@  @@ '),
(66040, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '31', NULL, NULL, NULL, NULL, ' @@ 2 @@  @@ '),
(66041, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', 'Picking the right people', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '32', NULL, NULL, NULL, NULL, ' @@ Picking the right people @@  @@ '),
(66042, '2008-12-17 16:46:25', '0000-00-00 00:00:00', 5, 'Legacy', 'avpart', '3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '33', NULL, NULL, NULL, NULL, ' @@ 3 @@  @@ ');

# Title A/V
INSERT INTO `title_av` (`id`, `country`, `distributors_ref`, `isbn`, `shelf_ref`, `doc_included`, `doc_separate`, `doc_notes`, `uses`, `ref`, `title_series`, `title_sub`, `video_colour`, `video_bw`, `audio_sound`, `audio_silent`, `audio_silentmusic`, `indexed_notes`, `legacy_price`, `legacy_physical_description`, `availability`, `director`, `producer`) VALUES
(1, 'gb,us', '', '', '', 0, 0, '', '', 'eng trilt VF25T vf37', 'Connect for better business, series', '', 0, 0, 0, 0, 0, '', '1999 sale: £39.50 (+VAT +p&p)', 'Videocassette. VHS. col. 10 min.', 'Sale', ' Moran, Sherry ', ' Moran, Sherry '),
(2, 'jp', '', '', '', 0, 0, 'Accompanying instructor''s guide.', '', 'vf32++', '', '', 0, 0, 0, 0, 0, '', '1997 sale: £750.00 (+VAT)', '3 videocassettes. VHS. col. 90 min.', 'Sale', '', ''),
(3, NULL, '', '', '', 0, 0, '', '', '', 'Together (2 parts)', '', 0, 0, 0, 0, 0, '', '', '', '', '', ''),
(24188, 'gb', 'AA31', '', '', 1, 0, '', '', 'vf 47', '', '', 0, 0, 0, 0, 0, '', '', 'Compact disc.', 'Sale', '', '');

# Title AV Part
INSERT INTO `title_avpart` (`id`, `isbn`, `number_in_series`, `legacy_contributor_name`, `legacy_contribution_type`, `duration`, `video_colour`, `video_bw`, `audio_sound`, `audio_silent`, `audio_silentmusic`, `distributors_ref`, `legacy_part_id`) VALUES
(32863, '', '', ' Moran, Sherry', 'Producer of', '', 0, 0, 0, 0, 0, '', '1'),
(62575, '1 887943 57 9', '1', '', '', '1:13', 1, 0, 0, 1, 0, 'FHEV 1453 (video)'' FHED 1454', 'P113418'),
(62576, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P113419'),
(66011, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30677'),
(66012, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30678'),
(66013, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30679'),
(66014, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30680'),
(66015, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30681'),
(66016, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30682'),
(66023, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30689'),
(58556, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P109142'),
(58557, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P109143'),
(58558, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P109144'),
(66037, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30715'),
(66038, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30716'),
(66039, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30717'),
(66040, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30718'),
(66041, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30719'),
(66042, '', '', '', '', '', 0, 0, 0, 0, 0, '', 'P30720');

# legacy_AV_titles
INSERT INTO `legacy_AV_titles` (`Audio_format`, `Audio_Price`, `Audio_Sale_Hire`, `Audio_time`, `Audio_Year`, `Audio_Yes`, `AV_Alternative_title`, `AV_availability`, `AV_Catalogued_date`, `AV_Catalogued_Time`, `AV_Cataloguers_notes`, `AV_country`, `AV_distributors_ref_no`, `AV_documentation_included`, `AV_documentation_notes`, `AV_ID`, `AV_indexed_notes`, `AV_ISBN_Number`, `AV_Language`, `AV_Medium`, `AV_modification_date`, `AV_Notes`, `AV_physical_description`, `AV_price`, `AV_production_company_companies_link`, `AV_Production_Date`, `AV_Release_date`, `AV_Series_Title`, `AV_Shelf_Reference`, `AV_Source`, `AV_Synopsis`, `AV_title`, `AV_Uses`, `Recovered_3`, `cd_rom_price`, `Recovered_11`, `cd_rom_year`, `Recovered_7`, `CDROM_time`, `Colour_BW`, `constant`, `Recovered_2`, `dvd_price`, `Recovered_10`, `DVD_time`, `dvd_year`, `Recovered_6`, `DVD_format2`, `DVD_format3`, `Recovered_4`, `film_price`, `Film_Sale_Hire`, `Film_time`, `film_year`, `Recovered_8`, `flat_companies`, `flat_individuals`, `flat_parts_freetext_general`, `flat_parts_freetext_synopsis`, `flat_parts_freetext_title`, `flat_subject_terms`, `flat_thesaurus_terms`, `global_new_code`, `global_original_code`, `online`, `Slide_Tape_Format`, `Slide_Tape_number`, `Slide_Tape_Price`, `Slide_Tape_Sale_Hire`, `Slide_Tape_time`, `Slide_Tape_Year`, `Slide_Tape_Yes`, `Sound_Mute`, `subtitle`, `tag`, `Recovered_1`, `video_price`, `Recovered_9`, `Video_time`, `Video_year`, `Recovered_5`, `w_format_calc`, `w_individuals`, `w_synopsis`, `Foundset_summary`, `video_price_vat_pp`, `cd_rom_price_vat_pp`, `dvd_price_vat_pp`, `flm_price_vat_pp`, `audio_price_vat_pp`, `slide_tape_price_vat_pp`, `last_mod_date_global`, `web_tag`, `On_line_yes`, `On_line_format`, `On_line_player`, `On_line_url`, `On_line_price`, `tinlib`, `av_title_CAPS`, `thesaurus_input_filter`, `Categories_tag_field`, `tagged_author_calc`, `sh_duration_mins`, `sh_duration_feet`, `sh_play_cat`, `sh_period`, `sh_transmission_date`, `sh_transmission_date_accuracy`, `sh_recording_date_accuracy`, `sh_recording_date_calc`, `sh_transmission_time`, `sh_reviews`, `sh_reviews_global`, `Sh_constant`, `sh_play_id`, `sh_history`, `sh_theatre_company`, `sh_theatre`, `sh_textual_info`, `sh_figures`, `sh_awards`, `sh_stills`, `sh_general_notes`, `sh_user_comments`, `sh_prod_type`, `sh_indiv_director`, `sh_transmission_date_calc`, `sh_recording_date`, `creator`, `part_count`, `record_count`, `sh_broadcast_channel`, `record_nav_global`, `sh_title_calc`, `sh_title_overide`, `sh_approx_date_flag`, `sh_sort_field`, `sh_sort_field_without_carriage_returns`) VALUES
('Cassette', '£9.99', 'Sale', '150 mimutes', '2004', 'Audio', '', 'Sale', '9/9/1999', '12:13:04 pm', '', '', '', '', '', '1', '', '', 'en', 'Video', '02/12/2008', '', 'Videocassette. VHS. col. 10 min.', '1999 sale: £39.50 (+VAT +p&p)', '', '1995', '', 'Connect for better business, series', '', '', 'A case study showing how a large-sized business has used good business practices to enhance its competitive edge. 3M Bedford Distribution Centre, with 4400 employees it its UK organisation, is an important part of the 3M UK operation and is changing to offer customers tailored services that fit the new requirements of low inventory and flow-through product.', '3M''S BEDFORD DISTRIBUTION CENTRE', '', 'Hybrid', '£99.00 - £250.00 each', 'Sale', '2005', '', '10 discs', '', '1', 'Region 2 NTSC', '$9.99', 'Sale', '67 minutes', '2003', 'VCD', 'Region 0', 'Region 0', '16mm', '£30.00', 'Hire', '92 minutes', '2004', 'Film', 'Tribeca (Distributor); Tribeca Productions (Production Company); Department of Trade and Industry, Best Practice Division (Sponsor)', 'Moran, Sherry (Producer); Moran, Sherry (Director); Moran, Sherry (Writer)', '', '', '', '', '', '', '', 'Online', 'Slide Set', '80', '£80.00', '', '', '2005', 'Slide/Tape', '', '', '', 'PAL', '£13.00 (hire)£80.00 (sale)', 'SaleHire', '2 x 55 minutes', '2005', 'VHS', 'Videocassette. VHS. col. 10 min. 1999 sale: £39.50 (+VAT +p&p). Sale', '?', 'A case study showing how a large-sized business has used good business practices to enhance its competitive edge. 3M...', '32862', '', '', '', '', '', '', '', '', 'On-line', 'Download', 'QuicktimeWMP', 'www.emol.ac.uk', 'free to UK HE and FE', '', '3M''S BEDFORD DISTRIBUTION CENTRE', '', '', '1author', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Director1', '', '', 'sysadmin', '1', '4260', '', '', '3M''S BEDFORD DISTRIBUTION CENTRE', '', '', '3M''S BEDFORD DISTRIBUTION CENTRE(1)', '3M''S BEDFORD DISTRIBUTION CENTRE(1)'),
('', '', '', '', '', '', '', 'Sale', '23/9/1997', '12:13:04 pm', '', 'Japan', '', '', 'Accompanying instructor''s guide.', '10', '', '', 'en-gb', 'Video', '20/06/2008', '', '3 videocassettes. VHS. col. 90 min.', '1997 sale: £750.00 (+VAT)', '', '199', '', '', '', 'vf32', 'Looks in detail at the visual control of the workplace covering the storage, operations, equipment, quality and safety. Offers concrete examples of visual control in action in Japanese manufacturng plants.', '5S: VISUAL CONTROL SYSTEMS', '', '', '', '', '', '', '', '', '1', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Productivity Europe Ltd (Distributor); PHP Institute (Production Company)', '', '', '', '', '', '', '', '', 'Online', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '3 videocassettes. VHS. col. 90 min. 1997 sale: £750.00 (+VAT). Sale', '', 'Looks in detail at the visual control of the workplace covering the storage, operations, equipment, quality and safety. Offers...', '32862', '', '', '', '', '', '', '', '', 'On-line', 'StreamedDownload', 'WMP', 'http://environment.uwe.ac.uk/video/wmv.html', '£34.99', '', '5S: VISUAL CONTROL SYSTEMS', '', '', '10author', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Director10', '', '', 'sysadmin', '', '4260', '', '', '5S: VISUAL CONTROL SYSTEMS', '', '', '', ''),
('', '', '', '', '', '', '', '', '2/4/1993', '12:13:04 pm', '', '', '', '', '', '100', '', '', 'en-gb', '', '20/06/2008', '', '', '', '', '', '', 'Together (2 parts)', '', '', '', '1930 TO TOMORROW', '', '', '', '', '', '', '', '', '1', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Online', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '32862', '', '', '', '', '', '', '', '', '', '', '', 'junk url', '', '', '1930 TO TOMORROW', '', '', '100author', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Director100', '', '', 'sysadmin', '', '4260', '', '', '1930 TO TOMORROW', '', '', '', ''),
('CD', '£14.95 (inclusive)', 'Sale', '', '2002 ', 'Audio', '', 'Sale', '9/1/2002', '11:15:14 am', '', 'GB', 'AA31', 'Included', '', 'AV34238', '', '', 'en-gb', 'Sound recording', '20/06/2008', '', 'Compact disc.', '', '', '1997', '', '', '', 'vf47', 'An audio recording of a performance, originally broadcast on BBC radio, in which British composer Gavin Bryars and Spanish sculptor Juan Muñoz conceived a series of five-minute pieces about card tricks. Muñoz narrates each trick from the preparation of the pack to the manipulation and revelation, whilst Bryars'' music orchestrates the deception and intensifies the duplicity Muñoz describes. Performed by The Gavin Bryars Ensemble and released on Phillip Glass''s Point Music label, the CD features a number of other compositions by Bryars, including the North Downs and the South Downs, for piano, cello and viola. The CD includes sleeve notes by Gavin Bryars. 73 minutes', 'A MAN IN A ROOM GAMBLING', '', '', '', '', '', '', '', '', '1', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Artangel (Distributor); Point Films (Publisher); Artangel (Sponsor)', 'Bryars, Gavin (Performer); Munoz, Juan (Writer); Munoz, Juan (Performer)', '', '', '', '', '', '', '', 'Online', '', '', '', '', '', '', '', '', '', '', 'PAL', '£13.00 (hire)£80.00 (sale)', 'SaleHire', '2 x 55 minutes', '2005', 'Video Recording', '2002 . GB. Audio (CD). Price: £14.95 (inclusive). ', '?', 'An audio recording of a performance, originally broadcast on BBC radio, in which British composer Gavin Bryars and Spanish...', '32862', '', '', '', '', '', '', '', 'new_format', '', '', '', '', '', '', 'A MAN IN A ROOM GAMBLING', '', '', 'AV34238author', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'DirectorAV34238', '', '', 'sysadmin', '2', '4260', '', '', 'A MAN IN A ROOM GAMBLING', '', '', '', '');

# Keyword
INSERT INTO `keyword` (`id`, `data_modified`, `data_created`, `data_status`, `data_author`, `data_set`, `term`, `description`, `legacy_id`) VALUES
(1, '2003-04-07 06:00:00', '2001-12-13 07:00:00', 5, 'Legacy', 'hrmsubj', 'Agriculture', NULL, ' 3'),
(2, '2003-04-07 06:00:00', '2001-12-13 07:00:00', 5, 'Legacy', 'hrmsubj', 'Anthropology', NULL, ' 4'),
(12, '2003-04-07 06:00:00', '2001-12-13 07:00:00', 5, 'Legacy', 'hrmsubj', 'Business studies', NULL, ' 16'),
(31, '2003-04-07 06:00:00', '2001-12-13 07:00:00', 5, 'Legacy', 'hrmsubj', 'Engineering', NULL, ' 35'),
(1317, '2005-11-04 07:00:00', '2001-12-13 07:00:00', 5, 'Legacy', 'hrmthes', 'business firms', NULL, '960'),
(2038, '2005-11-04 07:00:00', '2001-12-13 07:00:00', 5, 'Legacy', 'hrmthes', 'control systems', NULL, '1590'),
(5314, '2005-11-04 07:00:00', '2001-12-13 07:00:00', 5, 'Legacy', 'hrmthes', 'manufacturing industries', NULL, '4283'),
(9424, '2005-11-04 07:00:00', '2001-12-13 07:00:00', 5, 'Legacy', 'hrmthes', 'zinc coatings', NULL, '7708');

# relations
INSERT INTO `relation` (`id`, `data_modified`, `data_created`, `data_status`, `data_author`, `data_set`, `id1`, `id1_entity`, `id2`, `id2_entity`, `description`, `description_val`, `bidir`, `legacy_id`) VALUES
(1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 1, 'title', 32863, 'title', 'Parent', 'val_reldesc', 1, NULL),
(2, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 1, 'title', 2, 'title', 'Related', 'val_reldesc', 0, NULL),
(29688, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 24188, 'title', 62575, 'title', 'Parent', 'val_reldesc', 1, NULL),
(29689, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 24188, 'title', 62576, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33085, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 1, 'title', 66011, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33086, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 1, 'title', 66012, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33087, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 1, 'title', 66013, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33088, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 1, 'title', 66014, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33089, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 1, 'title', 66015, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33090, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 1, 'title', 66016, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33097, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 1, 'title', 66023, 'title', 'Parent', 'val_reldesc', 1, NULL),
(25670, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 2, 'title', 58556, 'title', 'Parent', 'val_reldesc', 1, NULL),
(25671, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 2, 'title', 58557, 'title', 'Parent', 'val_reldesc', 1, NULL),
(25672, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 2, 'title', 58558, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33111, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 2, 'title', 66037, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33112, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 2, 'title', 66038, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33113, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 2, 'title', 66039, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33114, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 2, 'title', 66040, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33115, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 2, 'title', 66041, 'title', 'Parent', 'val_reldesc', 1, NULL),
(33116, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 5, 'Legacy', 'av', 2, 'title', 66042, 'title', 'Parent', 'val_reldesc', 1, NULL),
(56226, '2001-12-13 07:00:00', '2008-02-14 07:00:00', 5, 'Legacy', 'av', 1, 'title', 12, 'keyword', 'Subject', 'val_reldesc', 1, 'LT13227'),
(56227, '2001-12-13 07:00:00', '2008-02-14 07:00:00', 5, 'Legacy', 'av', 1, 'title', 31, 'keyword', 'Subject', 'val_reldesc', 1, 'LT13227'),
(60614, '2001-12-13 07:00:00', '2003-04-07 06:00:00', 5, 'Legacy', 'av', 2, 'title', 31, 'keyword', 'Subject', 'val_reldesc', 1, 'LT17512'),
(87970, '2001-12-13 07:00:00', '2008-02-14 07:00:00', 5, 'Legacy', 'av', 1, 'title', 1317, 'keyword', 'Thesaurus', 'val_reldesc', 1, 'LT37301'),
(92791, '2001-12-13 07:00:00', '2003-08-19 06:00:00', 5, 'Legacy', 'av', 2, 'title', 2038, 'keyword', 'Thesaurus', 'val_reldesc', 1, 'LT42166'),
(107950, '2001-12-13 07:00:00', '2003-08-19 06:00:00', 5, 'Legacy', 'av', 2, 'title', 5314, 'keyword', 'Thesaurus', 'val_reldesc', 1, 'LT57534'),
(151194, '2005-11-07 07:00:00', '2005-11-07 07:00:00', 5, 'Legacy', 'avpart', 62575, 'title', 12, 'keyword', 'Subject', 'val_reldesc', 1, 'S138740'),
(151195, '2006-10-24 06:00:00', '2006-10-24 06:00:00', 5, 'Legacy', 'avpart', 62575, 'title', 1317, 'keyword', 'Thesaurus', 'val_reldesc', 1, 'LT106588'),
(169451, '2008-02-14 07:00:00', '2008-02-14 07:00:00', 5, 'Legacy', 'av', 1, 'title', 14678, 'pers', '6', 'val_pers_type', 1, 'LN28343'),
(169480, '2008-02-14 07:00:00', '2008-02-14 07:00:00', 5, 'Legacy', 'av', 1, 'title', 14678, 'pers', '7', 'val_pers_type', 1, 'LN28372'),
(169509, '2008-02-14 07:00:00', '2008-02-14 07:00:00', 5, 'Legacy', 'av', 1, 'title', 14678, 'pers', '3', 'val_pers_type', 1, 'LN28401'),
(253280, '2002-01-09 07:00:00', '2006-01-24 07:00:00', 5, 'Legacy', 'avpart', 62575, 'title', 6246, 'pers', '4', 'val_pers_type', 1, 'LN40667'),
(253281, '2002-01-09 07:00:00', '2006-01-24 07:00:00', 5, 'Legacy', 'avpart', 62575, 'title', 76100, 'pers', '3', 'val_pers_type', 1, 'LN40668'),
(253282, '2002-01-09 07:00:00', '2006-01-24 07:00:00', 5, 'Legacy', 'avpart', 62575, 'title', 76100, 'pers', '4', 'val_pers_type', 1, 'LN40669'),
(258366, '2001-12-13 07:00:00', '2008-02-14 07:00:00', 5, 'Legacy', 'av', 1, 'title', 5379, 'org', '3', 'val_org_relation', 1, 'LC15225'),
(258372, '2001-12-13 07:00:00', '2002-05-08 06:00:00', 5, 'Legacy', 'av', 2, 'title', 4113, 'org', '3', 'val_org_relation', 1, 'LC15233'),
(276852, '2001-12-13 07:00:00', '2008-02-14 07:00:00', 5, 'Legacy', 'av', 1, 'title', 945, 'org', '12', 'val_org_relation', 1, 'LC44260');

# Person
INSERT INTO `pers` (`id`, `data_modified`, `data_created`, `data_status`, `data_author`, `data_set`, `name_last`, `name_other`, `name_first`, `name_salutation`, `type`, `date_birth`, `date_death`, `description`, `notes`, `legacy_id`) VALUES
(6246, '2008-12-17 16:43:51', '2001-12-13 07:00:00', 5, 'Legacy', 'hrmindiv', 'Bryars, Gavin', '', '', '', '4', NULL, NULL, NULL, '', '1956'),
(14678, '2008-12-17 16:43:52', '2001-12-13 07:00:00', 5, 'Legacy', 'hrmindiv', 'Moran, Sherry', '', '', '', '6', NULL, NULL, NULL, '', '9888'),
(76100, '2005-11-04 07:00:00', '2002-01-31 07:00:00', 5, 'Legacy', 'hrmindiv', 'Munoz, Juan', '', '', '', NULL, NULL, NULL, NULL, '', 'N39643');

# Organisations
INSERT INTO `org` (`id`, `data_modified`, `data_created`, `data_status`, `data_set`, `data_author`, `name`, `type`, `description`, `notes`, `contact_name`, `contact_position`, `email`, `url`, `telephone`, `fax`, `address_1`, `address_2`, `address_3`, `address_4`, `address_town`, `address_county`, `address_country`, `address_postcode`, `legacy_id`) VALUES
(945, '2002-04-17 06:00:00', '2001-12-13 07:00:00', 5, 'hrmcomp', 'Legacy', 'Department of Trade and Industry, Best Practice Division', '3', NULL, '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '1924'),
(4113, '2002-04-17 06:00:00', '2001-12-13 07:00:00', 5, 'hrmcomp', 'Legacy', 'Productivity Europe Ltd', NULL, NULL, '', '', '', '', 'http://www.productivityeurope.co.uk', '01234 713311', '01234 713930', 'Osborns Court', 'Olney', 'Bucks', 'MK46 4AG', '', '', '', '', '5003'),
(5379, '2004-02-10 07:00:00', '2001-12-13 07:00:00', 5, 'hrmcomp', 'Legacy', 'Tribeca', NULL, NULL, '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '6222');

# Country data subset
INSERT INTO `val_country` (`value`, `label`) VALUES
('ar', 'Argentina'),
('at', 'Austria'),
('au', 'Australia'),
('be', 'Belgium'),
('bf', 'Burkina Faso'),
('bg', 'Bulgaria'),
('br', 'Brazil'),
('ca', 'Canada'),
('cg', 'Congo'),
('ch', 'Switzerland'),
('cn', 'China'),
('cs', 'Former Czechoslovakia'),
('cshh', 'Czechoslovakia'),
('cz', 'Czech Republic'),
('de', 'Germany'),
('dk', 'Denmark'),
('ee', 'Estonia'),
('eg', 'Egypt'),
('es', 'Spain'),
('fi', 'Finland'),
('fr', 'France'),
('gb', 'Great Britain'),
('ge', 'Georgia'),
('gh', 'Ghana'),
('gr', 'Greece'),
('hk', 'Hong Kong'),
('hr', 'Croatia'),
('hu', 'Hungary'),
('ie', 'Ireland'),
('il', 'Israel'),
('in', 'India'),
('it', 'Italy'),
('jp', 'Japan'),
('lt', 'Lithuania'),
('lu', 'Luxembourg'),
('ma', 'Morocco'),
('mg', 'Madagascar'),
('mx', 'Mexico'),
('my', 'Malaysia'),
('ng', 'Nigeria'),
('nl', 'Netherlands'),
('no', 'Norway'),
('nz', 'New Zealand'),
('pl', 'Poland'),
('pt', 'Portugal'),
('ro', 'Romania'),
('ru', 'Russian Federation'),
('se', 'Sweden'),
('sg', 'Singapore'),
('si', 'Slovenia'),
('srb', 'Serbia'),
('su', 'Former USSR'),
('th', 'Thailand'),
('tj', 'Tadjikistan'),
('tr', 'Turkey'),
('tw', 'Taiwan'),
('us', 'United States'),
('uz', 'Uzbekistan'),
('ve', 'Venezuela'),
('ye', 'Yemen'),
('yu', 'Yugoslavia'),
('za', 'South Africa');

# Language data subset
INSERT INTO `val_language` (`value`, `label`) VALUES
('ar-ae', 'Arabic (U.A.E.)'),
('ar-eg', 'Arabic (Egypt)'),
('be', 'Belarusian'),
('bg', 'Bulgarian'),
('ca', 'Catalan'),
('cs', 'Czech'),
('cym', 'Welsh'),
('da', 'Danish'),
('de', 'German (Standard)'),
('de-at', 'German (Austria)'),
('de-ch', 'German (Switzerland)'),
('el', 'Greek'),
('en', 'English'),
('en-au', 'English (Australia)'),
('en-cb', 'English (Caribbean)'),
('en-gb', 'English (Great Britain)'),
('en-ie', 'English (Ireland)'),
('es', 'Spanish (Spain - Modern)'),
('es-ar', 'Spanish (Argentina)'),
('es-mx', 'Spanish (Mexico)'),
('es-ve', 'Spanish (Venezuela)'),
('et', 'Estonian'),
('fi', 'Finnish'),
('fr', 'French (Standard)'),
('fr-be', 'French (Belgium)'),
('fr-ca', 'French (Canada)'),
('fr-ch', 'French (Switzerland)'),
('geo', 'Georgian'),
('he', 'Hebrew'),
('hi', 'Hindi'),
('hr', 'Croatian'),
('hu', 'Hungarian'),
('it', 'Italian (Standard)'),
('ja', 'Japanese'),
('ji', 'Yiddish'),
('lt', 'Lithuanian'),
('mri', 'Maori'),
('ms', 'Malaysian'),
('mz', 'Mzansi'),
('nl', 'Dutch (Standard)'),
('nl-be', 'Dutch (Belgium)'),
('no', 'Norwegian (Nynorsk)'),
('pid-en', 'Pidgin English'),
('pl', 'Polish'),
('pt', 'Portuguese (Portugal)'),
('pt-br', 'Portuguese (Brazil)'),
('ro', 'Romanian'),
('rom', 'Romany'),
('ru', 'Russian'),
('sgn', 'Sign Language'),
('sl', 'Slovenian'),
('sr', 'Serbian (Latin)'),
('sv', 'Swedish'),
('sw-af', 'Swahili'),
('sz', 'Sami (Lappish)'),
('th', 'Thai'),
('tr', 'Turkish'),
('ur', 'Urdu'),
('xh', 'Xhosa'),
('zh-cn', 'Chinese - Mandarin (PRC)'),
('zh-hk', 'Chinese (Hong Kong SAR)'),
('zh-sg', 'Chinese (Singapore)'),
('zu', 'Zulu');

# Title formats
INSERT INTO `val_title_type` (`value`, `label`) VALUES
(1, 'CD-i'),
(2, 'CD-ROM'),
(3, 'Compact disc'),
(4, 'Computer programme'),
(5, 'DAT/Exabyte'),
(6, 'DVD'),
(7, 'Electronic document'),
(8, 'Film'),
(9, 'Filmstrip'),
(10, 'Photo CD'),
(11, 'Photomicrographs'),
(12, 'Radiographs'),
(13, 'Slide set'),
(14, 'Slides'),
(15, 'Audio recording'),
(16, 'Tape-filmstrip'),
(17, 'Tape-slide'),
(18, 'Television broadcast'),
(19, 'Video'),
(20, 'Video recording'),
(21, 'Videodisc'),
(22, 'Multimedia'),
(23, 'Radio');

# Organisation types
INSERT INTO `val_org_type` (`value`, `label`) VALUES
(1, 'Publishing'),
(2, 'Production'),
(3, 'Distribution'),
(4, 'Sponsor');

# Organisation relation
INSERT INTO `val_org_relation` (`value`, `label`) VALUES
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

# Person types/roles
INSERT INTO `val_pers_type` (`value`, `label`, `data_set`) VALUES
(1, 'Adaptor for Radio', 'av_shk'),
(2, 'Adaptor for Television', 'av_shk'),
(3, 'Writer', 'av,av_shk'),
(4, 'Performer', 'av,av_shk'),
(5, 'Editor', 'av,av_shk'),
(6, 'Producer', 'av,av_shk'),
(7, 'Director', 'av,av_shk'),
(8, 'Contributor', 'av,av_shk'),
(9, 'Composer', 'av,av_shk'),
(10, 'Music', 'av,av_shk'),
(11, 'Music Director', 'av,av_shk'),
(12, 'Art Direction', 'av,av_shk'),
(13, 'Cinematographer', 'av,av_shk'),
(14, 'Screenplay', 'av,av_shk'),
(15, 'Costume', 'av,av_shk'),
(16, 'Choreographer', 'av,av_shk'),
(17, 'Animator', 'av_shk'),
(18, 'Production Design', 'av,av_shk'),
(19, 'Author', 'book,journal,catalogue,article'),
(20, 'Conference/Meeting', 'book,journal,catalogue,article'),
(21, 'Corporate', 'book,journal,catalogue,article'),
(22, 'Foreword', 'book,journal,catalogue,article'),
(23, 'Introduction', 'book,journal,catalogue,article'),
(24, 'Contributor*', NULL);