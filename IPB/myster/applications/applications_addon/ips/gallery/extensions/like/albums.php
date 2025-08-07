<?php
/**
 * @file		albums.php 	Albums like class (gallery application)
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
 * @class		like_gallery_albums_composite
 * @brief		Albums like class (gallery application)
 */
class like_gallery_albums_composite extends classes_like_composite
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
		return 'follow_album';
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
		// Fetch
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'gallery_albums', 'where' => 'album_id IN (' . implode( ',', $relId ) . ')' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$category	= $this->registry->getClass('categories')->fetchCategory( $row['album_category_id'] );

			//-----------------------------------------
			// Find the title
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'title', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.title']	= $row['album_name'];
			} 
			
			//-----------------------------------------
			// Find the URL
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'url', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.url']		= $this->registry->output->buildSEOUrl( "app=gallery&amp;album=" . $row['album_id'], "public", $row['album_name_seo'], "viewalbum" );
			}
			
			//-----------------------------------------
			// Find the type
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'type', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.type']	= $this->lang->words['viewimg_album'];
			} 
			
			//-----------------------------------------
			// Find the parent's title
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.parentTitle']	= $category['category_name'];
			} 
			
			//-----------------------------------------
			// Find the parent's URL
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.parentUrl']	= $this->registry->output->buildSEOUrl( "app=gallery&amp;category=" . $category['category_id'], "public", $category['category_name_seo'], "viewcategory" );
			} 
			
			//-----------------------------------------
			// Find the parent's type
			//-----------------------------------------

			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentType', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.type'] = $this->lang->words['viewing_category'];
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
	 * Sends the album notifications.  This is a proxy to the built in sendNotifications() method.
	 *
	 * @param	mixed	$albumid		Album ID, or array of album IDs
	 * @param	string	$author_name	Author name
	 * @return	@e bool
	 * @see		sendNotifications()
	 */
	public function sendAlbumNotifications( $albumid=0, $author_name='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albums			= array();
		
		if ( ! is_array( $albumid ) )
		{
			$albums[]	= $albumid;
		}
		else
		{
			$albums		= $albumid;
		}
		
		if ( ! count( $albums ) )
		{
			return false;
		}

		if ( ! $author_name )
		{
			$author_name	= $this->memberData['members_display_name'];
		}
		
		//-----------------------------------------
		// Loop over the albums
		//-----------------------------------------

		foreach( $albums as $albumid )
		{
			$album	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumid );
			
			if ( ! count( $album ) )
			{
				continue;
			}
		
			$url	= $this->registry->output->buildSEOUrl( 'app=gallery&amp;album=' . $album['album_id'], 'public', $album['album_name_seo'], 'viewalbum' );
			
			//-----------------------------------------
			// Send the notification
			//-----------------------------------------

			try
			{
				$this->sendNotifications( $album['album_id'], array( 'immediate', 'offline' ), array(
																									'notification_key'		=> 'new_image',
																									'notification_url'		=> $url,
																									'email_template'		=> 'gallery_new_aimage',
																									'email_subject'			=> $this->lang->words['subject__gallery_new_aimage'],
																									'build_message_array'	=> array(
																																	'NAME'		=> '-member:members_display_name-',
																																	'AUTHOR'	=> $author_name,
																																	'TITLE'		=> $album['album_name'],
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