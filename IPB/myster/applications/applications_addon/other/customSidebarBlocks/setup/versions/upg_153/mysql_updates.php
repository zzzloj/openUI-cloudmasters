<?php

// Add new block setting to hide block (goes with new export feature)
$SQL[] = "ALTER TABLE custom_sidebar_blocks ADD csb_hide_block TINYINT(1) NOT NULL default '0';";