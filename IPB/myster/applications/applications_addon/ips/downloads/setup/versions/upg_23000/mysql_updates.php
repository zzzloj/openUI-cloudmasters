<?php

$SQL[] = "DROP TABLE downloads_ip2ext;";
$SQL[] = "ALTER TABLE downloads_downloads DROP dcountry;";
$SQL[] = "UPDATE rc_classes SET app='downloads' WHERE my_class='downloads';";
$SQL[] = "ALTER TABLE downloads_cfields ADD cf_format TEXT NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE downloads_comments ADD comment_author VARCHAR( 255 ) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE groups ADD idm_report_files TINYINT( 1 ) NOT NULL DEFAULT '0';";
$SQL[] = "UPDATE groups SET idm_report_files=1;";
$SQL[] = "ALTER TABLE groups ADD idm_view_downloads TINYINT( 1 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_mods ADD modchangeauthor TINYINT( 1 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_files DROP INDEX file_open , ADD INDEX file_open ( file_open , file_cat , file_submitted );";
$SQL[] = "ALTER TABLE downloads_files ADD file_version VARCHAR( 32 ) NULL DEFAULT NULL , ADD file_changelog TEXT NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE downloads_filebackup ADD b_version VARCHAR( 32 ) NULL DEFAULT NULL , ADD b_changelog TEXT NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE downloads_files_records ADD record_default TINYINT( 1 ) NOT NULL DEFAULT '0';";


$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='idm_allow_emaillinks';";
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='idm_comment_display';";
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='idm_guest_report';";