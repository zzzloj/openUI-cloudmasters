<?php
/**
* Installation Schematic File
* Generated on Thu, 04 Dec 2008 16:39:43 +0000 GMT
*/

$TABLE[] = "CREATE TABLE tracker_module_severity (
	severity_id			int(10) NOT NULL,
	skin_id				int(10) NOT NULL,
	font_color			varchar(15) NOT NULL default '',
	background_color	varchar(15) NOT NULL default '',
	PRIMARY KEY (severity_id, skin_id)
);";


/* Alters */
$TABLE[] = "ALTER TABLE tracker_moderators ADD severity_field_severity_show TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD severity_field_severity_submit TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD severity_field_severity_update TINYINT(1) NOT NULL DEFAULT '0';";

$TABLE[] = "ALTER TABLE tracker_issues ADD module_severity_id TINYINT(2) NOT NULL DEFAULT '0';";

?>