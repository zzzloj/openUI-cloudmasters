<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

/**
* Array for holding reset information
*
* Populate the $_RESET array and ipsRegistry will do the rest
*/

$_LOAD = array();

$_LOAD = array(
				'forms'		=> 1,
				'form_fields'	=> 1,			
				);

$CACHE['forms']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'form' ) . '/sources/forms.php',
								'recache_class'		=> 'class_forms',
								'recache_function'	=> 'rebuild_forms' 
							);
                            
$CACHE['form_fields']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'form' ) . '/sources/fields.php',
								'recache_class'		=> 'class_fields',
								'recache_function'	=> 'rebuild_fields' 
							);                            


if ( $_REQUEST['module'] == 'post' )
{
	$_LOAD['attachtypes'] = 1;
	$_LOAD['bbcode']      = 1;
	$_LOAD['badwords']    = 1;
	$_LOAD['emoticons']   = 1;
}

if ( $_REQUEST['module'] == 'display' && $_REQUEST['section'] == 'index' )
{
	$_LOAD['emoticons'] = 1;
}

?>