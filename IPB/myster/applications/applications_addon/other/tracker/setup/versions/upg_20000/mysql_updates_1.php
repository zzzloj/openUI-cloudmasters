<?php

$SQL[] = "ALTER TABLE tracker_module_status CHANGE cat_id status_id int(10) NOT NULL   auto_increment;";
$SQL[] = "ALTER TABLE tracker_module_status CHANGE cat_title title varchar(200) NOT NULL DEFAULT '';";
$SQL[] = "ALTER TABLE tracker_module_status CHANGE cat_default default_open tinyint(1) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_module_status ADD default_closed tinyint(1) NOT NULL DEFAULT '0' AFTER default_open;";
$SQL[] = "ALTER TABLE tracker_module_status CHANGE cat_position position int(5) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_module_status CHANGE cat_allownew allow_new tinyint(1) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_module_status DROP cat_fixed_status;";
$SQL[] = "ALTER TABLE tracker_module_status CHANGE cat_reply_text reply_text text NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_module_status ADD closed tinyint(1) NULL DEFAULT NULL  AFTER reply_text;";

$SQL[] = "ALTER TABLE tracker_field_changes CHANGE field_change_date date int(10) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_field_changes CHANGE field_change_mid mid int(10) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_field_changes CHANGE field_issue_id issue_id int(10) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_field_changes CHANGE field_type module varchar(250) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_field_changes DROP field_extra;";
$SQL[] = "ALTER TABLE tracker_field_changes CHANGE field_old_value old_value varchar(250) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_field_changes CHANGE field_new_value new_value varchar(250) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_field_changes ADD title varchar(250) NULL DEFAULT NULL  AFTER module;";

$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_title title varchar(200) NOT NULL DEFAULT '';";
$SQL[] = "ALTER TABLE tracker_issues ADD title_seo varchar(200) NULL DEFAULT NULL  AFTER title;";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_state state varchar(8) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_posts posts int(10) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_starter_id starter_id mediumint(8) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues CHANGE starter_id starter_id mediumint(10) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_starter_name starter_name varchar(255) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_start_date start_date int(10) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_issues ADD starter_name_seo varchar(255) NULL DEFAULT NULL  AFTER start_date;";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_last_poster_id last_poster_id mediumint(10) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_last_poster_name last_poster_name varchar(255) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_issues ADD last_poster_name_seo varchar(255) NULL DEFAULT NULL  AFTER last_poster_name;";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_last_post last_post int(10) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_author_mode author_mode tinyint(1) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_hasattach hasattach smallint(5) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_firstpost firstpost int(10) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues DROP icon_id;";
$SQL[] = "ALTER TABLE tracker_issues DROP issue_locked;";
$SQL[] = "ALTER TABLE tracker_issues CHANGE cat_id module_status_id int(10) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues CHANGE severity_id module_severity_id tinyint(2) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues ADD private tinyint(1) NOT NULL DEFAULT '0'  AFTER firstpost;";