<?php 
$SQL[] = "ALTER TABLE quiz_quizzes
	ADD `quiz_approved` int(11) NOT NULL;";
$SQL[] = "ALTER TABLE quiz_quizzes
	ADD `quiz_support_topic` int(11) NOT NULL;"
?>