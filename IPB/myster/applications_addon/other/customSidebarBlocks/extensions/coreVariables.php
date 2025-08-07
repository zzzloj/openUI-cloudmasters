<?php
/**
 * (e$30) Custom Sidebar Blocks
 * version: 1.0.0
 */

//-----------------------------------------
// Extension File: Registered Caches
//-----------------------------------------

//$_LOAD = array();

#Banks
$CACHE['custom_sidebar_blocks'] = array( 
										'array'            => 1,
										'allow_unload'     => 0,
										'default_load'     => 1,
										'recache_file'     => IPSLib::getAppDir('customSidebarBlocks' ) . '/modules_admin/customSidebarBlocks/core.php',
										'recache_class'    => 'admin_customSidebarBlocks_customSidebarBlocks_core',
										'recache_function' => 'rebuildBlockCache' 
						               );