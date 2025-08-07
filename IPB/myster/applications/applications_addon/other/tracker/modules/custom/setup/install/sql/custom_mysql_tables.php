<?php
/**
* Installation Schematic File
* Generated on Thu, 04 Dec 2008 16:39:43 +0000 GMT
*
*/

$TABLE[] = "CREATE TABLE tracker_module_custom (
  field_id int(10) NOT NULL AUTO_INCREMENT,
  title varchar(250) NOT NULL DEFAULT '',
  title_plural varchar(250) NOT NULL DEFAULT '',
  description varchar(250) NOT NULL DEFAULT '',
  type varchar(250) NOT NULL DEFAULT '',
  setup varchar(250) NOT NULL DEFAULT '',
  max_input smallint(6) NOT NULL DEFAULT '0',
  project_display tinyint(1) NOT NULL DEFAULT '0',
  not_null tinyint(1) NOT NULL DEFAULT '0',
  input_format text,
  multimod_update tinyint(1) NOT NULL DEFAULT '0',
  project_filter tinyint(1) NOT NULL DEFAULT '0',
  position int(11) DEFAULT NULL,
  projects text,
  custom_perms text,
  issue_display_type varchar(10) DEFAULT 'info',
  PRIMARY KEY (field_id)
);";

$TABLE[] = "CREATE TABLE tracker_module_custom_option (
  option_id int(10) NOT NULL AUTO_INCREMENT,
  field_id smallint(5) NOT NULL DEFAULT '0',
  key_value varchar(250) NOT NULL DEFAULT '',
  human varchar(250) NOT NULL DEFAULT '',
  permissions varchar(250) NOT NULL DEFAULT '',
  default_option tinyint(1) NOT NULL DEFAULT '0',
  position int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (option_id),
  KEY field_id (field_id)
);";

// This has to be an alter in case we are upgrading from 1.3
//$TABLE[] = "ALTER TABLE tracker_module_custom ADD issue_display_type varchar(10) NOT NULL DEFAULT 'info';";

?>