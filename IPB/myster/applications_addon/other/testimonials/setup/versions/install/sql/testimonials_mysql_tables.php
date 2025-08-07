<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonial System
 * @version 1.1.0
 */

$TABLE[] = "CREATE TABLE testemunhos_cats (
	c_id int(10) NOT NULL AUTO_INCREMENT,
	c_name varchar(50) NOT NULL DEFAULT '',
	c_name_seo varchar(70) NOT NULL DEFAULT '',
	c_desc varchar(255) NOT NULL DEFAULT '',
	c_test int(11) NOT NULL DEFAULT '0',
	c_views int(11) NOT NULL DEFAULT '0',
	c_pos int(11) NOT NULL,
	PRIMARY KEY (c_id)
);";

$TABLE[] = "CREATE TABLE testemunhos_ratings (
  rating_id int(10) NOT NULL AUTO_INCREMENT,
  rating_tid int(10) NOT NULL,
  rating_member_id int(10) NOT NULL,
  rating_value int(1) NOT NULL,
  rating_ip_address int(20) NOT NULL,
  PRIMARY KEY (rating_id)
);";


$TABLE[] = "CREATE TABLE testemunhos (
  t_id INT(10) NOT NULL AUTO_INCREMENT,
  t_date INT(10) NOT NULL,
  t_last_comment INT(10) NOT NULL,
  t_views INT(10) NOT NULL,
  t_comments INT(10) NOT NULL DEFAULT '0',
  t_rating TINYINT(1) NOT NULL DEFAULT '0',
  t_rating_hits TINYINT(10) NOT NULL,
  t_approved TINYINT(1) NOT NULL,
  t_pinned TINYINT(1) NOT NULL,
  t_open TINYINT(1) NOT NULL,
  t_member_id MEDIUMINT(8) NOT NULL,
  t_title varchar(255) NOT NULL DEFAULT '',
  t_title_seo varchar(255) NOT NULL,
  t_cat TINYINT(1) NOT NULL,
  t_content TEXT NOT NULL,
  t_htmlstate TINYINT(1) NOT NULL,
  t_ip_address varchar(16) NOT NULL DEFAULT '',
  t_topicid INT(10) NOT NULL DEFAULT '0',
  t_append_edit TINYINT(1) NOT NULL,
  t_append_edit_time INT(10) NOT NULL,
  t_append_edit_author varchar(255) NOT NULL default '',
  PRIMARY KEY(t_id)
);";

$TABLE[] = "CREATE TABLE testemunhos_comments (
	cid int(11) NOT NULL AUTO_INCREMENT,
	tid int(11) NOT NULL DEFAULT '0',
	member_id int(11) NOT NULL DEFAULT '0',
	date int(11) NOT NULL DEFAULT '0',
	comment text NOT NULL,
	pid int(8) NOT NULL DEFAULT '0',
  	append_edit TINYINT(1) NOT NULL,
  	append_edit_time INT(10) NOT NULL,
  	append_edit_author varchar(255) NOT NULL default '',
	PRIMARY KEY (cid)
);";

$TABLE[] = "CREATE TABLE testemunhos_modlogs (
	id int(10) NOT NULL AUTO_INCREMENT,
	t_id int(10) NOT NULL DEFAULT '0',
	t_title varchar(250) DEFAULT NULL,
	member_id mediumint(8) NOT NULL DEFAULT '0',
	name varchar(255) NOT NULL default '',
	ip_address varchar(16) NOT NULL DEFAULT '0',
	datetime int(10) DEFAULT NULL,
	action varchar(255) DEFAULT NULL,
	PRIMARY KEY (id),
	KEY ip_address (ip_address)
)";

$TABLE[] = "CREATE TABLE testemunhos_topicsconverted (
	id int(10) NOT NULL AUTO_INCREMENT,
	member_id mediumint(8) NOT NULL DEFAULT '0',
	forum_id smallint(5) NOT NULL DEFAULT '0',
	topic_id INT(10) NOT NULL DEFAULT '0',
	datetime int(10) DEFAULT NULL,
	PRIMARY KEY(id)
)";

$TABLE[] = "CREATE TABLE testemunhos_tracker (
	id mediumint(8) NOT NULL AUTO_INCREMENT,
	member_id mediumint(8) NOT NULL DEFAULT '0',
	t_id int(10) NOT NULL DEFAULT '0',
	start_date int(10) DEFAULT NULL,
	type char(5) NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	KEY t_id (t_id),
	KEY testemunho_member (member_id,t_id)
)";