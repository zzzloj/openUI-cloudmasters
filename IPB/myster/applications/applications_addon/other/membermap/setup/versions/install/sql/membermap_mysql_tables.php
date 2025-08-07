<?php

/**
 * Install SQL
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

$TABLE[] = "CREATE TABLE member_map (
  member_id MEDIUMINT( 8 ) NOT NULL,
    lat FLOAT( 10, 6 ) NOT NULL,
    lon FLOAT( 10, 6 ) NOT NULL,
  PRIMARY KEY  (member_id)
);";

$TABLE[] = "CREATE TABLE member_map_cmarkers_groups (
		id			MEDIUMINT( 5 ) NOT NULL AUTO_INCREMENT,
		g_title		VARCHAR( 150 ) NOT NULL,
		pin_colour	VARCHAR( 6 ) NOT NULL,
		pin_icon	VARCHAR( 15 ) NOT NULL,
		PRIMARY KEY  (id)
		);";

$TABLE[] = "CREATE TABLE member_map_cmarkers (
		m_id MEDIUMINT( 5 ) NOT NULL AUTO_INCREMENT,
		g_id MEDIUMINT( 5 ) NOT NULL,
		title VARCHAR( 150 ) NOT NULL,
		description TEXT NOT NULL,
		lat FLOAT( 10, 6 ) NOT NULL,
		lon FLOAT( 10, 6 ) NOT NULL,
		PRIMARY KEY (m_id)
		);";

?>