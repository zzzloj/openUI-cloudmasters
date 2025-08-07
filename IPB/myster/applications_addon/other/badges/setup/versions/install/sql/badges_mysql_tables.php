<?php

/* Badges */

$TABLE[] = "CREATE TABLE HQ_badges (
  ba_id     int(10)     NOT NULL AUTO_INCREMENT,
  ba_gid    varchar(255)  NOT NULL DEFAULT '0',
  ba_type     tinyint(1)    NOT NULL DEFAULT '1',
  ba_sg        tinyint(1)     NULL,
  ba_image      varchar(255)  NOT NULL DEFAULT '',
  ba_background varchar(255)  NULL, 
  ba_links    varchar(255)  NULL,
  ba_enabled  tinyint(1)    NOT NULL DEFAULT '0', 
  ba_forums   varchar(2000) NULL,
  ba_cstyle   varchar( 255 ) NULL,

  PRIMARY KEY (ba_id)
) ENGINE=MyISAM"; 

$TABLE[] = "CREATE TABLE HQ_badges_modules (
  ba_mid int(10)     NOT NULL,
  ba_mname varchar(255)  NOT NULL,
  ba_mdir varchar(255)  NOT NULL,
  ba_mactive tinyint(1)    NOT NULL DEFAULT '1',

  PRIMARY KEY (ba_mid)
) ENGINE=MyISAM"; 

$TABLE[] = "INSERT INTO HQ_badges_modules (ba_mid, ba_mname, ba_mdir, ba_mactive) VALUES (1, 'Group Badges', '', 1)";
