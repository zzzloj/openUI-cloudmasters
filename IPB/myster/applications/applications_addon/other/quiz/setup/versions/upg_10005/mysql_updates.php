<?php 

$SQL[] = "CREATE TABLE quiz_questions (
  `question_id` int(11) NOT NULL AUTO_INCREMENT,
  `question_name` varchar(140) CHARACTER SET utf8 NOT NULL,
  `question_seotitle` varchar(140) CHARACTER SET utf8 NOT NULL,
  `question_timestamp` int(11) NOT NULL,
  `question` longtext NOT NULL,
  `quiz_id` int(11) NOT NULL,
  PRIMARY KEY (`question_id`)
) ENGINE=MyISAM;";

$SQL[] = "CREATE TABLE quiz_answers (
  `answer_id` int(11) NOT NULL AUTO_INCREMENT,
  `answer_name` varchar(140) CHARACTER SET utf8 NOT NULL,
  `answer_seotitle` varchar(140) CHARACTER SET utf8 NOT NULL,
  `answer_timestamp` int(11) NOT NULL,
  `answer_user_id` int(11) NOT NULL,
  `answer` longtext NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  PRIMARY KEY (`answer_id`)
) ENGINE=MyISAM;";
?>