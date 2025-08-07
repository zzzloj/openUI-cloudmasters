<?php 
// 1.1 upgrade file?
// long questions were being broken up.
$SQL[] = "ALTER TABLE quiz_questions CHANGE question_name question_name text";
// add the stuff for promote.
$SQL[] = "ALTER TABLE quiz_quizzes 
ADD `quiz_promote_group_id` int(11) DEFAULT 0,
ADD `quiz_group_promo_score` int(11) DEFAULT 0,
ADD `quiz_timelimit` int(11) NOT NULL DEFAULT 0;
";
// add new correct answer field
$SQL[] = "ALTER TABLE quiz_answers
ADD `correct_answer_name` text;";