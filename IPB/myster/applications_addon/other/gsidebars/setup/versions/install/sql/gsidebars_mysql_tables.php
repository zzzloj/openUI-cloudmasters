<?php
 
//-----------------------------------------------
// (DP32) Global Sidebars
//-----------------------------------------------
//-----------------------------------------------
// MySQL 
//-----------------------------------------------
// Author: DawPi
// Site: http://www.ipslink.pl
// Written on: 05 / 02 / 2011
// Updated on: 18 / 11 / 2011
//-----------------------------------------------
// Copyright (C) 2011 DawPi
// All Rights Reserved
//-----------------------------------------------  


/* Sidebars */

$TABLE[] = "CREATE TABLE dp3_gs_sidebars (
  s_id int(10) NOT NULL AUTO_INCREMENT,
  s_name varchar(255) NOT NULL DEFAULT '',
  s_type varchar(50) NOT NULL DEFAULT '',
  s_enabled tinyint(1) NOT NULL,
  s_groups varchar(255) NOT NULL DEFAULT '',  
  s_adverts text NOT NULL,
  s_adv_adverts varchar(255) NOT NULL DEFAULT '',
  s_custom varchar(255) NOT NULL DEFAULT '',
  s_wrapper tinyint(1) NOT NULL,
  s_wrapper_separate tinyint(1) NOT NULL,  
  s_random tinyint(1) NOT NULL,  
  s_limit_at_once mediumint(5) NOT NULL,
  s_nexus_adverts varchar(255) NOT NULL,
  PRIMARY KEY (s_id)
)";
  
  
/* Adverts */

$TABLE[] = "CREATE TABLE dp3_gs_adverts (
  a_id int(10) NOT NULL AUTO_INCREMENT,
  a_sidebar_id int(10) NOT NULL,
  a_php_mode tinyint(1) NOT NULL,
  a_raw_mode tinyint(1) NOT NULL,
  a_name varchar(255) NOT NULL DEFAULT '',
  a_content text NOT NULL,
  a_position mediumint(5) NOT NULL,
  a_enabled tinyint(1) NOT NULL,
  a_is_advanced int(5) NOT NULL,
  a_duplicate_id int(10) NOT NULL,
  a_pinned tinyint(1) NOT NULL,
  a_is_nexus int(5) NOT NULL,
  PRIMARY KEY (a_id)
)";  