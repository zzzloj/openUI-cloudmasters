<?php
/**
* Installation Schematic File
* Generated on Thu, 04 Dec 2008 16:39:43 +0000 GMT
*/

$TABLE[] = "CREATE TABLE tracker_module_version (
	version_id					int(10) NOT NULL auto_increment,
	project_id					int(10) NOT NULL default '0',
	human						varchar(250) NOT NULL default '',
	permissions					varchar(250) NOT NULL default '',
	fixed_only					tinyint(1) NOT NULL default '0',
	report_default				tinyint(1) NOT NULL default '0',
	fixed_default				tinyint(1) NOT NULL default '0',
	position					int(5) NOT NULL default '0',
	locked						tinyint(1) NOT NULL default '0',
	PRIMARY KEY (version_id),
	KEY project_id (project_id)
);";


/* Alters */
$TABLE[] = "ALTER TABLE tracker_moderators ADD versions_field_version_show TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD versions_field_version_submit TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD versions_field_version_update TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD versions_field_version_developer TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD versions_field_version_alter TINYINT(1) NOT NULL DEFAULT '0';";

$TABLE[] = "ALTER TABLE tracker_moderators ADD versions_field_fixed_in_show TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD versions_field_fixed_in_submit TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD versions_field_fixed_in_update TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD versions_field_fixed_in_developer TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD versions_field_fixed_in_report TINYINT(1) NOT NULL DEFAULT '0';";

$TABLE[] = "ALTER TABLE tracker_issues ADD module_versions_reported_id INT(10) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_issues ADD module_versions_fixed_id INT(10) NOT NULL DEFAULT '0';";

?>