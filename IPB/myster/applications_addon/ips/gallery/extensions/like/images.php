<?php
/**
 * @file		images.php 	Images like class (gallery application)
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-06-20 22:48:36 -0400 (Wed, 20 Jun 2012) $
 * @version		v5.0.5
 * $Revision: 10965 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		like_gallery_images_composite
 * @brief		Images like class (gallery application)
 */
class like_gallery_images_composite extends classes_like_composite
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
		// Set gallery objects
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}

		//-----------------------------------------
		// Load language file
		//-----------------------------------------

		$this->lang->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
	}
	
	/**
	 * Return an array of acceptable frequencies. 
	 * Possible: immediate, offline, daily, weekly
	 * 
	 * @return	@e array
	 */
	public function allowedFrequencies()
	{
		return array( 'immediate', 'offline' );
	}
	
	/**
	 * Return type of notification available for this item
	 * 
	 * @return	@e array
	 */
	public function getNotifyType()
	{
		return array( 'comments', $this->lang->words['comments_lower'] );
	}
	
	/**
	 * Gets the vernacular (like or follow)
	 *
	 * @return	@e string
	 */
	public function getVernacular()
	{
		return 'follow_image';
	}
	
	/**
	 * Fetch the template group
	 * 
	 * @return	@e string
	 */
	public function skin()
	{
		return 'gallery_global';
	}
	
	/**
	 * Returns the type of item
	 * 
	 * @param	mixed	Relationship ID or array of
	 * @param	array	Array of meta to select (title, url, type, parentTitle, parentUrl, parentType) null fetches all
	 * @return	@e array	Meta data
	 */
	public function getMeta( $relId, $selectType=null )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$return		= array();
		$isNumeric	= false;
		
		if ( is_numeric( $relId ) )
		{
			$relId		= array( intval($relId) );
			$isNumeric	= true;
		}

		//-----------------------------------------
		// Fetch
		//-----------------------------------------

		$this->DB->build( array(
								'select'	=> 'i.*',
								'from'		=> array( 'gallery_images' => 'i' ),
								'where'		=> 'i.image_id IN (' . implode( ',', $relId ) . ')',
								'add_join'	=> array(
													array(
														'select' => 'a.*',
														'from'   => array( 'gallery_albums' => 'a' ),
														'where'  => 'i.image_album_id=a.album_id',
														'type'   => 'left'
														)
													)
						)		);
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$category	= $this->registry->getClass('categories')->fetchCategory( $row['image_category_id'] );

			//-----------------------------------------
			// Find the title
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'title', $selectType ) ) )
			{
				$return[ $row['image_id'] ]['like.title']	= $row['image_caption'];
			} 
			
			//-----------------------------------------
			// Find the URL
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'url', $selectType ) ) )
			{
				$return[ $row['image_id'] ]['like.url']		= $this->registry->output->buildSEOUrl( "app=gallery&amp;image=" . $row['image_id'], "public", $row['image_caption_seo'], "viewimage" );
			}
			
			//-----------------------------------------
			// Find the type
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'type', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.type']			= $this->lang->words['modcp_gal_caption'];
			} 
			
			//-----------------------------------------
			// Find the parent's title
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['image_id'] ]['like.parentTitle']	= $row['image_album_id'] ? $row['album_name'] : $category['category_name'];
			} 
			
			//-----------------------------------------
			// Find the parent's URL
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['image_id'] ]['like.parentUrl']	= $row['image_album_id'] ? 
																	$this->registry->output->buildSEOUrl( "app=gallery&amp;album=" . $row['album_id'], "public", $row['album_name_seo'], "viewalbum" ) :
																	$this->registry->output->buildSEOUrl( "app=gallery&amp;category=" . $category['category_id'], "public", $category['category_name_seo'], "viewcategory" );
			} 
			
			//-----------------------------------------
			// Find the parent's type
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentType', $selectType ) ) )
			{
				$return[ $row['image_id'] ]['like.parentType']	= $row['image_album_id'] ? $this->lang->words['viewimg_album'] : $this->lang->words['viewing_category'];
			} 
		}
		
		return ( $isNumeric === true ) ? array_pop( $return ) : $return;
	}
}