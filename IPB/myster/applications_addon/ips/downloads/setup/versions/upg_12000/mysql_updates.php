<?php

$SQL[] = "CREATE TABLE downloads_sessions (
dsess_id VARCHAR( 32 ) NOT NULL ,
dsess_mid INT( 10 ) NOT NULL DEFAULT '0',
dsess_ip VARCHAR( 32 ) NULL ,
dsess_file INT( 10 ) NOT NULL DEFAULT '0',
dsess_start VARCHAR( 13 ) NOT NULL DEFAULT '0',
dsess_end VARCHAR( 13 ) NOT NULL DEFAULT '0',
PRIMARY KEY ( dsess_id ) ,
INDEX ( dsess_mid , dsess_ip )
) ENGINE = MYISAM ;";


$SQL[] = "ALTER TABLE downloads_cfields ADD cf_topic TINYINT( 1 ) NOT NULL DEFAULT '0', ADD cf_search TINYINT( 1 ) NOT NULL DEFAULT '0';";

$SQL[] = "ALTER TABLE downloads_comments ADD comment_append_edit TINYINT( 1 ) NOT NULL DEFAULT '0',
ADD comment_edit_time VARCHAR( 11 ) NOT NULL DEFAULT '0',
ADD comment_edit_name VARCHAR( 255 ) NULL ,
ADD ip_address VARCHAR( 32 ) NULL ,
ADD use_sig TINYINT( 1 ) NOT NULL DEFAULT '1',
ADD use_emo TINYINT( 1 ) NOT NULL DEFAULT '1';";


$SQL[] = "ALTER TABLE downloads_files ADD file_broken_info VARCHAR( 255 ) NULL AFTER file_broken_reason ;";

