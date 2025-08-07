<?php

$SQL[] = "CREATE TABLE HQ_badges_modules (
  ba_mid int(10)     NOT NULL,
  ba_mname varchar(255)  NOT NULL,
  ba_mdir varchar(255)  NOT NULL,
  ba_mactive tinyint(1)    NOT NULL DEFAULT '1',

  PRIMARY KEY (ba_mid)
)"; 

$SQL[] = "ALTER TABLE HQ_badges CHANGE ba_t ba_sg tinyint(1)     NULL";
$SQL[] = "ALTER TABLE HQ_badges CHANGE ba_r ba_image varchar(255)  NOT NULL DEFAULT ''";
$SQL[] = "INSERT INTO HQ_badges_modules (ba_mid, ba_mname, ba_mdir, ba_mactive) VALUES (1, 'Group Badges', '', 1);";
$SQL[] = "ALTER TABLE HQ_badges ADD ba_cstyle VARCHAR( 255 ) NULL";