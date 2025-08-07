<?php

global $DB;

$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_starter_name  issue_starter_name VARCHAR( 255 ) NULL DEFAULT NULL";
$SQL[] = "ALTER TABLE tracker_issues CHANGE issue_last_poster_name  issue_last_poster_name VARCHAR( 255 ) NULL DEFAULT NULL";
$SQL[] = "ALTER TABLE tracker_logs CHANGE member_name  member_name VARCHAR( 255 ) NULL DEFAULT NULL";
$SQL[] = "ALTER TABLE tracker_posts CHANGE author_name  author_name VARCHAR( 255 ) NULL DEFAULT NULL";
$SQL[] = "ALTER TABLE tracker_projects CHANGE project_last_poster_name  project_last_poster_name VARCHAR( 255 ) NULL DEFAULT NULL";

?>