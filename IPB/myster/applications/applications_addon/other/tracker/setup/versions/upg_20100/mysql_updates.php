<?php

$SQL[] = "TRUNCATE TABLE tracker_field_changes;";
$SQL[] = "DROP TABLE tracker_ptracker;";
$SQL[] = "DROP TABLE tracker_itracker;";
$SQL[] = "ALTER TABLE tracker_issues ADD type varchar(255) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE tracker_issues ADD sug_up int(11) NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues ADD sug_down int(11) NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues ADD repro_up int(11) NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_issues ADD repro_down int(11) NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_project_field ADD custom tinyint(1) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker_projects ADD enable_suggestions tinyint(1) NOT NULL DEFAULT '1';";
$SQL[] = "ALTER TABLE tracker_projects ADD disable_tagging tinyint(1) NOT NULL DEFAULT '0';";
$SQL[] = "CREATE TABLE tracker_ratings (
  rating_id int(11) NOT NULL AUTO_INCREMENT,
  member_id int(11) DEFAULT NULL,
  issue_id int(11) NOT NULL,
  type varchar(255) NOT NULL,
  score tinyint(1) DEFAULT NULL,
  PRIMARY KEY (rating_id)
);";