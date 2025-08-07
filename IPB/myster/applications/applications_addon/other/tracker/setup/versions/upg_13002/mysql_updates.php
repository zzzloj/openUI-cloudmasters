<?php

global $DB;

$SQL[] = "ALTER TABLE tracker_categories ADD cat_reply_text TEXT NOT NULL";
$SQL[] = "ALTER TABLE tracker_categories ADD cat_fixed_status TINYINT(1) NOT NULL";

?>