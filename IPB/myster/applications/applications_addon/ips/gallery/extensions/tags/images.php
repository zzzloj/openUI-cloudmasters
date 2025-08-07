<?php
/**
 * @file		images.php 	IP.Gallery images tags extension
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		25 Feb 2011
 * $LastChangedDate: 2012-11-06 18:04:33 -0500 (Tue, 06 Nov 2012) $
 * @version		v5.0.5
 * $Revision: 11567 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tags_gallery_images extends classes_tag_abstract
{
	/**
	 * Cache of already loaded images
	 *
	 * @var	array
	 */
	protected $imageCache	= array();
		
	/**
	 * CONSTRUCTOR
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
	}
	
	/**
	 * Init
	 *
	 * @return	@e void
	 */
	public function init()
	{
		//-----------------------------------------
		// Make sure we have our gallery object
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded('gallery') )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}

		//-----------------------------------------
		// Call our parent
		//-----------------------------------------

		return parent::init();
	}
	
	/**
	 * Little 'trick' to force preset tags
	 *
	 * @param	string		What are we rendering
	 * @param	array 		Where data
	 * @param	@e array	Where data to show
	 */
	public function render( $what, $where )
	{
		//-----------------------------------------
		// Get our image first
		//-----------------------------------------

		$image	= $this->_getImage( $where['meta_id'] );

		//-----------------------------------------
		// Get category data
		//-----------------------------------------

		if ( ! empty( $image['image_category_id'] ) )
		{
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'] );
		}

		//-----------------------------------------
		// Turn off open system if using preset tags
		//-----------------------------------------

		if ( ! empty( $category['category_preset_tags'] ) )
		{
			$this->settings['tags_open_system'] = false;
		}
		
		return parent::render( $what, $where );
	}
	
	/**
	 * Fetches parent ID
	 *
	 * @param 	array	Where Data
	 * @return	@e int	Id of parent
	 */
	public function getParentId( $where )
	{
		//-----------------------------------------
		// Get our image and return parent ID
		//-----------------------------------------

		$image	= $this->_getImage( $where['meta_id'] );
		
		return $image['image_album_id'] ? $image['image_album_id'] : $image['image_category_id'];
	}
	
	/**
	 * Fetches permission data
	 *
	 * @param 	array		Where Data
	 * @return	@e string	Permission string
	 */
	public function getPermissionData( $where )
	{
		//-----------------------------------------
		// Get image data
		//-----------------------------------------

		$image		= $this->_getImage( $where['meta_id'] );
		
		return $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'], 'perm_view' );
	}
	
	/**
	 * Basic permission check
	 *
	 * @param	string	$what (add/remove/edit/create/prefix) [ add = add new tags to items, create = create unique tags, use a tag as a prefix for an item ]
	 * @param	array	$where data
	 * @return	@e bool
	 */
	public function can( $what, $where )
	{
		//-----------------------------------------
		// Are we globally permitted?
		//-----------------------------------------

		$return = parent::can( $what, $where );

		if ( $return === false  )
		{
			return $return;
		}

		//-----------------------------------------
		// Cache data if it was passed in
		//-----------------------------------------

		if( !empty($where['_imageData']['image_id']) )
		{
			$this->imageCache[ $where['_imageData']['image_id'] ]	= $where['_imageData'];
		}

		//-----------------------------------------
		// Get image and container data
		//-----------------------------------------

		$album		= array();
		$category	= array();
		$image		= $this->_getImage( $where['meta_id'] ? $where['meta_id'] : $where['fake_meta_id'] );
		
		if ( ! empty( $image['image_album_id'] ) )
		{
			$album		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['image_album_id'] );
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $album['album_category_id'] );
		}
		else if ( ! empty( $image['image_category_id'] ) )
		{
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'] );
		}

		//-----------------------------------------
		// Tagging disabled in this cat?
		//-----------------------------------------

		if ( ! $category['category_can_tag'] )
		{
			return false;
		}

		//-----------------------------------------
		// Check permission
		//-----------------------------------------

		switch ( $what )
		{
			case 'create':
				if ( ! $this->_isOpenSystem() )
				{
					return false;
				}
				
				return true;
			break;

			case 'add':
				if( $album['album_id'] )
				{
					if ( $this->registry->gallery->helper('albums')->isUploadable( $album['album_id'] ) || $this->registry->gallery->helper('albums')->canModerate( $album ) )
					{
						return true;
					}
				}
				else
				{
					if ( $this->registry->gallery->helper('categories')->isUploadable( $category['category_id'] ) || $this->registry->gallery->helper('categories')->checkIsModerator( $category['category_id'] ) )
					{
						return true;
					}
				}
			break;

			case 'edit':
			case 'remove':
				if ( $this->memberData['member_id'] == $image['image_member_id'] )
				{
					return true;
				}

				if( $album['album_id'] )
				{
					if ( $this->registry->gallery->helper('albums')->canModerate( $album ) )
					{
						return true;
					}
				}
				else
				{
					if ( $this->registry->gallery->helper('categories')->checkIsModerator( $category['category_id'] ) )
					{
						return true;
					}
				}
			break;

			case 'prefix':
				return false;
			break;
		}

		return false;
	}
	
	/**
	 * DEFAULT: returns true and should be defined in your own class
	 *
	 * @param 	array	Where Data
	 * @return	@e bool
	 */
	public function getIsVisible( $where )
	{
		//-----------------------------------------
		// Get image
		//-----------------------------------------

		$image	= $this->_getImage( $where['meta_id'] );
		
		//-----------------------------------------
		// Check if it's approved
		//-----------------------------------------

		if( $image['image_approved'] < 1 )
		{
			return false;
		}

		//-----------------------------------------
		// Otherwise, check permission
		//-----------------------------------------

		if ( ! empty( $image['image_album_id'] ) )
		{
			$album		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['image_album_id'] );
			
			if( !$this->registry->gallery->helper('albums')->isViewable( $album ) )
			{
				return false;
			}

			return $this->registry->gallery->helper('categories')->isViewable( $album['album_category_id'] );
		}
		else
		{
			return $this->registry->gallery->helper('categories')->isViewable( $image['image_category_id'] );
		}
	}
	
	/**
	 * Search for tags.  Overridden to validate the containers we can search
	 *
	 * @param	mixed	$tags		Array or string
	 * @param	array	$options	Array( 'meta_id' (array), 'meta_parent_id' (array), 'olderThan' (int), 'youngerThan' (int), 'limit' (int), 'sortKey' (string) 'sortOrder' (string) )
	 */
	public function search( $tags, $options )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$ok = array();

		//-----------------------------------------
		// Check parent ids - we only let them search/filter
		// tags within categories, so that is what parent ids are
		//-----------------------------------------

		if ( isset( $options['meta_parent_id'] ) )
		{
			if ( is_array( $options['meta_parent_id'] ) )
			{
				foreach( $options['meta_parent_id'] as $id )
				{
					if ( $this->registry->gallery->helper('categories')->isViewable( $id ) === true )
					{
						$ok[] = $id;
					}
				}
			}
			else
			{
				if ( $this->registry->gallery->helper('categories')->isViewable( $options['meta_parent_id'] ) === true )
				{
					$ok[] = $options['meta_parent_id'];
				}
			}
		}

		//-----------------------------------------
		// Reset and hand to parent
		//-----------------------------------------

		$options['meta_parent_id'] = $ok;
		
		return parent::search( $tags, $options );
	}
	
	/**
	 * Get text field name (overridden to recognize the temporary upload hashes)
	 *
	 * @param 	array	Where Data
	 * @return 	@e bool
	 */
	protected function _getFieldId( $where )
	{
		return 'ipsTags_' . ( ( ! empty( $where['fake_meta_id'] ) ) ? $where['fake_meta_id'] : $where['meta_id']);
	}
	
	/**
	 * Fetch a list of pre-defined tags
	 * 
	 * @param 	array		Where Data
	 * @return	@e array	Array of predefined tags or null
	 */
	protected function _getPreDefinedTags( $where=array() )
	{
		//-----------------------------------------
		// Get image
		//-----------------------------------------

		$image	= $this->_getImage( $where['meta_id'] );

		//-----------------------------------------
		// Get category data
		//-----------------------------------------

		if ( ! empty( $image['image_category_id'] ) )
		{
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'] );
		}

		//-----------------------------------------
		// Reset predefined tags and return as appropriate
		//-----------------------------------------

		$this->settings['tags_predefined']	= ( ! empty( $category['category_preset_tags'] ) ) ? $category['category_preset_tags'] : $this->settings['tags_predefined'];
		
		return parent::_getPreDefinedTags( $where );
	}
	
	/**
	 * Can set an item as a topic prefix
	 * 
	 * @param 	array		$where		Where Data
	 * @return 	@e bool
	 */
	protected function _prefixesEnabled( $where )
	{
		return false;
	}
	
	/**
	 * Fetch an image
	 * 
	 * @param	integer		$imageId	Image ID
	 * @return	@e array	Image data
	 */
	protected function _getImage( $imageId )
	{
		//-----------------------------------------
		// Use inline caching
		//-----------------------------------------

		if ( ! isset( $this->imageCache[ $imageId ] ) )
		{
			$this->imageCache[ $imageId ]	= $this->registry->gallery->helper('image')->fetchImage( $imageId, true, false );
		}

		//-----------------------------------------
		// Return image data
		//-----------------------------------------

		return $this->imageCache[ $imageId ];
	}
}