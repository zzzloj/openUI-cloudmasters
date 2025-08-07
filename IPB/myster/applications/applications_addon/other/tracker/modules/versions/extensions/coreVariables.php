<?php

$_LOAD['tracker_module_versions'] = 1;

//------------------------------

$CACHE['tracker_module_versions'] = array( 
	'array'            => 1,
	'allow_unload'     => 0,
	'default_load'     => 1,
	'recache_file'     => IPSLib::getAppDir( 'tracker' ) . '/modules/versions/sources/cache_versions.php',
	'recache_class'    => 'tracker_module_versions_cache_versions',
	'recache_function' => 'rebuild'
);

?>