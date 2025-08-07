<?php

$SQL[] = "RENAME TABLE tracker_fields_data TO tracker_custom_fields_backup;";
$SQL[] = "RENAME TABLE tracker_categories TO tracker_module_status;";

$SQL[] = "CREATE TABLE tracker_field (
  field_id int(10) NOT NULL AUTO_INCREMENT,
  field_keyword varchar(40) NOT NULL DEFAULT '',
  module_id int(10) NOT NULL DEFAULT '0',
  setup varchar(250) NOT NULL DEFAULT '',
  title varchar(250) NOT NULL DEFAULT '',
  position int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (field_id)
);";

$SQL[] = "CREATE TABLE tracker_module (
  module_id int(10) NOT NULL AUTO_INCREMENT,
  title varchar(250) NOT NULL DEFAULT '',
  description varchar(250) NOT NULL DEFAULT '',
  author varchar(250) NOT NULL DEFAULT '',
  version varchar(250) NOT NULL DEFAULT '',
  long_version int(10) NOT NULL DEFAULT '0',
  directory varchar(255) NOT NULL DEFAULT '',
  added int(10) NOT NULL DEFAULT '0',
  protected tinyint(1) NOT NULL DEFAULT '0',
  enabled tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (module_id)
);";

$SQL[] = "CREATE TABLE tracker_module_severity (
  severity_id int(10) NOT NULL,
  skin_id int(10) NOT NULL,
  font_color varchar(15) NOT NULL DEFAULT '',
  background_color varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (severity_id,skin_id)
);";

$SQL[] = "CREATE TABLE tracker_module_upgrade_history (
  upgrade_id int(10) NOT NULL AUTO_INCREMENT,
  upgrade_version_id int(10) NOT NULL DEFAULT '0',
  upgrade_version_human varchar(200) NOT NULL DEFAULT '',
  upgrade_date int(10) NOT NULL DEFAULT '0',
  upgrade_mid int(10) NOT NULL DEFAULT '0',
  upgrade_notes text,
  upgrade_module varchar(32) NOT NULL DEFAULT 'core',
  PRIMARY KEY (upgrade_id),
  KEY upgrades (upgrade_module,upgrade_version_id)
);";

$SQL[] = "CREATE TABLE tracker_module_version (
  version_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) NOT NULL DEFAULT '0',
  human varchar(250) NOT NULL DEFAULT '',
  permissions varchar(250) NOT NULL DEFAULT '',
  fixed_only tinyint(1) NOT NULL DEFAULT '0',
  report_default tinyint(1) NOT NULL DEFAULT '0',
  fixed_default tinyint(1) NOT NULL DEFAULT '0',
  position int(5) NOT NULL DEFAULT '0',
  locked tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (version_id),
  KEY project_id (project_id)
);";

$SQL[] = "CREATE TABLE tracker_project_field (
  project_id int(10) NOT NULL,
  field_id int(10) NOT NULL,
  position int(5) NOT NULL DEFAULT '0',
  enabled tinyint(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (project_id,field_id)
);";

$SQL[] = "CREATE TABLE tracker_project_metadata (
  metadata_id int(10) NOT NULL AUTO_INCREMENT,
  field_id int(10) NOT NULL DEFAULT '0',
  project_id int(10) NOT NULL DEFAULT '0',
  meta_key varchar(250) NOT NULL DEFAULT '',
  meta_value text,
  PRIMARY KEY (metadata_id),
  KEY field_id (field_id),
  KEY project_id (project_id),
  KEY meta_key (meta_key)
);";

$SQL[] = "DROP TABLE tracker_upgrade_history;";

// $SQL[] = "ALTER TABLE tracker_issues CHANGE issue_version module_versions_reported_id int(10) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues ADD module_versions_reported_id int(10) NOT NULL DEFAULT '0';";
// $SQL[] = "ALTER TABLE tracker_issues CHANGE issue_fixed_in module_versions_fixed_id int(10) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues ADD module_versions_fixed_id int(10) NOT NULL DEFAULT '0';";