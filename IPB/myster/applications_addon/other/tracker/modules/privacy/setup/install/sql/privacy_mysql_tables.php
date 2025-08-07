<?php
/**
* Installation Schematic File
* Generated on Thu, 04 Dec 2008 16:39:43 +0000 GMT
*/

/* Alters */
$TABLE[] = "ALTER TABLE tracker_moderators ADD privacy_field_privacy_show TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD privacy_field_privacy_submit TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE tracker_moderators ADD privacy_field_privacy_update TINYINT(1) NOT NULL DEFAULT '0';";

$TABLE[] = "ALTER TABLE tracker_issues ADD module_privacy TINYINT(1) NOT NULL DEFAULT '0';";

?>