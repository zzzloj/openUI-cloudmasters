<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */
 
	if ( ! $_REQUEST['module'] )
	{
		$_RESET['module'] = 'testemunhos';
	}
	
	$CACHE['testemunhos'] = array( 'array'            => 1,
								   'allow_unload'     => 0,
								   'default_load'     => 1,
								   'recache_file'     => IPSLib::getAppDir( 'testimonials' ) . '/sources/classes/library.php',
								   'recache_class'    => 'testemunhosLibrary',
								   'recache_function' => 'rebuildTestemunhosCache'
	);

	if ( isset($_REQUEST['showtestimonial']) && intval($_REQUEST['showtestimonial']) > 0 )
	{
		$_RESET['module']  = 'testemunhos';
		$_RESET['section'] = 'view';
		$_RESET['t']       = intval($_REQUEST['showtestimonial']);
	}

	if ( $_REQUEST['showtestimonial'] > 0 )
	{
		$_LOAD['reputation_levels'] = 1;
	}

?>