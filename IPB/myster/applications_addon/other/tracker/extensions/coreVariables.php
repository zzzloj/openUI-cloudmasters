<?php

$_LOAD = array(
	'tracker_fields'          => 1,
	'tracker_field_templates' => 1,
	'tracker_mods'            => 1,
	'tracker_modules'         => 1,
	'tracker_files'           => 0,
	'tracker_projects'        => 1,
	'tracker_stats'           => 1
);

//------------------------------

$CACHE['tracker_fields'] = array( 
	'array'            => 1,
	'allow_unload'     => 0,
	'default_load'     => 1,
	'recache_file'     => IPSLib::getAppDir( 'tracker' ) . '/sources/classes/fields.php',
	'recache_class'    => 'tracker_core_cache_fields',
	'recache_function' => 'rebuild'
);

$CACHE['tracker_mods'] = array( 
	'array'            => 1,
	'allow_unload'     => 0,
	'default_load'     => 1,
	'recache_file'     => IPSLib::getAppDir( 'tracker' ) . '/sources/classes/moderators.php',
	'recache_class'    => 'tracker_core_cache_moderators',
	'recache_function' => 'rebuild'
);

$CACHE['tracker_modules'] = array( 
	'array'            => 1,
	'allow_unload'     => 0,
	'default_load'     => 1,
	'recache_file'     => IPSLib::getAppDir( 'tracker' ) . '/sources/classes/library.php',
	'recache_class'    => 'tracker_core_cache_modules',
	'recache_function' => 'rebuild'
);

$CACHE['tracker_files'] = array( 
	'array'            => 1,
	'allow_unload'     => 0,
	'default_load'     => 0,
	'recache_file'     => IPSLib::getAppDir( 'tracker' ) . '/sources/classes/cache_files.php',
	'recache_class'    => 'tracker_core_cache_files',
	'recache_function' => 'rebuild'
);

$CACHE['tracker_projects'] = array( 
	'array'            => 1,
	'allow_unload'     => 0,
	'default_load'     => 1,
	'recache_file'     => IPSLib::getAppDir( 'tracker' ) . '/sources/classes/projects.php',
	'recache_class'    => 'tracker_core_cache_projects',
	'recache_function' => 'rebuild'
);

$CACHE['tracker_stats'] = array( 
	'array'            => 1,
	'allow_unload'     => 0,
	'default_load'     => 1,
	'recache_file'     => IPSLib::getAppDir( 'tracker' ) . '/sources/classes/cache_stats.php',
	'recache_class'    => 'tracker_core_cache_stats',
	'recache_function' => 'rebuild'
);

// Load the module cache variables
if ( file_exists( DOC_IPS_ROOT_PATH . 'cache/tracker/coreVariables.php' ) && ipsRegistry::$request['section'] != 'setup' && ipsRegistry::$request['do'] != 'uninstall' )
{
	require_once( DOC_IPS_ROOT_PATH . 'cache/tracker/coreVariables.php' );
}


/**
* Array for holding reset information
*
* Populate the $_RESET array and ipsRegistry will do the rest
*/

$_RESET = array();

###### Redirect requests... ######
if ( $_REQUEST['autocom'] == 'tracker' || ! $_REQUEST['app'] )
{
	$_REQUEST['app'] = 'tracker';
	$_RESET['app']	 = 'tracker';
}

if( ! isset( $_REQUEST['module'] ) && ( isset( $_REQUEST['app'] ) && $_REQUEST['app'] == 'tracker' ) )
{
	$_RESET['module'] = 'projects';
}

# shortcut links
if ( $_REQUEST['showproject'] )
{
	$_RESET['app']     = 'tracker';
	$_RESET['module']  = 'projects';
	$_RESET['section'] = 'projects';
	$_RESET['pid']     = intval( $_REQUEST['showproject'] );
}

if ( $_REQUEST['showissue'] )
{
	$_RESET['app']     = 'tracker';
	$_RESET['module']  = 'projects';
	$_RESET['section'] = 'issues';
	$_RESET['iid']     = intval( $_REQUEST['showissue'] );	
}

# ALL
if ( $_REQUEST['CODE'] or $_REQUEST['code'] )
{
	$_RESET['do'] = ( $_REQUEST['CODE'] ) ? $_REQUEST['CODE'] : $_REQUEST['code'];
}

if ( $_REQUEST['module'] == 'projects' && $_REQUEST['section'] == 'issues' )
{
	$_LOAD['emoticons']			= 1;
	$_LOAD['reputation_levels']	= 1;
	$_LOAD['attachtypes']		= 1;
	$_LOAD['ranks']         	= 1;
	$_LOAD['profilefields'] 	= 1;
	$_LOAD['bbcode']        	= 1;
}

?>