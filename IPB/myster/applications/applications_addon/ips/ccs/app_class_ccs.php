<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS class loader
 * Last Updated: $Date: 2011-12-16 20:26:41 -0500 (Fri, 16 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10017 $
 */

if( !defined('DATABASE_FURL_MARKER') )
{
	define( 'DATABASE_FURL_MARKER', '_' );
}

class app_class_ccs
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Could potentially be setup from sessions
		//-----------------------------------------
		
		if( !$registry->isClassLoaded( 'ccsFunctions' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
			$registry->setClass( 'ccsFunctions', new $classToLoad( $registry ) );
		}
		
		//-----------------------------------------
		// This is set for topic markers extension
		//-----------------------------------------
		
		$request	=& $registry->fetchRequest();
		$request['_isDatabase']	= $registry->ccsFunctions->getDatabaseFurlString(); 
		
		if( IN_ACP )
		{
			if( !$registry->isClassLoaded( 'ccsAcpFunctions' ) )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/acpFunctions.php', 'ccsAcpFunctions', 'ccs' );
				$registry->setClass( 'ccsAcpFunctions', new $classToLoad( $registry ) );
			}
		}
	}
}