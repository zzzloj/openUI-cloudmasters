<?php
/**
 * @file		coreExtensions.php 	IP.Gallery core extensions library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		1st march 2002
 * $LastChangedDate: 2012-07-11 17:58:49 -0400 (Wed, 11 Jul 2012) $
 * @version		v5.0.5
 * $Revision: 11065 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_PERM_CONFIG = array( 'Categories' );

/**
 *
 * @class		galleryPermMappingCategories
 * @brief		Gallery permission plugin for categories
 */
class galleryPermMappingCategories
{
	/**
	 * Mapping of keys to columns
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $mapping = array(
								'images'	=> 'perm_view',
								'post'		=> 'perm_2',
								'comments'	=> 'perm_3',
								'rate'		=> 'perm_4',
								'bypass'	=> 'perm_5',
							);

	/**
	 * Mapping of keys to names
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $perm_names = array(
								'images'	=> 'View Albums / Images',
								'post'		=> 'Upload Images / Create Albums',
								'comments'	=> 'Add Comments',
								'rate'		=> 'Rate Albums / Images',
								'bypass'	=> 'Bypass Moderation',
							);

	/**
	 * Mapping of keys to background colors for the form
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $perm_colors = array(
								'images'	=> '#fff0f2',
								'post'		=> '#effff6',
								'comments'	=> '#edfaff',
								'rate'		=> '#f0f1ff',
								'bypass'	=> '#fffaee',
							);

	/**
	 * Method to pull the key/column mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermNames()
	{
		return $this->perm_names;
	}

	/**
	 * Method to pull the key/color mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermColors()
	{
		return $this->perm_colors;
	}

	/**
	 * Retrieve the items that support permission mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermItems()
	{
		//-----------------------------------------
		// Get our librar(y|ies)
		//-----------------------------------------

		ipsRegistry::getAppClass( 'gallery' );

		$_return_arr	= array();

		foreach( ipsRegistry::getClass('gallery')->helper('categories')->catJumpList() as $r )
		{
			$_category				= ipsRegistry::getClass('gallery')->helper('categories')->fetchCategory( $r[0] );
			$return_arr[ $r[0] ]	= array(
											'title'		=> $r[1],
											'perm_view'	=> $_category['perm_view'],
											'perm_2'	=> $_category['perm_2'],
											'perm_3'	=> $_category['perm_3'],
											'perm_4'	=> $_category['perm_4'],
											'perm_5'	=> $_category['perm_5'],									
											);
		}
		
		return $return_arr;
	}	
}

/**
 *
 * @class		itemMarking__gallery
 * @brief		Gallery item marking plugin
 */
class itemMarking__gallery
{
	/**
	 * Field Convert Data Remap Array
	 * This is where you can map your app_key_# numbers to application savvy fields
	 * 
	 * @var		array
	 */
	private $_convertData = array( 'categoryID' => 'item_app_key_1',
								   'albumID'    => 'item_app_key_2'
								  );
	
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
	 * I'm a constructor, twisted constructor
	 *
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry   =  $registry;
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang	      =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Convert Data
	 * Takes an array of app specific data and remaps it to the DB table fields
	 *
	 * @param	array
	 * @return	@e array
	 */
	public function convertData( $data )
	{
		$_data = array();
		
		foreach( $data as $k => $v )
		{
			if ( isset($this->_convertData[$k]) )
			{
				$_data[ $this->_convertData[ $k ] ] = intval( $v );
			}
			else
			{
				$_data[ $k ] = $v;
			}
		}
		
		return $_data;
	}
	
