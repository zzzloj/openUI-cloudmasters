<?php 
$SQL[] = "CREATE TABLE quiz_quizzes (
  `quiz_id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_name` varchar(140) CHARACTER SET utf8 NOT NULL,
  `quiz_seotitle` varchar(140) CHARACTER SET utf8 NOT NULL,
  `quiz_timestamp` int(11) NOT NULL,
  `quiz_starter_id` int(11) NOT NULL,
  `quiz` longtext NOT NULL,  
  PRIMARY KEY (`quiz_id`)
) ENGINE=MyISAM;";
?>