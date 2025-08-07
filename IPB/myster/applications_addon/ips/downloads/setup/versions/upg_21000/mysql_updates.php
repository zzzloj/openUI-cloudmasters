<?php

$SQL[] = "ALTER TABLE downloads_categories ADD cname_furl VARCHAR( 255 ) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE downloads_files ADD file_name_furl VARCHAR( 255 ) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE downloads_files ADD file_topicseoname VARCHAR( 255 ) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE downloads_files ADD file_post_key VARCHAR( 32 ) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE downloads_files ADD INDEX ( file_post_key );";
$SQL[] = "ALTER TABLE downloads_filebackup ADD b_records TEXT NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE downloads_filestorage CHANGE storage_id storage_id INT( 10 ) NOT NULL AUTO_INCREMENT;";
$SQL[] = "ALTER TABLE downloads_filestorage DROP INDEX storage_id , ADD PRIMARY KEY ( storage_id );";

$SQL[] = "ALTER TABLE downloads_filestorage CHANGE storage_file storage_file LONGBLOB NULL DEFAULT NULL ,
  CHANGE storage_ss storage_ss LONGBLOB NULL DEFAULT NULL ,
  CHANGE storage_thumb storage_thumb LONGBLOB NULL DEFAULT NULL;";

$SQL[] = "CREATE TABLE downloads_urls (
  url_id VARCHAR( 32 ) NOT NULL ,
  url_file INT NOT NULL DEFAULT '0',
  url_ip VARCHAR( 32 ) NULL DEFAULT NULL ,
  url_created VARCHAR( 13 ) NOT NULL DEFAULT '0',
  url_expires VARCHAR( 13 ) NOT NULL DEFAULT '0',
  PRIMARY KEY ( url_id ) ,
  INDEX ( url_file )
);";

$SQL[] = "CREATE TABLE downloads_files_records (
  record_id int(11) NOT NULL auto_increment,
  record_post_key varchar(32) default NULL,
  record_file_id int(11) NOT NULL default '0',
  record_type varchar(32) NOT NULL default 'file',
  record_location text,
  record_db_id int(11) NOT NULL default '0',
  record_thumb text,
  record_storagetype varchar(24) NOT NULL default 'web',
  record_realname varchar(255) default NULL,
  record_link_type varchar(255) default NULL,
  record_mime smallint(6) NOT NULL default '0',
  record_size int(11) NOT NULL default '0',
  record_backup tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (record_id),
  KEY record_post_key (record_post_key),
  KEY record_file_id (record_file_id),
  KEY record_db_id (record_db_id),
  KEY record_realname (record_realname),
  KEY record_type (record_type,record_file_id,record_backup)
);";

$SQL[] = "CREATE TABLE downloads_temp_records (
  record_id int(11) NOT NULL auto_increment,
  record_post_key varchar(32) default NULL,
  record_file_id int(11) NOT NULL default '0',
  record_type varchar(32) NOT NULL default 'file',
  record_location text null,
  record_realname varchar(255) default NULL,
  record_mime smallint(6) NOT NULL default '0',
  record_size int(11) NOT NULL default '0',
  record_added varchar(13) NOT NULL default '0',
  PRIMARY KEY  (record_id),
  KEY record_post_key (record_post_key),
  KEY record_file_id (record_file_id)
);";



