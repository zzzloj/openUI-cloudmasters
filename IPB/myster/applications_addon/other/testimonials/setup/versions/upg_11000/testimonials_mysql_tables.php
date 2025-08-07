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