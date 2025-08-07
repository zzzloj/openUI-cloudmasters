<?php

$TABLE[] = "CREATE TABLE IF NOT EXISTS bbcodeTables (
	id int(11) NOT NULL AUTO_INCREMENT,
	title varchar(150) DEFAULT NULL,
	description varchar(255) NOT NULL,
	table_width varchar(5) NOT NULL,
	table_cell_vpos varchar(6) NOT NULL,
	rows int(4) NOT NULL DEFAULT '0',
	columns int(4) NOT NULL DEFAULT '0',
	headerpos varchar(6) NOT NULL,
	headers text DEFAULT NULL,
	content text DEFAULT NULL,
	postid int(4) NOT NULL DEFAULT '0',
	userid int(11) NOT NULL DEFAULT '0',
	username varchar(50) DEFAULT NULL,
	PRIMARY KEY (id)
);";

$TABLE[] = "CREATE TABLE IF NOT EXISTS bbcodeTables_sessions (
	sid int(11) NOT NULL AUTO_INCREMENT,
	bbcodeid int(11) NOT NULL DEFAULT '0',
	time int(11) NOT NULL DEFAULT '0',
	session varchar(50) DEFAULT NULL,
	PRIMARY KEY (sid)
);";
