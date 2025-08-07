<?php
/**
 * @file		generate.php 	Navigation plugin: Gallery
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $ < I wrote this actually you glory stealer (-Matt x)
 * @since		3/8/2011
 * $LastChangedDate: 2012-06-22 16:33:20 -0400 (Fri, 22 Jun 2012) $
 * @version		v5.0.5
 * $Revision: 10979 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		navigation_gallery
 * @brief		Provide ability to share attachments via editor
 */
class navigation_gallery
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
	 * @return	@e void
	 */
	public function __construct() 
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			=  $this->registry->class_localization;
		
		if ( ! ipsRegistry::instance()->isClassLoaded( 'gallery') )
		{ 
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			ipsRegistry::instance()->setClass( 'gallery', new $classToLoad( ipsRegistry::instance() ) );
		}
		
		//-----------------------------------------
		// Grab language file
		//-----------------------------------------

		$this->lang->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
	}
	
	/**
	 * Return the tab title
	 *
	 * @return	@e string
	 */
	public function getTabName()
	{ 
		return IPSLib::getAppTitle( 'gallery' );
	}
	
	/**
	 * Returns navigation data
	 *
	 * @return	array	array( array( 0 => array( 'title' => 'x', 'url' => 'x' ) ) );
	 */
	public function getNavigationData()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$blocks		= array();
		$categories	= $this->_getCategories();
		$albums		= $this->_getAlbums();
			
		//-----------------------------------------
		// Add to the blocks
		//-----------------------------------------

		$blocks[]	= array( 'title' => $this->lang->words['g_nav_global'], 'links' => $categories );
		
		if ( is_array( $albums ) && count( $albums ) )
		{
			$blocks[]	= array( 'title' => $this->lang->words['g_nav_your'], 'links' => $albums );
		}
		
		return $blocks;
	}
	
	/**
	 * Fetches your albums
	 *
	 * @return	string
	 */
	private function _getAlbums()
	{
		$links	= array();
		
		if ( $this->memberData['member_id'] )
		{
			$mine	= $this->registry->gallery->helper('albums')->fetchAlbumsByOwner( $this->memberData['member_id'], array( 'limit' => 150, 'isViewable' => 1 ) );
			
			if ( count( $mine ) )
			{
				foreach( $mine as $id => $album )
				{
					$links[]	= array( 'depth' => 0, 'title' => '<strong>' . $album['album_name'] . '</strong>', 'url' => $this->registry->output->buildSEOUrl( 'app=gallery&amp;album=' . $album['album_id'], 'public', $album['album_name_seo'], 'viewalbum' ) );
				}
			}
		}
		
		return $links;
	}
	
	/**
	 * Fetches categories
	 *
	 * @return	string
	 */
	private function _getCategories()
	{
		$depth_guide	= 0;
		$links			= array();
		
		//-----------------------------------------
		// Get categories
		//-----------------------------------------

		foreach( $this->registry->gallery->helper('categories')->cat_cache[0] as $id => $cat )
		{
			if( $this->registry->gallery->helper('categories')->isViewable( $id ) )
			{
				$links[]	= array( 'important' => true, 'depth' => $depth_guide, 'title' => $cat['category_name'], 'url' => $this->registry->output->buildSEOUrl( 'app=gallery&amp;category=' . $cat['category_id'], 'public', $cat['category_name_seo'], 'viewcategory' ) );

				$links		= $this->_getRecursiveCategories( $cat['category_id'], $links, $depth_guide );
			}
		}
		
		return $links;
	}
	
	/**
	 * Internal helper function to recursively get categories
	 *
	 * @param	integer	$root_id
	 * @param	array	$links
	 * @param	string	$depth_guide
	 * @return	string
	 */
	private function _getRecursiveCategories( $categoryId, $links=array(), $depth_guide=0 )
	{
		if ( is_array( $this->registry->gallery->helper('categories')->cat_cache[ $categoryId ] ) )
		{
			$depth_guide++;
			
			foreach( $this->registry->gallery->helper('categories')->cat_cache[ $categoryId ] as $id => $cat )
			{
				$links[]	= array( 'depth' => $depth_guide, 'title' => $cat['category_name'], 'url' => $this->registry->output->buildSEOUrl( 'app=gallery&amp;category=' . $cat['category_id'], 'public', $cat['category_name_seo'], 'viewcategory' ) );
								
				$links = $this->_getRecursiveCategories( $id, $links, $depth_guide );
			}
		}
		
		return $links;
	}
}