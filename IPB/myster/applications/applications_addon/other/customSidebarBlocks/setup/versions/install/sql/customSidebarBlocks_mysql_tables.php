<?php

$TABLE[] = "CREATE TABLE custom_sidebar_blocks (
  csb_id smallint(4) unsigned NOT NULL auto_increment,
  csb_title varchar(255) NOT NULL default '',
  csb_image varchar(64) NOT NULL default '',
  csb_content text NOT NULL,
  csb_use_box tinyint(1) NOT NULL default '0',
  csb_use_perms tinyint(1) NOT NULL default '0',
  csb_position smallint(3) NOT NULL  default '0',
  csb_raw TINYINT(1) NOT NULL DEFAULT '0',
  csb_on tinyint(1) NOT NULL default '0',
  csb_no_collapse TINYINT(1) NOT NULL DEFAULT '0',
  csb_php TINYINT(1) NOT NULL DEFAULT '0',
  csb_hide_block TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (csb_id)
);";

?>