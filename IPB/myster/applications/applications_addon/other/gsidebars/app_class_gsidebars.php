<?php

//-----------------------------------------------
// (DP33) Global Sidebars
//-----------------------------------------------
//-----------------------------------------------
// Loader
//-----------------------------------------------
// Author: DawPi
// Site: http://www.ipslink.pl
// Written on: 04 / 02 / 2011
// Updated on: 20 / 06 / 2011
//-----------------------------------------------
// Copyright (C) 2011 DawPi
// All Rights Reserved
//-----------------------------------------------   

class app_class_gsidebars
{
	public function __construct( ipsRegistry $registry )
	{
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/library.php', 'gsLibrary', 'gsidebars' );
		$registry->setClass( 'gsLibrary', new $classToLoad( $registry ) );
        
        $cache = ipsRegistry::cache()->getCache('app_cache');
        if( $cache['nexus'] && IPSLib::appIsInstalled( 'nexus' ) && is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
        {
    		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php', 'gsLibraryNexus', 'gsidebars' );
    		$registry->setClass( 'gsLibraryNexus', new $classToLoad( $registry ) );
        }			
		
		if ( IN_ACP )
		{			
			$registry->getClass('class_localization')->loadLanguageFile( array( 'admin_gsidebars' ),  'gsidebars' );
		}
		else
		{
			$registry->getClass('class_localization')->loadLanguageFile( array( 'public_gsidebars' ), 'gsidebars' );
		}
	}		
}// End of class

# Act error numbers
// DP31GS_026