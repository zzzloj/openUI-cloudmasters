<?php

$SQL[] = "ALTER TABLE dp3_gs_sidebars ADD s_limit_at_once mediumint(5) NOT NULL";
$SQL[] = "ALTER TABLE dp3_gs_adverts ADD a_duplicate_id int(10) NOT NULL";
$SQL[] = "ALTER TABLE dp3_gs_adverts ADD a_pinned tinyint(1) NOT NULL";