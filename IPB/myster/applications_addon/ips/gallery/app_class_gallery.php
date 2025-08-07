<?php
/**
 * @file		app_class_gallery.php 	IP.Gallery class loader
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: AndyMillne $
 * @since		27th January 2004
 * $LastChangedDate: 2012-09-17 15:02:32 -0400 (Mon, 17 Sep 2012) $
 * @version		v5.0.5
 * $Revision: 11343 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class app_class_gallery
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @param	object		ipsRegistry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Get our Gallery object
		//-----------------------------------------

		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$registry->setClass( 'gallery', new $classToLoad( $registry ) );
		}
		
		//-----------------------------------------
		// Load some public side stuff
		//-----------------------------------------

		if( !IN_ACP )
		{
			//-----------------------------------------
			// Language file
			//-----------------------------------------

			$registry->class_localization->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
			
			//-----------------------------------------
			// Set default module
			//-----------------------------------------

			if ( ! ipsRegistry::$request['module'] )
			{
				ipsRegistry::$request['module'] = 'albums';
			}
		}

		//-----------------------------------------
		// Set some global constants we'll use
		//-----------------------------------------

		define( 'GALLERY_A_YEAR_AGO', time() - ( 86400 * 365 ) );
		define( 'GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE', ( ipsRegistry::$settings['gallery_images_per_page'] > 0 ) ? ipsRegistry::$settings['gallery_images_per_page'] : 50 );
		define( 'GALLERY_ALBUMS_PER_PAGE', ( ipsRegistry::$settings['gallery_albums_perpage'] > 0 ) ? ipsRegistry::$settings['gallery_albums_perpage'] : 20 );
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
		// More public side stuff...
		//-----------------------------------------

		if ( ! IN_ACP )
		{
			//-----------------------------------------
			// Can we access Gallery?
			//-----------------------------------------

			$registry->getClass('gallery')->checkGlobalAccess();

			//-----------------------------------------
			// Load some global JS/CSS
			//-----------------------------------------

			$registry->getClass('output')->addContent( $registry->getClass('output')->getTemplate('gallery_global')->globals() );
			
			//-----------------------------------------
			// Invalid non-legacy URL?
			//-----------------------------------------

			if( ipsRegistry::$request['request_method'] == 'get' && empty( ipsRegistry::$request['legacy'] ) )
			{
				if( $_GET['autocom'] == 'gallery' or $_GET['automodule'] == 'gallery' )
				{
					$registry->output->silentRedirect( ipsRegistry::$settings['base_url'] . "app=gallery", 'false', true, 'app=gallery' );
				}
			}
		}
	}	
}