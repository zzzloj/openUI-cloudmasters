<?php

$_LOAD['tracker_module_custom'] = 1;

//------------------------------

$CACHE['tracker_module_custom'] = array( 
	'array'            => 1,
	'allow_unload'     => 0,
	'default_load'     => 1,
	'recache_file'     => IPSLib::getAppDir( 'tracker' ) . '/modules/custom/sources/cache_custom.php',
	'recache_class'    => 'tracker_module_custom_cache_custom',
	'recache_function' => 'rebuild'
);

?>