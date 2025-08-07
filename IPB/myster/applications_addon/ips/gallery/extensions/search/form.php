<?php
/**
 * @file		form.php 	IP.Gallery search form plugin
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-07-13 18:03:32 -0400 (Fri, 13 Jul 2012) $
 * @version		v5.0.5
 * $Revision: 11076 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_form_gallery
{
	/**
	 * Construct
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		//-----------------------------------------
		// Set registry objects
		//-----------------------------------------

		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		//-----------------------------------------
		// Load our gallery object
		//-----------------------------------------

		if ( ! ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		//-----------------------------------------
		// Load language file
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
	}
	
	/**
	 * Return sort drop down
	 * 
	 * @return	@e array
	 */
	public function fetchSortDropDown()
	{
		$array	= array( 
						'images'	=> array(
											'date'		=> $this->lang->words['search_sort_date'],
											'title'		=> $this->lang->words['search_sort_caption'],
											'views'		=> $this->lang->words['search_sort_views'],
											'comments'	=> $this->lang->words['search_sort_comments'],
										),
						'comments'	=> array(
											'date'		=> $this->lang->words['s_search_type_0'],
											),
						'albums'	=> array(
											'date'		=> $this->lang->words['s_search_type_0'],
											)
					);

		return $array;
	}
	
	/**
	 * Return sort in options
	 * 
	 * @return	@e array
	 */
	public function fetchSortIn()
	{
		if( $this->request['search_tags'] )
		{
			$array	= array(
							array( 'images',	$this->lang->words['advsearch_images'] ),
							);
		}
		else
		{
			$array	= array(
							array( 'images',	$this->lang->words['advsearch_images'] ),
							array( 'comments',	$this->lang->words['advsearch_comments'] ),
							array( 'albums',	$this->lang->words['advsearch_albums'] )  
							);
		}

		return $array;
	}
	
	/**
	 * Retrieve HTML for additional filtering options
	 *
	 * @return	@e string	Filter HTML
	 */
	public function getHtml()
	{
		$categories	= $this->registry->gallery->helper('categories')->catJumpList( true, 'images' );
		
		return array( 'title' => IPSLIb::getAppTitle('gallery'), 'html' => ipsRegistry::getClass('output')->getTemplate('gallery_external')->galleryAdvancedSearchFilters( $categories ) );
	}
}