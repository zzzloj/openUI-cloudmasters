<?php

$SQL[] = "ALTER TABLE tracker_projects CHANGE project_title title varchar(200) NOT NULL DEFAULT '';";
$SQL[] = "ALTER TABLE tracker_projects CHANGE project_description description text NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_projects DROP project_owner_id;";
$SQL[] = "ALTER TABLE tracker_projects ADD template_id int(10) NOT NULL DEFAULT '0'  AFTER description;";
$SQL[] = "ALTER TABLE tracker_projects CHANGE project_parent parent_id int(10) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_projects CHANGE project_cat_only cat_only tinyint(1) NOT NULL DEFAULT '0';";

$SQL[] = "ALTER TABLE tracker_projects DROP project_num_issues;";
$SQL[] = "ALTER TABLE tracker_projects CHANGE project_bug_counts serial_data text;";
$SQL[] = "ALTER TABLE tracker_projects CHANGE project_email_new email_new tinyint(1) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_projects CHANGE project_position position int(5) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_projects CHANGE project_enable_rss enable_rss tinyint(1) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_projects CHANGE project_use_html use_html tinyint(1) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_projects CHANGE project_use_ibc use_ibc tinyint(1) NOT NULL DEFAULT '1'";
$SQL[] = "ALTER TABLE tracker_projects CHANGE project_quick_reply quick_reply tinyint(1) NOT NULL DEFAULT '1'";
$SQL[] = "ALTER TABLE tracker_projects DROP project_last_post;";
$SQL[] = "ALTER TABLE tracker_projects DROP project_last_issue_id;";
$SQL[] = "ALTER TABLE tracker_projects DROP project_last_issue_title;";
$SQL[] = "ALTER TABLE tracker_projects DROP project_last_post_id;";
$SQL[] = "ALTER TABLE tracker_projects DROP project_last_poster_id;";
$SQL[] = "ALTER TABLE tracker_projects DROP project_last_poster_name;";
$SQL[] = "ALTER TABLE tracker_projects DROP project_custom_fields;";
$SQL[] = "ALTER TABLE tracker_projects ADD private_issues tinyint(1) NOT NULL DEFAULT '0'  AFTER quick_reply;";
$SQL[] = "ALTER TABLE tracker_projects ADD private_default tinyint(1) NOT NULL DEFAULT '0'  AFTER private_issues;";

// default data will go here nearer final release
$SQL[] = "INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd, app) VALUES(1, 'Tracker Plugin', 'This is the plugin for making reports with Tracker.', 'Invision Power Services, Inc', 'http://www.invisionpower.com', 'v1.0.0', 'tracker', ',1,2,3,4,6,', ',4,6,', 'a:1:{s:15:\"report_supermod\";s:1:\"1\";}', 0, 'tracker');";

$SQL[] = "INSERT INTO tracker_module (title,description,author,version,long_version,directory,added,protected,enabled)
VALUES('Versions', 'Versions allow you to categorise different reports into different project versions.', 'Invision Power Services, Inc.', '1.0.0', 10000, 'versions', 1292261881, 0, 1);";

$SQL[] = "INSERT INTO tracker_module (title,description,author,version,long_version,directory,added,protected,enabled)
VALUES('Statuses', 'Statuses allow you to categorize reports into different sections.', 'Invision Power Services, Inc.', '1.0.0', 10000, 'status', 1292262877, 0, 1)";

$SQL[] = "INSERT INTO tracker_module (title,description,author,version,long_version,directory,added,protected,enabled)
VALUES('Severity', 'Severities allow reports in your Tracker system to be assigned a level of importance ranging from 1 (not very important), up until 5 (critical).', 'Invision Power Services, Inc.', '1.0.0', 10000, 'severity', 1292268049, 0, 1);";

$SQL[] = "INSERT INTO tracker_field (field_keyword,module_id,setup,title,position)
VALUES ('severity', 3, '', 'Severity', 0), ('status', 2, '', 'Status', 0), ('version', 1, '', 'Version', 0), ('fixed_in', 1, '', 'Fixed In', 0);";