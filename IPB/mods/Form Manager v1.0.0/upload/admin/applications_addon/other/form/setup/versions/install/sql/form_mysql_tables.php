<?php

$TABLE[] = "CREATE TABLE form_logs (
  log_id int(10) NOT NULL auto_increment,
  log_form_id int(10) NOT NULL default '0',
  member_id int(10) NOT NULL default '0',
  member_name varchar(255) NOT NULL default '',
  member_email varchar(255) NOT NULL,
  log_date int(10) NOT NULL default '0',
  ip_address varchar(46) NOT NULL default '0.0.0.0',
  log_post_key varchar(32) default NULL,
  message text,
  has_attach mediumint(5) NOT NULL default '0',
  PRIMARY KEY  (log_id)
);";

$TABLE[] = "CREATE TABLE form_forms (
  form_id int(5) NOT NULL auto_increment,
  parent_id int(5) NOT NULL default '0',
  form_name varchar(255) NOT NULL,
  name_seo varchar(255) NOT NULL default '',
  description mediumtext,
  options text NOT NULL,
  info text,
  position int(5) NOT NULL default '1',
  form_rules text NULL,
  pm_settings text,
  email_settings text,
  topic_settings text,
  PRIMARY KEY  (form_id)
);";

$TABLE[] = "CREATE TABLE form_fields (
  field_id int(10) NOT NULL auto_increment,
  field_form_id int(10) NOT NULL,
  field_title varchar(255) NOT NULL default '',
  field_name varchar(255) NOT NULL default '',
  field_value text,
  field_text text,
  field_type varchar(255) NOT NULL default '',
  field_required tinyint(1) NOT NULL default '0',
  field_options text,
  field_extras text,
  field_data text,
  field_position int(5) NOT NULL default '1',
  PRIMARY KEY  (field_id)
);";

$TABLE[] = "CREATE TABLE form_fields_values (
  value_id int(10) NOT NULL auto_increment,
  value_field_id int(5) NOT NULL default '0',
  value_member_id int(5) NOT NULL default '0',
  value_form_id int(5) NOT NULL default '0',
  value_log_id int(5) NOT NULL default '0',
  value_value text,
  PRIMARY KEY  (value_id)
);";

$TABLE[] = "ALTER TABLE groups ADD g_fs_view_offline TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE groups ADD g_fs_submit_wait INT(5) NOT NULL DEFAULT '600';";    
$TABLE[] = "ALTER TABLE groups ADD g_fs_bypass_captcha TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE groups ADD g_fs_allow_attach TINYINT(1) NOT NULL DEFAULT '1';";    
$TABLE[] = "ALTER TABLE groups ADD g_fs_view_logs TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE groups ADD g_fs_moderate_logs TINYINT(1) NOT NULL DEFAULT '0';";

?>