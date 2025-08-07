<?php

$SQL[] = "ALTER TABLE downloads_files ADD file_url TINYTEXT NULL;";
$SQL[] = "ALTER TABLE downloads_files ADD file_ssurl TINYTEXT NULL;";
$SQL[] = "ALTER TABLE downloads_files ADD file_realname VARCHAR( 255 ) NULL;";
$SQL[] = "ALTER TABLE downloads_files ADD file_broken_reason TEXT NULL;";

$SQL[] = "ALTER TABLE groups ADD idm_restrictions TEXT NULL;";

$SQL[] = "CREATE TABLE downloads_filebackup (
b_id INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
b_fileid INT( 10 ) NOT NULL DEFAULT '0',
b_filetitle VARCHAR( 255 ) NOT NULL DEFAULT '0',
b_filedesc TEXT NULL ,
b_filename VARCHAR( 255 ) NOT NULL DEFAULT '0',
b_ssname VARCHAR( 255 ) NOT NULL DEFAULT '0',
b_thumbname VARCHAR( 255 ) NOT NULL DEFAULT '0',
b_filemime MEDIUMINT( 8 ) NOT NULL DEFAULT '0',
b_ssmime MEDIUMINT( 8 ) NOT NULL DEFAULT '0',
b_filemeta TEXT NULL ,
b_storage VARCHAR( 10 ) NOT NULL DEFAULT 'web',
b_hidden TINYINT( 1 ) NOT NULL DEFAULT '0',
b_backup VARCHAR( 13 ) NOT NULL DEFAULT '0',
b_updated VARCHAR( 13 ) NOT NULL DEFAULT '0',
b_fileurl TINYTEXT NULL ,
b_ssurl TINYTEXT NULL ,
INDEX ( b_fileid )
) ENGINE = MYISAM ;";

