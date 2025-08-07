<?php
/**
* Installation Schematic File
* Generated on Thu, 04 Dec 2008 16:39:43 +0000 GMT
*/

$TABLE[] = "CREATE TABLE tracker_module_status (
	status_id				int(10) NOT NULL auto_increment,
	title					varchar(200) NOT NULL default '',
	default_open			tinyint(1) NOT NULL default '0',
	default_closed			tinyint(1) NOT NULL default '0',
	position				int(5) NOT NULL default '0',
	allow_new				tinyint(1) NOT NULL default '0',
	closed					tinyint(1) NOT NULL default '0',
	reply_text				text,
	PRIMARY KEY (status_id)
);";


/* Alters */
$TABLE[] = "ALTER TABLE tracker_moderators ADD status_field_status_show TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD status_field_status_submit TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD status_field_status_update TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD status_field_status_allowall TINYINT(1) NOT NULL DEFAULT '0';";

$TABLE[] = "ALTER TABLE tracker_issues ADD module_status_id INT(10) NOT NULL DEFAULT '0';";

?>