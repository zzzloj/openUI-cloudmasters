<?php 

$TABLE[] = "CREATE TABLE quiz_answers (
  `answer_id` int(11) NOT NULL AUTO_INCREMENT,
  `answer_user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `is_correct_answer` int(11) NOT NULL,
  `answer_key` text CHARACTER SET utf8 NOT NULL COMMENT 'unique key per batch of answers so we can take the quiz more than once..',
  `timestamp` text CHARACTER SET utf8 NOT NULL,
  `correct_answer_name` text CHARACTER SET utf8 NOT NULL,
  `answer_name` text CHARACTER SET utf8 NOT NULL,
  `you_answered` text CHARACTER SET utf8 NOT NULL,
  `answer_score` text NOT NULL,
  PRIMARY KEY (`answer_id`)
) ENGINE=MyISAM;";

$TABLE[] = "CREATE TABLE quiz_categories (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(140) CHARACTER SET utf8 NOT NULL,
  `category_image` text CHARACTER SET utf8 NOT NULL,
  `category_seotitle` varchar(140) CHARACTER SET utf8 NOT NULL,
  `category_display_order` int(11) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM;";

$TABLE[] = "CREATE TABLE quiz_leaders (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `answer_key` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;";

$TABLE[] = "CREATE TABLE quiz_questions (
  `question_id` int(11) NOT NULL AUTO_INCREMENT,
  `question_name` text CHARACTER SET utf8 NOT NULL,
  `question_seotitle` varchar(140) CHARACTER SET utf8 NOT NULL,
  `question_timestamp` int(11) NOT NULL,
  `question` longtext NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_parent_id` int(11) NOT NULL,
  `question_is_correct` int(11) NOT NULL,
  PRIMARY KEY (`question_id`)
) ENGINE=MyISAM;";

$TABLE[] = "CREATE TABLE quiz_quizzes (
  `quiz_id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_name` varchar(140) CHARACTER SET utf8 NOT NULL,
  `quiz_seotitle` varchar(140) CHARACTER SET utf8 NOT NULL,
  `quiz_timestamp` int(11) NOT NULL,
  `quiz_starter_id` int(11) NOT NULL,
  `quiz` longtext NOT NULL,
  `quiz_category_id` int(11) NOT NULL,
  `quiz_support_topic` int(11) NOT NULL,
  `quiz_approved` int(11) NOT NULL,
  `quiz_public` int(11) NOT NULL DEFAULT 0,
  `quiz_promote_group_id` int(11) DEFAULT 0,
  `quiz_group_promo_score` int(11) DEFAULT 0,
  `quiz_timelimit` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`quiz_id`)
) ENGINE=MyISAM;";

?>
