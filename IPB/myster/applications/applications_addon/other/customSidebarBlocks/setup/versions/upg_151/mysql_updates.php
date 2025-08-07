<?php

// Add new block setting to hide collapse button
$SQL[] = "ALTER TABLE custom_sidebar_blocks ADD csb_no_collapse TINYINT(1) NOT NULL default '0';";