<?php

// Add new block setting to force PHP mode
$SQL[] = "ALTER TABLE custom_sidebar_blocks ADD csb_php TINYINT(1) NOT NULL default '0';";