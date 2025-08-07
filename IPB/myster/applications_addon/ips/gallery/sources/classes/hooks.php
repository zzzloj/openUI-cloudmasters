<?php
/**
 * @file		hooks.php 	Gallery hooks library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-07-27 17:45:29 -0400 (Fri, 27 Jul 2012) $
 * @version		v5.0.5
 * $Revision: 11151 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		app_gallery_classes_hooks
 * @brief		Gallery hooks library
 */
class app_gallery_classes_hooks
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
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Set shortcuts
		//-----------------------------------------

		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		//-----------------------------------------
		// Ensure our Gallery object is loaded
		//-----------------------------------------

		if ( !$this->registry->isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
	}
	
	/**
	 * Hook: Recent images on board index
	 *
	 * @return	@e string
	 */
	public function hookBoardIndexRecentImages()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$recents = array();
		
		//-----------------------------------------
		// Fetch the most recent 20 images
		//-----------------------------------------

		if ( $this->memberData['g_gallery_use'] && IPSLib::appIsInstalled('gallery') )
		{
			$recents = $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array( 'limit' => 20, 'sortKey' => 'date', 'sortOrder' => 'desc', 'unixCutOff' => GALLERY_A_YEAR_AGO, 'thumbClass' => 'galattach', 'link-thumbClass' => 'galimageview' ) );
		}
		
		//-----------------------------------------
		// If we have any, output them
		//-----------------------------------------

		return count( $recents ) ? $this->registry->output->getTemplate( 'gallery_global' )->hookRecentGalleryImages( $recents ) : '';
	}
}