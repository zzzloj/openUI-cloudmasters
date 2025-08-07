<?php

$SQL[] = "ALTER TABLE downloads_categories DROP INDEX cposition;";

$SQL[] = "ALTER TABLE downloads_categories ADD INDEX position_order ( cparent , cposition );";
$SQL[] = "ALTER TABLE downloads_urls ADD INDEX ( url_expires );";
$SQL[] = "ALTER TABLE downloads_favorites ADD INDEX ( fmid );";
$SQL[] = "ALTER TABLE downloads_downloads ADD INDEX ( dmid );";
$SQL[] = "ALTER TABLE downloads_sessions ADD INDEX ( dsess_start );";

$SQL[] = "ALTER TABLE downloads_comments CHANGE comment_date comment_date INT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_comments CHANGE comment_edit_time comment_edit_time INT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_favorites CHANGE fupdated fupdated INT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_filebackup CHANGE b_backup b_backup INT NOT NULL DEFAULT '0'";
$SQL[] = "ALTER TABLE downloads_filebackup CHANGE b_updated b_updated INT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_files CHANGE file_submitted file_submitted INT NOT NULL DEFAULT '0'";
$SQL[] = "ALTER TABLE downloads_files CHANGE file_updated file_updated INT NOT NULL DEFAULT '0'";
$SQL[] = "ALTER TABLE downloads_files CHANGE file_approvedon file_approvedon INT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_sessions CHANGE dsess_start dsess_start INT NOT NULL DEFAULT '0'";
$SQL[] = "ALTER TABLE downloads_sessions CHANGE dsess_end dsess_end INT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_temp_records CHANGE record_added record_added INT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_urls CHANGE url_created url_created INT NOT NULL DEFAULT '0'";
$SQL[] = "ALTER TABLE downloads_urls CHANGE url_expires url_expires INT NOT NULL DEFAULT '0';";
$SQL[] = "delete FROM core_sys_conf_settings WHERE conf_key='idm_guest_report';";

