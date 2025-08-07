<?php 
$SQL[] = "CREATE TABLE quiz_categories (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(140) CHARACTER SET utf8 NOT NULL,
  `category_seotitle` varchar(140) CHARACTER SET utf8 NOT NULL,
  `category_display_order` int(11) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM;";

?>