	/**
	 * Fetch unread count
	 *
	 * Grab the number of items truly unread
	 * This is called upon by 'markRead' when the number of items
	 * left hits zero (or less).
	 * 
	 *
	 * @param	array 	Array of data
	 * @param	array 	Array of read itemIDs
	 * @param	int 	Last global reset
	 * @return	@e integer	Last unread count
	 */
	public function fetchUnreadCount( $data, $readItems, $lastReset )
	{	
		/* Setup */
		$count		= 0;
		$lastItem	= 0;
		$readItems	= is_array( $readItems ) ? $readItems : array( 0 );
		
		/* Album or category? */
		if ( $data['albumID'] )
		{
			$approved	= ( $this->registry->gallery->helper('albums')->canModerate( $data['albumID'] ) ) ? '' : ' AND image_approved=1';
			$_count		= $this->DB->buildAndFetch( array( 
														'select' => 'COUNT(*) as cnt, MIN(image_date) as lastItem',
														'from'   => 'gallery_images',
														'where'  => "image_album_id=" . intval( $data['albumID'] ) . " AND image_id NOT IN(" . implode( ",", array_keys( $readItems ) ) . ") AND image_date > " . intval( $lastReset )
												)		);
		}
		else
		{
			$approved	= ( $this->registry->gallery->helper('categories')->checkIsModerator( $data['categoryID'] )  ) ? '' : ' AND image_approved=1';
			$_count		= $this->DB->buildAndFetch( array( 
														'select' => 'COUNT(*) as cnt, MIN(image_date) as lastItem',
														'from'   => 'gallery_images',
														'where'  => "image_category_id=" . intval( $data['categoryID'] ) . " AND image_album_id=0 AND image_id NOT IN(" . implode( ",", array_keys( $readItems ) ) . ") AND image_date > " . intval( $lastReset )
												)		);
		}

		return array( 'count' => intval( $_count['cnt'] ), 'lastItem' => intval( $_count['lastItem'] ) );
	}
}


/**
 *
 * @class		publicSessions__gallery
 * @brief		Handles public session data for the online list
 */
class publicSessions__gallery
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
	}
						 	
	/**
	 * Return session variables for this application.
	 *
	 * current_appcomponent, current_module and current_section are automatically
	 * stored. This function allows you to add specific variables in.
	 *
	 * @return	@e array	Parsed sessions data
	 */
	public function getSessionVariables()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$return_array	= array();

		//-----------------------------------------
		// Don't update session when image is shown dynamically
		//-----------------------------------------

		if( $this->request['section'] == 'img_ctrl' )
		{
			define( 'NO_SESSION_UPDATE', true );
		}

		//-----------------------------------------
		// Viewing an image?
		//-----------------------------------------

		else if( $this->request['module'] == 'images' && $this->request['section'] == 'viewimage' )
		{
			$return_array['location_1_type']	= 'image';
			$return_array['location_1_id']		= intval($this->request['image']);
			$return_array['location_2_type']	= 'album';
			$return_array['location_2_id']		= intval($this->request['album']);
			$return_array['location_3_type']	= 'category';
			$return_array['location_3_id']		= intval($this->request['category']);
		}

		//-----------------------------------------
		// Viewing a category?
		//-----------------------------------------

		else if( $this->request['module'] == 'albums' && $this->request['section'] == 'category' )
		{
			$return_array['location_3_type']	= 'category';
			$return_array['location_3_id']		= intval($this->request['category']);
		}

		//-----------------------------------------
		// Viewing an album?
		//-----------------------------------------

		else if( $this->request['module'] == 'albums' )
		{
			$return_array['location_2_type']	= 'album';
			$return_array['location_2_id']		= intval($this->request['album']);
		}

		return $return_array;
	}

	/**
	 * Parse/format the online list data for the records
	 *
	 * @param	array	$rows		Online list rows to check against
	 * @return	@e array	Online list rows parsed
	 */
	public function parseOnlineEntries( $rows )
	{
		//-----------------------------------------
		// Load language strings
		//-----------------------------------------

		$this->lang	= $this->registry->getClass('class_localization');
		$this->lang->loadLanguageFile( array( 'public_location' ), 'gallery' );
		
		//-----------------------------------------
		// Cache data
		//-----------------------------------------

		$sessionAlbums	= array();
		$sessionImages	= array();
		
		foreach( $rows as $session_id => $row )
		{
			//-----------------------------------------
			// If this row isn't in Gallery, skip it
			//-----------------------------------------

			if( $row['current_appcomponent'] != 'gallery' )
			{
				continue;
			}
			
			//-----------------------------------------
			// If this is an image, store image ids
			//-----------------------------------------

			if ( $row['location_1_type'] == 'image' && intval($row['location_1_id']) )
			{
				$_img	= intval($row['location_1_id']);
				
				$sessionImages[ $_img ]	= $_img;
			}

			//-----------------------------------------
			// If this is an album, store album ids
			//-----------------------------------------

			elseif ( $row['location_2_type'] == 'album' && intval($row['location_2_id']) )
			{
				$_alb	= intval($row['location_2_id']);
				
				$sessionAlbums[ $_alb ] = $_alb;
			}
		}

		//-----------------------------------------
		// Make sure we have our Gallery library
		//-----------------------------------------

		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		//-----------------------------------------
		// Get images and albums from DB
		// No need to store and cache categories as they're already cached
		//-----------------------------------------

		$albums	= array();
		$images	= array();
		
		if ( count($sessionAlbums) OR count($sessionImages) )
		{
			if ( count( $sessionAlbums ) )
			{
				$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $sessionAlbums );
			}
			
			if ( count( $sessionImages ) )
			{
				$images	= $this->registry->gallery->helper('image')->fetchImages( ips_MemberRegistry::instance()->getProperty('member_id'), array( 'imageIds' => $sessionImages, 'orderBy' => false ) );
			}			
		}
		
		//-----------------------------------------
		// Loop through the rows and merge our data in
		//-----------------------------------------

		foreach( $rows as $session_id => $row )
		{
			if( $row['current_appcomponent'] == 'gallery' )
			{
				//-----------------------------------------
				// Is this an image?
				//-----------------------------------------

				if( $row['location_1_type'] == 'image' && isset($images[ $row['location_1_id'] ]) && $this->registry->getClass('gallery')->helper('image')->validateAccess( $images[ $row['location_1_id'] ], true ) )
				{
					$_img	= $images[ $row['location_1_id'] ];
					
					$row['where_line']		= $this->lang->words['gallery_loci_si'];
					$row['where_line_more']	= $_img['image_caption'];
					$row['where_link']		= 'app=gallery&amp;image=' . $_img['image_id'];
					$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( $row['where_link'], 'public' ), $_img['image_caption_seo'], 'viewimage' );
				}

				//-----------------------------------------
				// Is this an album?
				//-----------------------------------------

				elseif ( $row['location_2_type'] == 'album' && isset($albums[ $row['location_2_id'] ]) && $this->registry->getClass('gallery')->helper('albums')->isViewable( $albums[ $row['location_2_id'] ], true ) )
				{
					$_album	= $albums[ $row['location_2_id'] ];
					
					$row['where_line']		= $this->lang->words['gallery_loci_album'];
					$row['where_line_more']	= $_album['album_name'];
					$row['where_link']		= 'app=gallery&amp;album=' . $_album['album_id'];
					$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( $row['where_link'], 'public' ), $_album['album_name_seo'], 'viewalbum' );
				}

				//-----------------------------------------
				// Is this a category?
				//-----------------------------------------

				elseif ( $row['location_3_type'] == 'category' && $row['location_3_id'] && $this->registry->getClass('gallery')->helper('categories')->isViewable( $row['location_3_id'] ) )
				{
					$_category	= $this->registry->gallery->helper('categories')->fetchCategory( $row['location_3_id'] );
					
					$row['where_line']		= $this->lang->words['gallery_loci_cat'];
					$row['where_line_more']	= $_category['category_name'];
					$row['where_link']		= 'app=gallery&amp;category=' . $_category['category_id'];
					$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( $row['where_link'], 'public' ), $_category['category_name_seo'], 'viewcategory' );
				}

				//-----------------------------------------
				// Gallery index
				//-----------------------------------------

				else
				{
					$row['where_line']		= $this->lang->words['gallery_loci_idx'];
					$row['where_link']		= 'app=gallery';
					$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( $row['where_link'], 'public' ), 'false', 'app=gallery' );
				}
			}
						
			$rows[ $session_id ] = $row;
		}

		return $rows;
	}
}

/**
 *
 * @class		gallery_findIpAddress
 * @brief		IP address lookup plugin class
 */
class gallery_findIpAddress
{
	/**
	 * Return ip address lookup tables
	 *
	 * @return	@e array 	Table lookups
	 */
	public function getTables()
	{
		return array( 'gallery_comments'	=> array( 'comment_author_id', 'comment_ip_address', 'comment_post_date' ) );
	}
}