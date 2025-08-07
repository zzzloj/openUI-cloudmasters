<?php 

$SQL[] = "ALTER TABLE quiz_questions
	ADD `question_parent_id` int(11) NOT NULL;"
$SQL[] = "ALTER TABLE quiz_questions
	ADD `question_is_correct` int(11) NOT NULL;"	
$SQL[] = "ALTER TABLE quiz_answers
	ADD `is_correct_answer` int(11) NOT NULL;"
$SQL[] = "ALTER TABLE quiz_answers
	ADD `answer_score` text NOT NULL;"
?>