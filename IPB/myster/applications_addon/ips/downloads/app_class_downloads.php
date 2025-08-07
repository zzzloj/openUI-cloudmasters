<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IP.Download Manager class loader
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		1st April 2004
 * @version		$Revision: 10721 $
 */

define( 'DL_VERSION'	, '2.5.4' );
define( 'DL_RVERSION'	, '25005'	);
define( 'DL_LINK'		, 'http://www.invisionpower.com/latestversioncheck/ipdownloads.php?v=' );

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * IP.Download Manager class loader
 * @package	IP.Downloads
 */
class app_class_downloads
{
	/**
	 * Constructor
	 *
	 * @param	object	ipsRegistry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Fix settings in case ../ was used
		//-----------------------------------------
		
		ipsRegistry::$settings['idm_localsspath']	= str_replace( "&#46;&#46;/", '../', ipsRegistry::$settings['idm_localsspath'] );
		ipsRegistry::$settings['idm_localfilepath']	= str_replace( "&#46;&#46;/", '../', ipsRegistry::$settings['idm_localfilepath'] );
		
		//-----------------------------------------
		// Make sure caches were loaded
		//-----------------------------------------
		
		$registry->cache()->getCache( array( 'idm_mods', 'idm_cats' ) );
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'downloads' ) . "/sources/classes/categories.php", 'class_categories', 'downloads' );
		
		$registry->setClass( 'categories', new $classToLoad( $registry ) );
		
		if( IN_ACP )
		{
			$registry->getClass('categories')->fullInit();
			
			/* Set a default module */
			if( ! ipsRegistry::$request['module'] )
			{
				ipsRegistry::$request['module']	= 'index';
			}
		}
		else
		{
			$registry->getClass('categories')->normalInit();
			
			$registry->getClass('class_localization')->loadLanguageFile( array( 'public_downloads' ), 'downloads' );
		}

		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'downloads' ) . "/sources/classes/functions.php", 'downloadsFunctions', 'downloads' );

		$registry->setClass( 'idmFunctions', new $classToLoad( $registry ) );
		
		//-----------------------------------------
		// Nexus currency
		//-----------------------------------------
		
		if ( IPSLib::appIsInstalled('nexus') and ipsRegistry::$settings['nexus_currency_locale'] )
		{
			setlocale( LC_MONETARY, ipsRegistry::$settings['nexus_currency_locale'] );
			ipsRegistry::getClass('class_localization')->local_data = localeconv();
		}
		
	}
	
	/**
	 * After output initialization
	 *
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function afterOutputInit( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Check if we are online
		//-----------------------------------------

		if( !IN_ACP )
		{	
			if( !defined('SKIP_ONLINE_CHECK') OR !SKIP_ONLINE_CHECK )
			{
				$registry->getClass('idmFunctions')->checkOnline();
			}
		}
		
		if( ipsRegistry::$request['showcat'] )
		{
			$category	= $registry->getClass('categories')->cat_lookup[ $_GET['showcat'] ];

			$registry->getClass('output')->checkPermalink( $category['cname_furl'] );
		}

		if( ipsRegistry::$request['request_method'] == 'get' )
		{
			if( $_GET['autocom'] == 'downloads' or $_GET['automodule'] == 'downloads' )
			{
				$registry->output->silentRedirect( ipsRegistry::$settings['base_url'] . "app=downloads", 'false', true, 'app=downloads' );
			}
		}
	}
}