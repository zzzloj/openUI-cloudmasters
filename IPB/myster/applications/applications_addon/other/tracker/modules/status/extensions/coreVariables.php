<?php

$_LOAD['tracker_module_status'] = 1;

//------------------------------

$CACHE['tracker_module_status'] = array( 
	'array'            => 1,
	'allow_unload'     => 0,
	'default_load'     => 1,
	'recache_file'     => IPSLib::getAppDir( 'tracker' ) . '/modules/status/sources/cache_status.php',
	'recache_class'    => 'tracker_module_status_cache_status',
	'recache_function' => 'rebuild'
);

?>