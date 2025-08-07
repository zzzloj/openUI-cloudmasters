<?php

//-----------------------------------------------
// (DP32) Global Sidebars
//-----------------------------------------------
//-----------------------------------------------
// Core Variables
//-----------------------------------------------
// Author: DawPi
// Site: http://www.ipslink.pl
// Written on: 04 / 02 / 2011
//-----------------------------------------------
// Copyright (C) 2011 DawPi
// All Rights Reserved
//----------------------------------------------- 

		   
$CACHE['gs_sidebars'] 	= array( 
									'array'				=> 1,
								   	'allow_unload'		=> 0,
								   	'default_load'		=> 1,
								   	'recache_file'		=> IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/library.php',
								   	'recache_class'		=> 'gsLibrary',
								   	'recache_function'	=> 'updateSidebarsCache'
								);		
								
$CACHE['gs_adverts'] 	= array( 
									'array'				=> 1,
								   	'allow_unload'		=> 0,
								   	'default_load'		=> 1,
								   	'recache_file'		=> IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/library.php',
								   	'recache_class'		=> 'gsLibrary',
								   	'recache_function'	=> 'updateAdvertsCache'
								);																			   						   