<?php
/**
 * (e$30) Custom Sidebar Blocks 
 * 1.0.0->1.1.0 DB Updates
 */

$SQL[] = "ALTER TABLE custom_sidebar_blocks ADD csb_raw TINYINT(1) NOT NULL DEFAULT '0';";

?>