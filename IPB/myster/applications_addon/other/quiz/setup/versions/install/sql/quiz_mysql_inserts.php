<?php 
$INSERT[] = "ALTER TABLE groups
	ADD `g_quiz_can_take_quiz` int(11) DEFAULT 1,
	ADD `g_quiz_can_view_quiz` int(11) DEFAULT 1,
	ADD `g_quiz_can_add_quiz` int(11) DEFAULT 1";
?>