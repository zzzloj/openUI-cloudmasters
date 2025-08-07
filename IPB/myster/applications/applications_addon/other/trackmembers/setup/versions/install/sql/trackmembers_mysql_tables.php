<?php

/**
 * Product Title:		(SOS34) Track Member
 * Product Version:		1.0.0
 * Author:				Adriano Faria
 * Website:				SOS Invision
 * Website URL:			http://forum.sosinvision.com.br/
 * Email:				administracao@sosinvision.com.br
 */

$TABLE[] = "CREATE TABLE members_tracker (
  id int(10) NOT NULL AUTO_INCREMENT,
  member_id mediumint(8) NOT NULL,
  description text NOT NULL,
  app varchar(32) NOT NULL DEFAULT '',
  date int(10) NOT NULL,
  function varchar(255) NOT NULL DEFAULT '',
  function_id int(10) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY member_id (member_id)
);";

$TABLE[] = "ALTER TABLE members ADD member_tracked tinyint(1) NOT NULL default '0'";
$TABLE[] = "ALTER TABLE members ADD member_tracked_deadline int(10) NOT NULL default '0'";