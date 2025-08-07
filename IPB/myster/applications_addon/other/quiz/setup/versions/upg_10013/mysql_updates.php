<?php
// Fix for Quiz Counts
$SQL[] = "UPDATE quiz_quizzes SET quiz_public='1' WHERE quiz_approved='1'";