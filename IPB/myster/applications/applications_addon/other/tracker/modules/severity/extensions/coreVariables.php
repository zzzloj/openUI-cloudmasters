<?php

$_LOAD['tracker_module_severity'] = 1;

//------------------------------

$CACHE['tracker_module_severity'] = array( 
	'array'            => 1,
	'allow_unload'     => 0,
	'default_load'     => 1,
	'recache_file'     => IPSLib::getAppDir( 'tracker' ) . '/modules/severity/sources/cache_severity.php',
	'recache_class'    => 'tracker_module_severity_cache_severity',
	'recache_function' => 'rebuild'
);

?>