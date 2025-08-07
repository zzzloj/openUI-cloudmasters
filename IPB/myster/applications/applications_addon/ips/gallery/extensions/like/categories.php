<?php
/**
 * @file		categories.php 	Categories like class (gallery application)
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-06-15 18:18:40 -0400 (Fri, 15 Jun 2012) $
 * @version		v5.0.5
 * $Revision: 10935 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		like_gallery_categories_composite
 * @brief		Categories like class (gallery application)
 */
class like_gallery_categories_composite extends classes_like_composite
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
	 * Return types of notification available for this item
	 * 
	 * @return	@e array	array( key, human readable )
	 */
	public function getNotifyType()
	{
		return array( 'images', $this->lang->words['images_lower'] );
	}
	
	/**
	 * Gets the vernacular (like or follow)
	 *
	 * @return	@e string
	 */
	public function getVernacular()
	{
		return 'follow_category';
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
	 * @param	mixed		$relId			Relationship ID or array of IDs
	 * @param	array		$selectType		Array of meta to select (title, url, type, parentTitle, parentUrl, parentType) null fetches all
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
		// Loop over categories
		//-----------------------------------------

		foreach( $relId as $_category )
		{
			$category	= $this->registry->getClass('categories')->fetchCategory( $_category );
			$parent		= $this->registry->getClass('categories')->fetchCategory( $category['category_parent_id'] );

			//-----------------------------------------
			// Find the title
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'title', $selectType ) ) )
			{
				$return[ $category['category_id'] ]['like.title']		= $category['category_name'];
			} 
			
			//-----------------------------------------
			// Find the URL
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'url', $selectType ) ) )
			{
				$return[ $category['category_id'] ]['like.url']			= $this->registry->output->buildSEOUrl( "app=gallery&amp;category=" . $category['category_id'], "public", $category['category_name_seo'], "viewcategory" );
			}
			
			//-----------------------------------------
			// Find the type
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'type', $selectType ) ) )
			{
				$return[ $category['category_id'] ]['like.type']		= $this->lang->words['viewing_category'];
			} 
			
			//-----------------------------------------
			// Find the parent's title
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $category['category_id'] ]['like.parentTitle']	= ( ! empty( $parent['category_name'] ) ) ? $parent['category_name'] : null;
			} 
			
			//-----------------------------------------
			// Find the parent's URL
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $category['category_id'] ]['like.parentUrl']	= ( ! empty( $parent['category_name'] ) ) ? $this->registry->output->buildSEOUrl( "app=gallery&amp;category=" . $parent['category_id'], "public", $parent['category_name_seo'], "viewcategory" ) : null;
			} 
			
			//-----------------------------------------
			// Find the parent's type
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentType', $selectType ) ) )
			{
				$return[ $category['category_id'] ]['like.type']		= ( ! empty( $parent['category_name'] ) ) ? $this->lang->words['viewing_category'] : null;
			} 
		}
		
		return ( $isNumeric === true ) ? array_pop( $return ) : $return;
	}
	
	/**
	 * Return the title based on the passed id
	 * 
	 * @param	mixed		$relId		Relationship ID or array of IDs
	 * @return	@e mixed	Title, or array of titles
	 */
	public function getTitleFromId( $relId )
	{
		$meta	= $this->getMeta( $relId, array( 'title' ) );
		
		if ( is_numeric( $relId ) )
		{
			return $meta[ $relId ]['like.title'];
		}
		else
		{
			$return	= array();
			
			foreach( $meta as $id => $data )
			{
				$return[ $id ] = $data['like.title'];
			}
			
			return $return;
		}
	}

	/**
	 * Return the URL based on the passed id
	 * 
	 * @param	mixed		$relId		Relationship ID or array of IDs
	 * @return	@e mixed	URL, or array of URLs
	 */
	public function getUrlFromId( $relId )
	{
		$meta	= $this->getMeta( $relId, array( 'url' ) );
		
		if ( is_numeric( $relId ) )
		{
			return $meta[ $relId ]['like.url'];
		}
		else
		{
			$return	= array();
			
			foreach( $meta as $id => $data )
			{
				$return[ $id ] = $data['like.url'];
			}
			
			return $return;
		}
	}

	/**
	 * Sends the category notifications.  This is a proxy to the built in sendNotifications() method.
	 *
	 * @param	mixed	$category		Category ID, or array of category IDs
	 * @param	string	$author_name	Author name
	 * @return	@e bool
	 * @see		sendNotifications()
	 */
	public function sendCategoryNotifications( $categoryid=0, $author_name='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$categories		= array();
		
		if ( ! is_array( $categoryid ) )
		{
			$categories[]	= $categoryid;
		}
		else
		{
			$categories		= $categoryid;
		}
		
		if ( ! count( $categories ) )
		{
			return false;
		}

		if ( ! $author_name )
		{
			$author_name	= $this->memberData['members_display_name'];
		}
		
		//-----------------------------------------
		// Loop over the categories
		//-----------------------------------------

		foreach( $categories as $categoryid )
		{
			$category 	 = $this->registry->gallery->helper('categories')->fetchCategory( $categoryid );
			
			if ( ! count( $category ) )
			{
				continue;
			}
		
			$url	= $this->registry->output->buildSEOUrl( 'app=gallery&amp;category=' . $category['category_id'], 'public', $category['category_name_seo'], 'viewcategory' );
			
			//-----------------------------------------
			// Send the notification
			//-----------------------------------------

			try
			{
				$this->sendNotifications( $category['category_id'], array( 'immediate', 'offline' ), array(
																									'notification_key'		=> 'new_image',
																									'notification_url'		=> $url,
																									'email_template'		=> 'gallery_new_cimage',
																									'email_subject'			=> $this->lang->words['subject__gallery_new_cimage'],
																									'build_message_array'	=> array(
																																	'NAME'		=> '-member:members_display_name-',
																																	'AUTHOR'	=> $author_name,
																																	'TITLE'		=> $category['category_name'],
																																	'URL'		=> $url
																																	)
																							)		);
			}
			catch( Exception $e )
			{
				/* No like class for this comment class */
			}
		}
		
		return true;
	}
}