<?php
/**
 * @file		image.php 	IP.Gallery image library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2013-05-23 17:21:35 -0400 (Thu, 23 May 2013) $
 * @version		v5.0.5
 * $Revision: 12269 $
 */

class gallery_image
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
	protected $cache;
	/**#@-*/

	/**
	 * Next/previous cache
	 * We use virtually the same query to generate the photostrip as to generate the next/prev links so
	 * we cache the result to save another query.
	 * 
	 * @var		array
	 */
	protected $_navCache	= array( 'prev' => null, 'now' => null, 'next' => null );
	
	/**
	 * Image count
	 *
	 * @var		int
	 */
	protected $_imageCount	= 0;
	
	/**
	 * Image file extensions
	 * 
	 * @var		array
	 */
	protected $_ext			= array( 'gif', 'png', 'jpg', 'jpeg', 'tiff' );
	
	/**
	 * Constructor
	 *
	 * @param	ipsRegistry	$registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Set registry objects
		//-----------------------------------------

		$this->registry   = $registry;
		$this->DB         = $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       = $this->registry->getClass('class_localization');
		$this->member     = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      = $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		define( 'GALLERY_IMAGES_FORCE_LOAD', true );
		
		//-----------------------------------------
		// Load some other objects we need
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}
		
		if ( ! ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		if ( ! ipsRegistry::isClassLoaded('classItemMarking') && ( ! defined('IPS_IS_UPGRADER') OR ! IPS_IS_UPGRADER ) )
		{
			$this->registry->getClass('classItemMarking');
		}
	}
	
	/**
	 * Return an array of allowed extensions
	 * 
	 * @return	@e array
	 */
	public function allowedExtensions()
	{
		return $this->_ext;
	}
	
	/**
	 * Returns the image count from a query which asks for it.
	 * Which is anything that passes via _fetchImages
	 *
	 * @return	@e int
	 */
	public function getCount()
	{
		return $this->_imageCount;
	}
	
	/**
	 * Return the correct mime type for an image
	 *
	 * @since	2.0.4
	 * @param	string	$img
	 * @return	@e string	
	 */
	public function getImageType( $img )
	{
		//-----------------------------------------
		// Get extension
		//-----------------------------------------

		$exploded_array	= explode( ".", $img );
		$ext			= strtolower( array_pop( $exploded_array ) );

		//-----------------------------------------
		// Determine mime-type based on extension
		//-----------------------------------------

		switch( $ext )
		{
			case 'gif':
				$file_type = 'image/gif';
			break;
			
			case 'jpg':
			case 'jpeg':
			case 'jpe':
				$file_type = 'image/jpeg';
			break;
			
			case 'png':
				$file_type = 'image/png';
			break;
		}
		
		return $file_type;
	}

	/**
	 * Return part of an SQL 'where' statement. Abstracted in case we change it later
	 *
	 * @param	mixed		Array or string of (private, public, friend)
	 * @return	@e string
	 */
	public function sqlWherePrivacy( $mask )
	{
		//-----------------------------------------
		// Reformat param and INIT
		//-----------------------------------------

		$privacy	= array();
		
		if ( ! is_array( $mask ) )
		{
			$mask	= array( $mask );
		}
		
		//-----------------------------------------
		// Loop through requested masks
		//-----------------------------------------

		foreach( $mask as $m )
		{
			switch ( strtolower( $m ) )
			{
				case 'private':
				case 'none':
					$privacy[] = 2;
				break;
				case 'public':
					$privacy[] = 1;
				break;
				case 'friend':
				case 'friends':
					$privacy[] = 3;
				break;
				case 'cat':
				case 'category':
				case 'global_album':
				case 'galbum':
					$privacy[] = 0;
				break;
			}
		}

		//-----------------------------------------
		// Return statement for SQL 'where' clause
		//-----------------------------------------

		if ( count( $privacy ) == 1 )
		{
			return 'image_privacy=' . array_shift( $privacy ) . '';
		}
		else
		{
			return 'image_privacy IN (' . implode( ",", $privacy ) . ')';
		}
	}

	/**
	 * Save imarges. Give it an array, and it'll update the fields in the array.
	 *
	 * @param	array	array( id => array( ... ) )
	 * @return	@e boolean
	 */
	public function save( array $array )
	{
		$albums		= array();
		$categories	= array();
		$uploads	= array();

		//-----------------------------------------
		// Loop over the array
		//-----------------------------------------

		foreach( $array as $id => $data )
		{
			//-----------------------------------------
			// Is this a temporary upload?
			//-----------------------------------------

			if ( ! is_numeric( $id ) AND strlen( $id ) == 32 )
			{
				$uploads[ $id ]	= $data;
			}
			else
			{
				//-----------------------------------------
				// Flag album and/or category for resync
				//-----------------------------------------

				if ( $data['image_album_id'] AND ! isset( $albums[ $data['image_album_id'] ] ) )
				{
					$albums[ $data['image_album_id'] ] = array();
				}

				if ( $data['image_category_id'] AND ! isset( $categories[ $data['image_category_id'] ] ) )
				{
					$categories[ $data['image_category_id'] ]	= $data['image_category_id'];
				}
				
				//-----------------------------------------
				// Remove special items
				//-----------------------------------------

				if ( isset( $data['_isCover'] ) )
				{
					if ( $data['_isCover'] )
					{
						if( $data['image_album_id'] )
						{
							$albums[ $data['image_album_id'] ] = array( 'album_cover_img_id' => $id );
						}
						else if( $data['image_category_id'] )
						{
							$this->DB->update( 'gallery_categories', array( 'category_cover_img_id' => $id ), 'category_id=' . $data['image_category_id'] );
						}
					}
					
					unset( $data['_isCover'] );
				}

				unset( $data['_follow'] );

				//-----------------------------------------
				// Update database
				//-----------------------------------------

				$this->DB->update( 'gallery_images', $data, 'image_id=' . intval( $id ) );

				//-----------------------------------------
				// Save tags
				//-----------------------------------------

				if ( ! empty( $_POST['ipsTags_' . $id ] ) )
				{
					$this->registry->galleryTags->replace( $_POST['ipsTags_' . $id], array( 'meta_id'		 => $id,
																	      					'meta_parent_id' => $data['image_album_id'] ? $data['image_album_id'] : $data['image_category_id'],
																	      					'member_id'	     => $data['image_member_id'],
																	      					'meta_visible'   => ( $data['image_approved'] == 1 ) ? 1 : 0 ) );
				}
				
				//-----------------------------------------
				// Rebuild the images
				//-----------------------------------------

				if ( isset( $data['image_masked_file_name'] ) AND isset( $data['image_directory'] ) )
				{
					$this->buildSizedCopies( $data );
				}
			}
		}
		
		//-----------------------------------------
		// Got any temporary images?
		//-----------------------------------------

		if ( count( $uploads ) )
		{
			$this->registry->gallery->helper('upload')->saveSessionImages( $uploads );
		}
		
		//-----------------------------------------
		// Update albums
		//-----------------------------------------

		if ( count( $albums ) )
		{
			$this->registry->gallery->helper('albums')->save( $albums );
		}

		//-----------------------------------------
		// Resynch albums and categories as needed
		//-----------------------------------------

		foreach( $albums as $id => $albumData )
		{
			//-----------------------------------------
			// Album wouldn't have resynched from save() call
			// if there was nothing in the array to save
			//-----------------------------------------

			if( !count($albumData) )
			{
				$this->registry->gallery->helper('albums')->resync( $id );
			}
		}

		foreach( $categories as $category )
		{
			$this->registry->gallery->helper('categories')->rebuildCategory( $category );
		}
	}
	
	/**
	 * Updates images in this album with the appropriate permission string
	 *
	 * @param	mixed	$album
	 * @return	@e void
	 */
	public function updatePermissionFromParent( $album )
	{
		if( !$album )
		{
			return;
		}

		//-----------------------------------------
		// Make sure we have the album data
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			if ( $album > 0 )
			{
				$album	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $album, true );
			}
		}

		//-----------------------------------------
		// Reformat array if needed
		//-----------------------------------------

		if ( is_array( $album ) AND isset( $album['album_id'] ) )
		{
			$album	= array( $album['album_id'] => $album );
		}

		//-----------------------------------------
		// Reset permissions now as needed
		//-----------------------------------------

		if( count($album) )
		{
			foreach( $album as $id => $_album )
			{
				$_update	= array(
									'image_privacy'				=> $_album['album_type'],
									'image_parent_permission'	=> $this->registry->gallery->helper('categories')->fetchCategory( $_album['album_category_id'], 'perm_view' ),
									);

				$this->DB->update( 'gallery_images', $_update, 'image_album_id=' . $_album['album_id'] );
			}
		}
	}
	
	/**
	 * Deletes images.  This method just redirects to the moderate library, but is left 
	 * here for consistency and as a fallback/shortcut.
	 *
	 * @param	array		Images to delete
	 * @return	@e bool
	 * @see		gallery_moderate::deleteImages
	 */
	public function delete( $images )
	{
		return $this->registry->gallery->helper('moderate')->deleteImages( $images );
	}
	
	/**
	 * Generic method for getting an image record.  Does NOT perform permission checks!
	 * 
	 * @since	2.0
	 * @param	mixed	$id					Image ID or array of Image IDs
	 * @param	bool	$force				Force loading the image fresh if it is cached
	 * @param	bool	$parseDescription	If we're loading lots of images in the same view and not displaying descriptions, pass false to save some resources
	 * @return	@e array
	 */
	public function fetchImage( $id, $force=false, $parseDescription=true )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$_imageIds	= array();
		$_img		= array();

		if ( is_numeric($id) && $id > 0 )
		{
			$_imageIds[]	= intval($id);
		}
		else if( is_array($id) AND count($id) )
		{
			$_imageIds		= $id;
		}

		//-----------------------------------------
		// If ID is not numeric, fallback to upload session
		//-----------------------------------------

		else if( $id )
		{
			$_img	= $this->registry->gallery->helper('upload')->fetchImage( trim( $id ) );

			if( count($_img) )
			{
				//-----------------------------------------
				// Pull in category data
				//-----------------------------------------

				if( !$_img['image_album_id'] )
				{
					$_img	= array_merge( $_img, $this->registry->gallery->helper('categories')->fetchCategory( $_img['image_category_id'] ) );
				}

				$_img	= $this->_setUpImage( $_img, $parseDescription );
			}
		}
		else
		{
			return array();
		}

		//-----------------------------------------
		// Check cache, if $force is not enabled
		//-----------------------------------------

		static $imagesCache = array();
		
		if ( isset($imagesCache[ md5( serialize( $id ) ) ]) && $force === false )
		{
			return $imagesCache[ md5( serialize( $id ) ) ];
		}
		
		//-----------------------------------------
		// Get the image data if it's a stored image
		//-----------------------------------------

		if( !count($_img) )
		{
			$this->DB->build( array(
									'select'	=> 'i.*',
									'from'		=> array( 'gallery_images' => 'i' ),
									'where'		=> 'i.image_id IN(' . implode( ',', $_imageIds ) . ")",
									'add_join'	=> array( array(
																'select'	=> 'a.*',
																'from'		=> array( 'gallery_albums' => 'a' ),
																'where'		=> 'a.album_id=i.image_album_id',
																'type'		=> 'left'
																),
															array(
																'select'	=> 'mem.members_display_name',
																'from'		=> array( 'members' => 'mem' ),
																'where'		=> 'mem.member_id=i.image_member_id',
																'type'		=> 'left'
																)
															)
							 )		);
			$this->DB->execute();

			while( $r = $this->DB->fetch() )
			{
				//-----------------------------------------
				// Pull in category data
				//-----------------------------------------

				if( !$r['image_album_id'] )
				{
					$r	= array_merge( $r, $this->registry->gallery->helper('categories')->fetchCategory( $r['image_category_id'] ) );
				}

				$_img[ $r['image_id'] ]	= $this->_setUpImage( $r, $parseDescription );
			}

			if( count( $_img ) == 1 )
			{
				$_img	= array_pop($_img);
			}
		}

		//-----------------------------------------
		// Prevent errors and wasted resources if image does not exist
		//-----------------------------------------

		if( !is_array($_img) OR !count($_img) )
		{
			$imagesCache[ md5( serialize( $id ) ) ]	= array();

			return array();
		}

		//-----------------------------------------
		// Cache and return
		//-----------------------------------------

		$imagesCache[ md5( serialize( $id ) ) ]	= $_img;

		return $imagesCache[ md5( serialize( $id ) ) ];
	}

	/**
	 * Set the approved/not approved flags in the query
	 *
	 * @param	array 	Filters
	 * @param	string	Query prefix
	 * @return	@e string
	 */
	public function sqlWhereApproved( $filters, $prefix='i.' )
	{
		//-----------------------------------------
		// Not bypassing permission checks?
		//-----------------------------------------

		if ( empty($filters['bypassModerationChecks']) )
		{
			//-----------------------------------------
			// Are we a category moderator anywhere?
			//-----------------------------------------

			$_modCats	= array();

			foreach( $this->registry->gallery->helper('categories')->fetchCategories() as $_cat )
			{
				if( $this->registry->gallery->helper('categories')->checkIsModerator( $_cat['category_id'], null, 'mod_can_approve' ) )
				{
					$_modCats[]	= $_cat['category_id'];
				}
			}

			//-----------------------------------------
			// Return image_approved clause
			//-----------------------------------------

			if( count($_modCats) )
			{
				return "( {$prefix}image_approved=1 OR ( {$prefix}image_approved IN(-1,0,1) AND {$prefix}image_category_id IN( " . implode( ',', $_modCats ) . " ) ) )";
			}
			else
			{
				return "{$prefix}image_approved=1";
			}
		}
		else
		{
			//-----------------------------------------
			// Bypassing perm checks
			//-----------------------------------------

			return "{$prefix}image_approved IN(-1,0,1)";
		}
	}

	/**
	 * Fetch images: Generic method that accepts filters to determine what to pull.
	 *
	 * Filters:
	 * sortKey				Sort results key
	 * sortOrder			Sort results asc/desc
	 * limit				Limit to X results
	 * offset				Fetch from X rows
	 * imageIds				Array of image ids to fetch
	 * albumId				One or more album Ids to fetch from.  If passed, but 0, we check image_album_id=0 (e.g. it is a category image and not in an album)
	 * categoryId			One or more category Ids to fetch from
	 * getTotalCount		Fetch total count of WHERE before limit is added
	 * featured				WHERE on the featured field
	 * getLatestComment		Fetch latest comment with data
	 * hasComments			Only return images with comments
	 * retrieveByComments	Fetch comments and join image data, rather than vice-versa.  Allows more than one comment on the same image to be returned.
	 *
	 * @param	mixed		Member ID (the person viewing) or null
	 * @param	array		Sort options (sortKey, sortOrder, offset, limit, unixCutOff)
	 * @param	bool		Bypass permission check
	 * @return	@e array	Array of images and other information
	 */
	public function fetchImages( $memberId, $filters=array(), $byPassPerms=false )
	{
		//-----------------------------------------
		// Set and check filters
		//-----------------------------------------

		$memberId	= ( $memberId !== null ) ? intval( $memberId ) : null;
		$filters	= $this->_setFilters( $filters );
		$where		= array();
		$_or		= array();
		$_masks		= array( 'public', 'category' );
		
		//-----------------------------------------
		// Pulling specific images?
		//-----------------------------------------

		if ( ! empty( $filters['imageIds'] ) && is_array( $filters['imageIds'] ) )
		{
			$where[] = "i.image_id IN (" . implode( ",", $filters['imageIds'] ) . ')';
		}

		//-----------------------------------------
		// Querying based on a member's permissions?
		//-----------------------------------------

		if ( $memberId !== null )
		{
			//-----------------------------------------
			// Make sure we have a member ID, otherwise it's a guest
			//-----------------------------------------

			if ( $memberId )
			{
				//-----------------------------------------
				// If it's current user, just grab memberData
				//-----------------------------------------

				if ( $memberId == $this->memberData['member_id'] )
				{
					$_member	= $this->memberData;
					$_permId	= $this->member->perm_id_array;
				}
				else
				{
					//-----------------------------------------
					// Load member data and unpack cache
					//-----------------------------------------

					$_member				= IPSMember::load( $memberId, 'groups' );
					$_member['_cache']		= IPSMember::unpackMemberCache( $_member['member_cache'] );
					$_member['org_perm_id']	= IPSText::cleanPermString( $_member['org_perm_id'] );
					$_permId				= explode( ",", !empty($_member['org_perm_id']) ? $_member['org_perm_id'] : $_member['g_perm_id'] );
				}

				//-----------------------------------------
				// Start building OR clauses
				//-----------------------------------------

				$_categories	= $this->registry->gallery->helper('categories')->fetchCategories();

				foreach( $_categories as $_cat )
				{
					if( $this->registry->gallery->helper('categories')->checkIsModerator( $_cat['category_id'], $_member ) )
					{
						$_or[]	= "( i." . $this->sqlWherePrivacy( array( 'friend', 'public', 'private', 'category' ) ) . ' AND ( ' .  $this->DB->buildWherePermission( $_permId, 'i.image_parent_permission', true ) . ')  AND i.image_category_id=' . $_cat['category_id'] . ' )';
					}
				}

				$_or[]	= "( i." . $this->sqlWherePrivacy( array( 'friend', 'public', 'private', 'category' ) ) . ' AND i.image_member_id=' . $memberId . ' )';
				$_or[]	= "( i." . $this->sqlWherePrivacy( array( 'public', 'category' ) ) . ' AND ( ' .  $this->DB->buildWherePermission( $_permId, 'i.image_parent_permission', true ) . ') )';

				if ( is_array( $_member['_cache']['friends'] ) AND count( $_member['_cache']['friends'] ) )
				{
					$_or[]	= "( i." . $this->sqlWherePrivacy( array( 'public', 'friend', 'category' ) ) . ' AND ( ' .  $this->DB->buildWherePermission( $_permId, 'i.image_parent_permission', true ) . ')' . ' AND i.image_member_id IN(' . implode( ",", array_slice( array_keys( $_member['_cache']['friends'] ), 0, 300 ) ) . ') )';
				}
			}
			else
			{
				$where[]	= "i." . $this->sqlWherePrivacy( array( 'public', 'category' ) ) . ' AND ( ' .  $this->DB->buildWherePermission( $this->member->perm_id_array, 'i.image_parent_permission', true ) . ')';
			}
		}
		
		//-----------------------------------------
		// Combine or clauses if any are set
		//-----------------------------------------

		if ( count( $_or ) )
		{
			$where[]	= '( ' . implode( " OR ", $_or ) . ' )';
		}

		//-----------------------------------------
		// Check if image needs to be approved
		//-----------------------------------------

		$where[]	= $this->sqlWhereApproved( $filters );
		
		//-----------------------------------------
		// Pass request to central processing function
		//-----------------------------------------

		return $this->_fetchImages( $where, $filters );
	}
	
	/**
	 * Fetch images from a specific album
	 *
	 * @param	int			Album ID
	 * @param	array		Sort options (sortKey, sortOrder, offset, limit, unixCutOff)
	 * @return	@e array	Array of images and album information
	 */
	public function fetchAlbumImages( $albumId, $filters=array() )
	{	
		//-----------------------------------------
		// Set some filters
		//-----------------------------------------

		$filters	= $this->_setFilters( $filters );
		$where		= array();
				
		//-----------------------------------------
		// Set the album ID filter
		//-----------------------------------------

		$filters['albumId']			= $albumId;
		$filters['thumbClass']		= 'galattach';
		$filters['link-thumbClass']	= 'galimageview';
		
		//-----------------------------------------
		// Check if image needs to be approved
		//-----------------------------------------

		$where[]	= $this->sqlWhereApproved( $filters );
				
		//-----------------------------------------
		// Pass request to central processing function
		//-----------------------------------------

		return $this->_fetchImages( $where, $filters );
	}

	/**
	 * Fetch images from a specific category
	 *
	 * @param	int			Category ID
	 * @param	array		Sort options (sortKey, sortOrder, offset, limit, unixCutOff)
	 * @return	@e array	Array of images and album information
	 */
	public function fetchCategoryImages( $categoryId, $filters=array() )
	{	
		//-----------------------------------------
		// Set some filters
		//-----------------------------------------

		$filters	= $this->_setFilters( $filters );
		$where		= array();
				
		//-----------------------------------------
		// Set the album ID filter
		//-----------------------------------------

		$filters['albumId']		= 0;
		$filters['categoryId']	= $categoryId;
		
		//-----------------------------------------
		// Check if image needs to be approved
		//-----------------------------------------

		$where[]	= $this->sqlWhereApproved( $filters );
				
		//-----------------------------------------
		// Pass request to central processing function
		//-----------------------------------------

		return $this->_fetchImages( $where, $filters );
	}
	
	/**
	 * Fetch images for a specific member
	 *
	 * @param	mixed		Member ID of owner or array of owners
	 * @param	array		Sort options (sortKey, sortOrder, offset, limit, unixCutOff)
	 * @return	@e array	Array of images and album information
	 */
	public function fetchMembersImages( $member, $filters=array() )
	{
		//-----------------------------------------
		// Set filters
		//-----------------------------------------

		$filters	= $this->_setFilters( $filters );
		$where		= array();
		$_or		= array();
		
		//-----------------------------------------
		// Check member data
		//-----------------------------------------

		if ( is_numeric( $member ) )
		{
			$member = array( $member );
		}
		
		if ( ! count( $member ) )
		{
			return array();
		}
		
		//-----------------------------------------
		// Loop over the members
		//-----------------------------------------

		foreach( $member as $memberId )
		{
			$memberId	= intval( $memberId );
			$_masks		= array( 'public', 'category' );
			
			//-----------------------------------------
			// Restrict images?
			//-----------------------------------------

			if ( $memberId == $this->memberData['member_id'] )
			{
				$_masks[]	= 'friend';
				$_masks[]	= 'private';
			}
			elseif ( IPSMember::checkFriendStatus( $memberId, 0, true ) )
			{
				$_masks[]	= 'friend';
			}

			$_permId		= $this->member->perm_id_array;

			//-----------------------------------------
			// Build the query clause for this user
			//-----------------------------------------

			$_or[]	= "( i." . $this->sqlWherePrivacy( $_masks ) . ' AND i.image_member_id=' . $memberId . ' AND ( ' .  $this->DB->buildWherePermission( $_permId, 'i.image_parent_permission', true ) . ') )';
		}
		
		//-----------------------------------------
		// Join the 'or' clauses
		//-----------------------------------------

		if ( count( $_or ) )
		{
			$where[] = implode( " OR ", $_or );
		}
		
		//-----------------------------------------
		// Check if image needs to be approved
		//-----------------------------------------

		$where[]	= $this->sqlWhereApproved( $filters );
		
		//-----------------------------------------
		// Pass request to central processing function
		//-----------------------------------------

		return $this->_fetchImages( $where, $filters );
	}
	
	/**
	 * Fetch images for your friends
	 *
	 * @param	int			Member ID (the person viewing)
	 * @param	array		Sort options (sortKey, sortOrder, offset, limit, unixCutOff)
	 * @return	@e array	Array of images and album information
	 */
	public function fetchFriendsImages( $memberId, $filters=array() )
	{
		//-----------------------------------------
		// Set filters
		//-----------------------------------------

		$memberId	= intval( $memberId );
		$filters	= $this->_setFilters( $filters );
		$where		= array();
		$_fids		= array();
		
		//-----------------------------------------
		// If there is no member ID, no friends
		//-----------------------------------------

		if ( ! $memberId )
		{
			return array();
		}
		
		//-----------------------------------------
		// Check if image needs to be approved
		//-----------------------------------------

		$where[]	= $this->sqlWhereApproved( $filters );
		
		$where[]	= "i." . $this->sqlWherePrivacy( array( 'friend', 'public', 'category' ) );
		
		//-----------------------------------------
		// Grab friend IDs
		//-----------------------------------------

		if( $memberId == $this->memberData['member_id'] )
		{
			//-----------------------------------------
			// Grab from cache to save a query if we can
			//-----------------------------------------

			$_fids	= array_slice( array_keys( $this->memberData['_cache']['friends'] ), 0, 300 );
		}
		else
		{
			$this->DB->build( array(
									'select'	=> '*',
									'from'		=> 'profile_friends',
									'where'		=> 'friends_member_id=' . $memberId . ' AND friends_approved=1',
									'limit'		=> array(0, 250)
							)		);
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$_fids[]	= intval( $row['friends_friend_id'] );
			}
		}
		
		//-----------------------------------------
		// Found any friend IDs?
		//-----------------------------------------

		if ( count( $_fids ) )
		{
			$where[]	= "i.image_member_id IN (" . implode( ",", $_fids ) . ")";
		}
		
		//-----------------------------------------
		// Pass request to central processing function
		//-----------------------------------------

		return $this->_fetchImages( $where, $filters );
	}
	
	/**
	 * Fetch featured image
	 * 
	 * @param	int			Number of featured images to return
	 * @return	@e array	Array of image info (or empty array if no featured images)
	 */
	public function fetchFeatured( $limit=1 )
	{
		//-----------------------------------------
		// Make sure we have a proper limit
		//-----------------------------------------

		if ( !$limit )
		{
			return array();
		}
		
		//-----------------------------------------
		// Fetch the images and return
		//-----------------------------------------
		
		$images	= $this->fetchImages( $this->memberData['member_id'], array( 'featured' => true, 'limit' => $limit, 'sortKey' => 'image_date', 'sortOrder' => 'desc', 'excludeMedia' => true /*, 'unixCutOff' => GALLERY_A_YEAR_AGO*/ ) );

		foreach( $images as $k => $v )
		{
			$images[ $k ]['mediumUrl']		= $this->makeImageTag( $v, array( 'type' => 'medium', 'link-type' => 'src' ) );
		}

		return $images;
	}
	
	/**
	 * Generate photostrip
	 *
	 * @param	array  		Image array
	 * @param	mixed		Null, or [left|right] to indicate direction to pull from
	 * @param	mixed		Null, or integer position offset
	 * @return	@e mixed	Photostrip html, or array of data
	 */
	public function fetchPhotoStrip( $image, $jumpDirection=null, $directionPos=null )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$images			= array();
		$directionPos	= ( $directionPos == null ) ? 0 : $directionPos;
		$_jd			= '';
		
		//-----------------------------------------
		// Fetch image if we just have an ID
		//-----------------------------------------

		if ( is_numeric( $image ) )
		{
			$image	= $this->fetchImage( $image );
		}
		
		//-----------------------------------------
		// If we don't have the image, return now
		//-----------------------------------------

		if ( ! count( $image ) OR ! isset( $image['image_id'] ) )
		{
			return $jumpDirection ? array() : '';
		}
		
		//-----------------------------------------
		// Are we in an album or category?
		//-----------------------------------------

		if( $image['image_album_id'] )
		{
			$album		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['image_album_id'] );
			$where		= 'image_album_id=' . $image['image_album_id'];
			$order		= $album['album_sort_options__key'] ? $album['album_sort_options__key'] : 'image_date';
			$dir		= strtolower( $album['album_sort_options__dir'] );
		}
		else
		{
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'] );
			$where		= '( image_album_id=0 AND image_category_id=' . $image['image_category_id'] . ')';
			$order		= $category['category_sort_options__key'] ? $category['category_sort_options__key'] : 'image_date';
			$dir		= strtolower( $category['category_sort_options__dir'] );
		}

		//-----------------------------------------
		// Add appropriate approved flag
		//-----------------------------------------

		$where	= $where . ' AND ' . $this->sqlWhereApproved( array(), '' );
		
		//-----------------------------------------
		// Fix sort key from older versions
		// @link	http://community.invisionpower.com/tracker/issue-36059-driver-error-when-trying-to-view-a-picture/
		// @link	http://community.invisionpower.com/resources/bugs.html/_/ip-gallery/view-full-image-explodes-r38401
		// @link	http://community.invisionpower.com/resources/bugs.html/_/ip-gallery/driver-error-r38756
		//-----------------------------------------

		$order	= ( $order == 'name' ) ? 'image_caption' : $order;
		$order	= ( $order == 'album_name' ) ? 'image_caption' : $order;
		$order	= ( $order == 'caption_seo' ) ? 'image_caption' : $order;
		$order	= ( $order == 'idate' ) ? 'image_date' : $order;
		$order	= ( $order == 'rating' ) ? 'image_rating' : $order;
		$order	= ( $order == 'comments' ) ? 'image_comments' : $order;
		$order	= ( $order == 'views' ) ? 'image_views' : $order;

		if( !in_array( $order, array( 'image_views', 'image_comments', 'image_rating', 'image_date', 'image_caption' ) ) )
		{
			$order	= 'image_date';
		}

		//-----------------------------------------
		// Swap jump direction based on order
		//-----------------------------------------

		if ( $jumpDirection )
		{
			if ( $dir != 'asc' )
			{
				$_jd	= ( $jumpDirection == 'left' ) ? 'right' : 'left';
			}
			else
			{
				$_jd	= $jumpDirection;
			}
		}

		//-----------------------------------------
		// If we jump left, we want 4 more images AFTER the current one
		//-----------------------------------------

		if ( $_jd == 'left' )
		{
			$where	.= " AND " . $order . " < " . intval( $image[ $order ] );
			$dir	= 'desc';
		}

		//-----------------------------------------
		// If we jump right, we want 4 more images BEFORE the current one
		//-----------------------------------------

		else if ( $_jd == 'right' )
		{
			$where	.= " AND " . $order . " > " . intval( $image[ $order ] );
			$dir	= 'asc';
		}
		
		//-----------------------------------------
		// Grab all images in container
		//-----------------------------------------

		$this->DB->build( array(
								'select'	=> "image_id, image_caption, image_caption_seo, image_masked_file_name, image_directory, image_media, image_media_thumb, image_thumbnail",
								'from'		=> 'gallery_images',
								'where'		=> $where,
								'order'		=> $order . ' ' . $dir
						)		);
		$res = $this->DB->execute();

		$total	= $this->DB->getTotalRows( $res );
		$cache	= array();
		$seen	= 0;
		
		//-----------------------------------------
		// Loop over results
		//-----------------------------------------

		while( $row = $this->DB->fetch( $res ) )
		{
			$row['_ourImage']	= 0;

			//-----------------------------------------
			// Increment seen flag
			//-----------------------------------------

			$seen++;
			
			//-----------------------------------------
			// Jump to the right?
			//-----------------------------------------

			if ( $jumpDirection == 'right' )
			{
				$directionPos++;
				$images[ $directionPos ] = $row;
			}

			//-----------------------------------------
			// Jump to the left?
			//-----------------------------------------

			else if ( $jumpDirection == 'left' )
			{
				$directionPos--;
				$images[ $directionPos ] = $row;
			}

			//-----------------------------------------
			// Jump up and down?  Dunno..
			//-----------------------------------------

			else
			{
				//-----------------------------------------
				// Found our current image
				//-----------------------------------------

				if ( $row['image_id'] == $image['image_id'] )
				{
					$row['_ourImage']	= 1;

					//-----------------------------------------
					// This is the 'center' image, so store at position 0
					//-----------------------------------------

					$images[ 0 ]	= $row;
					
					//-----------------------------------------
					// Store navigation cache
					//-----------------------------------------

					$this->_navCache['now'] = $image;
					
					//-----------------------------------------
					// Populate previous two images
					//-----------------------------------------

					if ( array_key_exists( ($seen - 1), $cache ) )
					{
						$images[ -1 ] = $cache[ $seen - 1 ];

						$this->_navCache['prev'] = $cache[ $seen - 1 ];
					}
					
					if ( array_key_exists( ($seen - 2), $cache ) )
					{
						$images[ -2 ] = $cache[ $seen - 2 ];
					}
				}
				else
				{
					//-----------------------------------------
					// We have passed our current image
					//-----------------------------------------

					if ( isset( $images[0]['image_id'] ) AND ! isset( $images[1]['image_id'] ) )
					{
						//-----------------------------------------
						// This is the 'next' image
						//-----------------------------------------

						$images[1] = $row;

						$this->_navCache['next'] = $row;
					}
					else if ( isset( $images[1]['image_id'] ) AND ! isset( $images[2]['image_id'] ) )
					{
						$images[2] = $row;
					}
				}
			}
			
			//-----------------------------------------
			// Cache each image
			//-----------------------------------------

			$cache[ $seen ] = $row;
		
			//-----------------------------------------
			// Only keep our last 5 images
			//-----------------------------------------

			if ( count( $cache ) > 5 )
			{ 
				//-----------------------------------------
				// Delete without reindexing keys
				//-----------------------------------------

				unset( $cache[ $seen - 5 ] );
			}
			
			//-----------------------------------------
			// If we've got all our images, break
			//-----------------------------------------

			if ( count( $images ) == 5 )
			{
				break;
			}
		}
		
		//-----------------------------------------
		// Sort images
		//-----------------------------------------

		ksort( $images );

		//-----------------------------------------
		// Build thumbs
		//-----------------------------------------

		foreach( $images as $id => $im )
		{
			$im['thumb']	= $this->makeImageLink( $im, array( 'type' => 'small', 'link-type' => 'page' ) );
			$images[ $id ]	= $im;
		}
		
		//-----------------------------------------
		// Return the array or HTML
		//-----------------------------------------

		$return = array( 'total' => $total, 'photos' => $images );
		
		return ( $jumpDirection ) ? $return : $this->registry->output->getTemplate( 'gallery_img' )->photostrip( $return );
	}
	
	/**
	 * Fetch next/previous links. 
	 * Returns the image that is next and previous. If you are at left or right of stream then that element will be null
	 * 
	 * @param	int		Image id
	 * @return	@e array
	 */
	public function fetchNextPrevImages( $imageId )
	{
		//-----------------------------------------
		// If we don't have the data cached, build photostrip
		//-----------------------------------------

		if ( ! isset( $this->_navCache['now'] ) OR $imageId != $this->_navCache['now']['image_id'] )
		{
			$this->fetchPhotoStrip( $imageId );
		}
		
		return $this->_navCache;
	}

	/**
	 * Is this image viewable by the current user?
	 * 
	 * @param	mixed	$image	Image ID or image data array
	 * @return	@e boolean
	 */
	public function isViewable( $image )
	{
		//-----------------------------------------
		// Pull image if we just have the ID
		//-----------------------------------------

		if ( is_numeric( $image ) )
		{
			$image	= $this->fetchImage( $image );
		}

		//-----------------------------------------
		// If we're not the owner, make sure image is approved or we can moderate
		//-----------------------------------------

		if ( ! $this->isOwner( $image ) )
		{
			if ( $image['image_approved'] < 1 AND !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) )
			{
				return false;
			}
		}

		//-----------------------------------------
		// Verify we can view
		//-----------------------------------------

		if( $image['image_album_id'] )
		{
			return $this->registry->gallery->helper('albums')->isViewable( $image['image_album_id'] );
		}
		else
		{
			return $this->registry->gallery->helper('categories')->isViewable( $image['image_category_id'] );
		}
	}
	
	/**
	 * Is this image owned by the current user?
	 * 
	 * @param	mixed	$image	Image ID or array of image data
	 * @param	mixed	$member	Null for current user, or array of member data, or member ID
	 * @return	@e boolean
	 */
	public function isOwner( $image, $member=null )
	{
		//-----------------------------------------
		// Pull image if we just have the ID
		//-----------------------------------------

		if ( is_numeric( $image ) )
		{
			$image	= $this->fetchImage( $image );
		}

		//-----------------------------------------
		// Get member data
		//-----------------------------------------

		if ( $member !== null )
		{
			if ( is_numeric( $member ) )
			{
				$member	= IPSMember::load( $member, 'core' );
			}
		}
		else
		{
			$member	= $this->memberData;
		}

		//-----------------------------------------
		// Verify if we are owner
		//-----------------------------------------

		return ( $member['member_id'] == $image['image_member_id'] ) ? true : false;
	}

	/**
	 * Resync image
	 *
	 * @param	mixed		Either image ID or image array
	 * @return	@e void
	 */
	public function resync( $image )
	{
		//-----------------------------------------
		// Pull image if we just have the ID
		//-----------------------------------------

		if ( is_numeric( $image ) )
		{
			$image = $this->fetchImage( $image );
		}

		$image['image_id']	= intval($image['image_id']);
		
		//-----------------------------------------
		// Grab data to resync image
		//-----------------------------------------

		$que	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as ued', 'from' => 'gallery_comments', 'where' => 'comment_img_id=' . $image['image_id'] . ' AND comment_approved=0' ) );
												
		$tot	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as al', 'from' => 'gallery_comments', 'where' => 'comment_img_id=' . $image['image_id'] . ' AND comment_approved=1' ) );
		
		$las	= $this->DB->buildAndFetch( array( 'select' => 'MAX(comment_post_date) as t', 'from' => 'gallery_comments', 'where' => 'comment_img_id=' . $image['image_id'] . ' AND comment_approved=1' ) );

		//-----------------------------------------
		// Find category ID
		//-----------------------------------------

		if( $image['image_album_id'] )
		{
			$album	= $this->DB->buildAndFetch( array( 'select' => 'album_category_id', 'from' => 'gallery_albums', 'where' => 'album_id=' . $image['image_album_id'] ) );

			$image['image_category_id']	= intval($album['album_category_id']);
		}

		//-----------------------------------------
		// Update image record
		//-----------------------------------------

		$this->DB->update( 'gallery_images', array( 'image_category_id' => $image['image_category_id'], 'image_comments' => intval( $tot['al'] ), 'image_last_comment' => intval( $las['t'] ), 'image_comments_queued' => intval( $que['ued'] ) ), 'image_id=' . $image['image_id'] );
	}
	
	/**
	 * Generates the HTML tags for an image.
	 *
	 * OPTS:
	 * --type:			[thumb|small|medium|max] What size image to show
	 * --h1image:		Flag to indicate if image will be used next to <h1> tag
	 * --thumbClass:	CSS classes to use with the image thumbnail
	 * --coverImg:		Flag to indicate if the image is a cover image
	 * --link-type:		[src]: Return URL to image
	 *					[none|page|container]: Returns <img> tag
	 *					[{blank}]: Returns <a> tag for small|medium 'type' and <img> tag for thumb|full type
	 * --link-container-type: [null|category] Set to category to force link to category even if image is in an album (when link-type is container)
	 * 
	 * @since	1.0
	 * @param	array 	Image data
	 * @param	array	options (see above)
	 * @return	@e string
	 */
	public function makeImageTag( $image, $opts=array() )
	{
		//-----------------------------------------
		// Full image but we've exceeded bandwidth?
		//-----------------------------------------

		if ( $opts['type'] != 'thumb' AND $opts['type'] != 'small' )
		{
			if ( ! $this->checkBandwidth() )
			{
				return $this->lang->words['bwlimit'];
			}
		}

		//-----------------------------------------
		// Showing next to <h1>?
		//-----------------------------------------

		if ( $opts['h1image'] )
		{
			$opts['thumbClass']	= 'ipsUserPhoto ipsGallery_h1image_image left galattach';
			$opts['type']		= 'thumb';
		}

		//-----------------------------------------
		// Missing sizes?  Auto-adapt
		//-----------------------------------------

		if( $opts['type'] == 'small' AND ( !$this->settings['gallery_small_image_width'] OR !$this->settings['gallery_small_image_height'] ) )
		{
			$opts['type']	= 'thumb';
		}

		if( $opts['type'] == 'medium' AND ( !$this->settings['gallery_medium_width'] OR !$this->settings['gallery_medium_height'] ) )
		{
			$opts['type']	= 'max';
		}
		
		//-----------------------------------------
		// Format CSS class
		//-----------------------------------------

		if( empty( $opts['thumbClass'] ) )
		{
			$opts['thumbClass']	= ( $opts['type'] == 'medium' ) ? 'galmedium' : ( ( $opts['type'] == 'small' ) ? 'galsmall' : 'galattach' );
		}

		//-----------------------------------------
		// Cover image?
		//-----------------------------------------

		if ( isset( $opts['coverImg'] ) AND $opts['coverImg'] === true )
		{
			$opts['thumbClass']	.= ' cover_img___xx___';
			$opts['link-type']	= $opts['link-type'] ? $opts['link-type'] : 'container';
		}

		//-----------------------------------------
		// Overlays
		//-----------------------------------------

		if( isset( $image['_isRead'] ) && ! $image['_isRead'] )
		{
			$opts['thumbClass']	.= ' hello_i_am_new';
		}

		if( isset( $image['image_approved'] ) && $image['image_approved'] == 0 )
		{
			$opts['thumbClass']	.= ' hello_i_am_unapproved';
		}

		if( isset( $image['image_approved'] ) && $image['image_approved'] == -1 )
		{
			$opts['thumbClass']	.= ' hello_i_am_hidden';
		}

		//-----------------------------------------
		// Directory?
		//-----------------------------------------
		
		$dir		= $image['image_directory']	? "&amp;dir={$image['image_directory']}/"	: "";
		$directory	= $image['image_directory']	? "{$image['image_directory']}/"			: "";

		//-----------------------------------------
		// Does image exist?
		//-----------------------------------------

		$prefix		= ( $opts['type'] == 'thumb' ) ? 'tn_' : ( ( $opts['type'] == 'small' ) ? 'sml_' : '' );
		$filename	= ( $opts['type'] == 'medium' ) ? $image['image_medium_file_name'] : $image['image_masked_file_name'];

		if ( ! file_exists( $this->settings['gallery_images_path'] . '/' . $directory . $prefix . $filename ) )
		{
			if( !defined('GALLERY_SKIP_LOCAL_CHECK') OR !GALLERY_SKIP_LOCAL_CHECK )
			{
				$prefix				= '';
				$opts['type']		= 'max';
				$filename			= $image['image_masked_file_name'];
				$opts['thumbClass']	= str_replace( array( 'galmedium', 'galsmall' ), 'galattach', $opts['thumbClass'] );
			}
		}

		$_imageId	= ( ( !empty($opts['id_prefix']) ) ? $opts['id_prefix'] : $prefix ) . "image_view_{$image['image_id']}";

		if( $opts['coverImg'] === true OR $opts['h1image'] )
		{
			$_imageId	= ( ( !empty($opts['id_prefix']) ) ? $opts['id_prefix'] : $prefix ) . "image_view_{$image['image_id']}_cover";
		}

		//-----------------------------------------
		// Reset full-sized image 'type'
		//-----------------------------------------

		if( $opts['type'] != 'thumb' AND $opts['type'] != 'small' AND $opts['type'] != 'medium' )
		{
			$opts['type']	= 'max';
		}

		//-----------------------------------------
		// Got an image?
		//-----------------------------------------

		if ( ! $image['image_id'] )
		{
			return $this->makeNoPhotoTag( $opts );
		}
		
		//-----------------------------------------
		// Update bandwidth
		//-----------------------------------------
		
		if ( $this->settings['gallery_detailed_bandwidth'] && $this->request['section'] == 'viewimage' )
		{
		  	if ( !in_array( $opts['type'], array( 'thumb', 'small', 'medium' ) ) AND empty( $opts['coverImg'] ) )
		  	{
				$this->DB->insert( 'gallery_bandwidth', array( 'member_id' => $this->memberData['member_id'], 'file_name' => $image['image_masked_file_name'], 'bdate' => time(), 'bsize' => $image['image_file_size'] ), true );
		  	}
		}
		
		//-----------------------------------------
		// Pass to media class if this is a media image
		//-----------------------------------------
		
		if ( $image['image_media'] ) 
		{
			return $this->registry->gallery->helper('media')->getThumb( $image, $opts );
		}
		else 
		{
			//-----------------------------------------
			// Sort out w+h HTML attributes
			//-----------------------------------------

			$size	= '';

			if( !isset($image['_data']) )
			{
				$image['_data']	= @unserialize( $image['image_data'] );
			}

			if ( $opts['type'] AND /*$opts['type'] != 'medium' AND $opts['type'] != 'max' AND*/ isset( $image['_data'] ) AND is_array( $image['_data']['sizes'][ $opts['type'] ] ) AND $image['_data']['sizes'][ $opts['type'] ][0] > 0 )
			{
				$size	= " width='" . intval( $image['_data']['sizes'][ $opts['type'] ][0] ). "' height='" . intval( $image['_data']['sizes'][ $opts['type'] ][1] ) . "' ";
			}

			//-----------------------------------------
			// If this is a thumb and we enabled cropping, force height to same as width
			//-----------------------------------------

			if( $opts['type'] == 'thumb' AND $this->settings['gallery_use_square_thumbnails']  )
			{
				if( $image['_data']['sizes']['thumb'][0] <= 0 )
				{
					$image['_data']['sizes']['thumb'][0]	= $this->settings['gallery_size_thumb_width'];
				}

				$size	= " width='" . intval( $image['_data']['sizes']['thumb'][0] ). "' height='" . intval( $image['_data']['sizes']['thumb'][0] ) . "' ";
			}

			//-----------------------------------------
			// Direct URLs or masked URLs?
			//-----------------------------------------

			if( $this->settings['gallery_images_url'] )
			{
				$imagemg_url	= $this->settings['gallery_images_url'] . '/' . $directory . $prefix . $filename;
			}
			else
			{
				$imagemg_url	= "{$this->settings['board_url']}/index.php?app=gallery&amp;module=images&amp;section=img_ctrl&amp;img={$image['image_id']}&amp;file=" . $opts['type'];
			}

			$imagemg_tag	= "<img src='{$imagemg_url}' class='{$opts['thumbClass']}' title='{$image['image_caption']}' {$size} alt='{$image['image_caption']}' id='{$_imageId}' />";

			//-----------------------------------------
			// Return requested HTML
			//-----------------------------------------

			if ( $opts['link-type'] == 'src' )
			{
				return $imagemg_url;
			}
			else if ( $opts['link-type'] == 'none' OR $opts['link-type'] == 'page' OR $opts['link-type'] == 'container' )
			{
				return $imagemg_tag;
			}
			else
			{
				return "<a href='{$imagemg_url}' class='gal' title='{$image['image_caption']}' alt='{$image['image_caption']}'>{$imagemg_tag}</a>";
			}
		}
	}
	
	/**
	 * Make no photo image tag
	 *
	 * @param	array 		Options
	 * @return	@e string
	 * @todo	Width and height hardcoded to 100px...verify if this is ok
	 */
	public function makeNoPhotoTag( $opts=array() )
	{
		//-----------------------------------------
		// Determine pic to show
		//-----------------------------------------

		switch( $opts['type'] )
		{
			default:
				$img	= 'nophotothumb.png';
			break;
			case 'strip':
				$img	= 'nopicstrip.png';
			break;
		}

		//-----------------------------------------
		// Return the photo tag
		//-----------------------------------------

		if ( $opts['link-type'] == 'src' )
		{
			return "{$this->settings['img_url']}/gallery/{$img}";
		}
		
		return "<img src='{$this->settings['img_url']}/gallery/{$img}' width='100' height='100' class='{$opts['thumbClass']}' />";
	}

	/**
	 * Check bandwidth usage
	 *
	 * @return	@e bool		Show image or not
	 */
	public function checkBandwidth()
	{
		$show	= true;

		//-----------------------------------------
		// Can only check if we store logs..
		//-----------------------------------------

		if ( $this->settings['gallery_detailed_bandwidth'] )
		{
			//-----------------------------------------
			// Build query
			//-----------------------------------------

			$q = array();
			
	  		if ( $this->memberData['g_max_transfer'] != -1 )
	  		{
		 		$q[] = "SUM( bsize ) AS transfer";
	  		}

	  		if ( $this->memberData['g_max_views'] != -1 )
	  		{
		 		$q[] = "COUNT( bsize ) AS views";
	  		}

			//-----------------------------------------
			// If we have restrictions, get details, store and check
			//-----------------------------------------

	  		if( count($q) )
	  		{
		 		if( ! $this->memberData['bandwidth'] )
		 		{
					$time	= time() - ( 60 * 60 * 24 );

					$this->memberData['bandwidth']	= $this->DB->buildAndFetch( array( 'select' => implode( ", ", $q ), 'from' => 'gallery_bandwidth', 'where' => "member_id={$this->memberData['member_id']} AND bdate > {$time}" ) );
				}

		 		if( ! empty( $this->memberData['g_max_transfer'] ) && $this->memberData['bandwidth']['transfer'] > $this->memberData['g_max_transfer'] * 1024 )
		 		{
					$show	= false;
		 		}

		 		if( ! empty( $this->memberData['g_max_views'] ) && $this->memberData['bandwidth']['views'] > $this->memberData['g_max_views'] )
		 		{
					$show	= false;
		 		}
	  		}
		}
		
		return $show;
	}

	/**
	 * Generates a link to an image.  If $tn is set, then
	 * it will generate a thumbnail tag.  Checks bandwith too
	 * 
	 * @since	1.0
	 * @param	array 	Image data
	 * @param	array	Options
	 * @return	@e string
	 * @see		makeImageTag()
	 */
	public function makeImageLink( $image, $opts=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$target	= ( IN_ACP ) ? ' target="_blank" ' : '';

		//-----------------------------------------
		// Got an image?
		//-----------------------------------------

		if ( ! $image['image_id'] )
		{
			return $this->makeImageTag( $image, $opts );
		}
		
		//-----------------------------------------
		// Is the image from an upload session?
		//-----------------------------------------

		if ( ! is_numeric( $image['image_id'] ) AND strlen( $image['image_id'] ) == 32 )
		{
			return $this->makeImageTag( $image, $opts );
		}
		
		//-----------------------------------------
		// Is this a cover image?
		//-----------------------------------------

		if ( isset( $opts['coverImg'] ) AND $opts['coverImg'] === true )
		{
			$opts['link-type']	= $opts['link-type'] ? $opts['link-type'] : 'container';
		}

		//-----------------------------------------
		// Check bandwidth
		//-----------------------------------------

		if ( $opts['type'] != 'thumb' AND $opts['type'] != 'small' )
		{
			if ( ! IN_ACP AND !$this->checkBandwidth() ) 
			{
				return $this->lang->words['bwlimit'];
			}
		}
		
		//-----------------------------------------
		// Build URL to link to
		//-----------------------------------------

		if ( $opts['link-type'] == 'container' )
		{
			//-----------------------------------------
			// Is image within an album or category?
			//-----------------------------------------

			if( $image['image_album_id'] AND $opts['link-container-type'] != 'category' )
			{
				if ( ! empty( $image['album_id'] ) AND ! empty( $image['album_name'] ) )
				{
					$album	= array( 'album_id' => $image['album_id'], 'album_name_seo' => ( ! empty( $image['album_name_seo'] ) ) ? $image['album_name_seo'] : IPSText::makeSeoTitle( $image['album_name'] ) );
				}
				else
				{
					$album	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['image_album_id'] );
				}

				$url	= $this->registry->output->buildSEOUrl( "app=gallery&amp;album={$album['album_id']}", 'public', $album['album_name_seo'], 'viewalbum' );
			}
			else
			{
				$_category	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'] );
				$url		= $this->registry->output->buildSEOUrl( "app=gallery&amp;category={$_category['category_id']}", 'public', $_category['category_name_seo'], 'viewcategory' );
			}
		}
		else
		{
			$url	= $this->registry->output->buildSEOUrl( "app=gallery&amp;image={$image['image_id']}", 'public', $image['image_caption_seo'], 'viewimage' );
		}

		//-----------------------------------------
		// Return the proper HTML
		//-----------------------------------------

		if ( $opts['h1image'] )
		{
			return "<a href='{$url}' {$target} title='{$image['image_caption']}' class='ipsUserPhotoLink {$opts['link-thumbClass']}'>" . $this->makeImageTag( $image, $opts ) . "</a>";
		}
		else
		{			 	
			return "<a href='{$url}' {$target} title='{$image['image_caption']}' class='{$opts['link-thumbClass']}'>" . $this->makeImageTag( $image, $opts ) . "</a>";
		}
	}

	/**
	 * Generic method for validating access to an image
	 * 
	 * @since	2.2
	 * @param	mixed		Int for image ID, or array for image data
	 * @param	boolean		Return instead of outputting error
	 * @param	mixed		Int for member ID, array for member data or null for $this->memberData
	 * @return	@e mixed	Image data / print error
	 */
	public function validateAccess( $image, $return=false, $member=null )
	{
		//-----------------------------------------
		// Get member data
		//-----------------------------------------

		if ( is_numeric( $member ) )
		{
			$member	= IPSMember::load( $member, 'all' );
		}
		elseif ( ! is_array( $member ) OR ! isset( $member['member_id'] ) )
		{
			$member	= $this->memberData;
		}

		//-----------------------------------------
		// Get image data
		//-----------------------------------------

		if ( is_numeric( $image ) )
		{
			$image	= $this->fetchImage( $image );
		}

		//-----------------------------------------
		// If this is a member check their gallery perms
		//-----------------------------------------

		if ( $member['member_id'] )
		{
			$perms	= explode( ':', $member['gallery_perms'] );

			if( ! $perms[0] )
			{
				if( $return )
				{
					return false;
				}

			 	$this->registry->output->showError( 'no_permission', 107161, null, null, 403 );
			}
		}

		//-----------------------------------------
		// Make sure we have our image
		//-----------------------------------------

		if ( ! $image['image_id'] )
		{
			if ( $return )
			{
				return false;
			}

			$this->registry->output->showError( 'img_not_found', 107163, null, null, 404 );
		}

		//-----------------------------------------
		// Verify our container exists
		//-----------------------------------------

		if( $image['image_album_id'] )
		{
			$album		= $this->registry->getClass('gallery')->helper('albums')->fetchAlbum( $image['image_album_id'] );
		}
		else
		{
			if( !$this->registry->getClass('gallery')->helper('categories')->isViewable( $image['image_category_id'] ) )
			{
				if ( $return )
				{
					return false;
				}
			
				$this->registry->output->showError( 'no_permission', 107166, null, null, 403 );
			}
		}
		
		//-----------------------------------------
		// Check image privacy
		//-----------------------------------------

		if ( $image['image_privacy'] > 1 )
		{
			if ( !$this->registry->getClass('gallery')->helper('albums')->isOwner( $album, $member ) AND !$this->registry->getClass('gallery')->helper('albums')->canModerate( $album, $member ) )
			{
				if ( $return )
				{
					return false;
				}
		
				$this->registry->output->showError( 'no_permission', 107167, null, null, 403 );
			}
		}

		//-----------------------------------------
		// Check image is approved
		//-----------------------------------------

		if ( $image['image_approved'] < 1 )
		{
			if ( !$this->registry->getClass('gallery')->helper('categories')->checkIsModerator( $image['image_category_id'], $member, 'mod_can_approve' ) )
			{
				if ( $return )
				{
					return false;
				}
		
				$this->registry->output->showError( 'no_permission', 107167.1, null, null, 403 );
			}
		}
		
		//-----------------------------------------
		// Return the image data
		//-----------------------------------------

		return $image;
	}

	/**
	 * Get the lattitude and longtitude of an image
	 *
	 * @param	mixed	$image
	 * @return	@e array
	 */
	public function getLatLon( $image )
	{
		//-----------------------------------------
		// Load image if necessary
		//-----------------------------------------

		if ( is_numeric( $image ) )
		{
			$image	= $this->fetchImage( $image );
		}

		//-----------------------------------------
		// Format array with GPS data
		//-----------------------------------------

		if ( $image['image_gps_lat'] && $image['image_gps_lon'] )
		{
			return array( $image['image_gps_lat'], $image['image_gps_lon'] );
		}
		else
		{
			return array( false, false );
		}
	}

	/**
	 * Updates the image with reverse geo look up data
	 *
	 * @param	mixed	$image
	 * @return	@e void
	 */
	public function setReverseGeoData( $image )
	{
		//-----------------------------------------
		// Get mapping class
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/mapping/bootstrap.php' );/*noLibHook*/
		$_mapping	= classes_mapping::bootstrap( IPS_MAPPING_SERVICE );

		//-----------------------------------------
		// Load the image if necessary
		//-----------------------------------------

		if ( is_numeric( $image ) )
		{
			$image	= $this->fetchImage( $image );
		}
		
		//-----------------------------------------
		// Make sure we have the image data
		//-----------------------------------------

		if ( empty( $image['image_id'] ) )
		{
			return;
		}

		//-----------------------------------------
		// Get latitude and longitude
		//-----------------------------------------

		$latLon	= $this->getLatLon( $image );
		
		if ( $latLon[0] !== false )
		{
			//-----------------------------------------
			// Get location data
			//-----------------------------------------

			if ( empty( $image['image_loc_short'] ) )
			{
				$_data				= $_mapping->reverseGeoCodeLookUp( $latLon[0], $latLon[1] );
				$image_loc_short	= $_data['geocache_short'];
			}
			else
			{
				$image_loc_short	= $image['image_loc_short'];
			}
			
			if ( $image_loc_short && $image_loc_short !== false )
			{ 
				$this->DB->update( 'gallery_images', array( 'image_loc_short' => $image_loc_short ), 'image_id=' . intval( $image['image_id'] ) );
			}
		}
	}
	
	/**
	 * Extracts exif data
	 * 
	 * @param	string		path to file
	 * @return	@e array	exif data
	 * @since	2.1
	 * @todo	Just store the language key and parse on display
	 */  
	public function extractExif( $file )
	{
		//-----------------------------------------
		// Load the language file if needed
		//-----------------------------------------

		if( !$this->lang->words['check_key'] )
		{
			$this->lang->loadLanguageFile( array( 'public_meta' ), 'gallery' );
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$return	= array();
		$gps	= array();

		//-----------------------------------------
		// Return if we don't have the extension
		//-----------------------------------------

		if( !extension_loaded('exif') )
		{
			return $return;
		}

		//-----------------------------------------
		// Return if the file doesn't exist
		//-----------------------------------------

		if( !file_exists( $file ) )
		{
			return $return;
		}

		//-----------------------------------------
		// Can exif module read the file?
		//-----------------------------------------

		if( @exif_imagetype( $file ) )
		{
			//-----------------------------------------
			// Read the exif data
			//-----------------------------------------

			$data	= @exif_read_data( $file, 0, true );

			if( is_array($data) AND count($data) > 0 )
			{
				//-----------------------------------------
				// Loop over the exif data
				//-----------------------------------------

				foreach( $data as $k => $v )
				{
					if ( $k == 'GPS' )
					{
						$gps	= $data['GPS'];
						continue;
					}				

					//-----------------------------------------
					// Loop deeper...
					//-----------------------------------------

					foreach( $v as $k2 => $v2 )
					{
						if( ( is_string($v2) OR is_numeric($v2) ) AND !is_null($v2) )
						{
							$key	= $k . '.' . $k2;

							//-----------------------------------------
							// This can have special chars that break serialization
							//-----------------------------------------

							if ( strstr( $key, 'UndefinedTag' ) )
							{
								continue;
							}

							//-----------------------------------------
							// If array key exists, we have localization
							//-----------------------------------------

							if( array_key_exists( $key, $this->lang->words ) )
							{
								//-----------------------------------------
								// If the value is blank, skip
								//-----------------------------------------

								if( !$this->lang->words[ $key ] )
								{
									continue;
								}
								else
								{
									//-----------------------------------------
									// This value has a localized mapping
									// Just store the key/value and translate on display
									//-----------------------------------------

									if( array_key_exists( $key . '_map_' . $v2, $this->lang->words ) )
									{
										$return[ $key ]	= $v2;
									}
									else
									{
										$return[ $key ]	= htmlspecialchars($v2);
									}
								}
							}
							else
							{
								$return[ $key ] = htmlspecialchars($v2);
							}
						}
					}
				}
			}
		}

		//-----------------------------------------
		// If we have GPS data, set it properly
		//-----------------------------------------

		if ( is_array( $gps ) AND count( $gps ) )
		{
			foreach( $gps as $k => $v )
			{
				if( array_key_exists( $k, $this->lang->words ) )
				{
					// Probably commonly corrupted
					if( !$this->lang->words[ $k ] )
					{
						unset( $gps[ $k ] );
					}
				}
			}
			
			$return['GPS']	= $gps;
		}
		
		return $return;
	}
	
	/**
	 * Rebuild exif data
	 * 
	 * @param	mixed		$image		Image Data
	 * @return	@e boolean
	 * @note	Only used in upgrade routines or tools that may need it
	 */
	public function rebuildExif( $image )
	{
		//-----------------------------------------
		// Get image data if needed
		//-----------------------------------------

		if ( is_numeric( $image ) )
		{
			$image	= $this->fetchImage( $image );
		}
		
		if ( empty( $image['image_id'] ) )
		{
			return false;
		}
		
		//-----------------------------------------
		// Build paths
		//-----------------------------------------

		$dir	= $image['image_directory'] ? $image['image_directory'] . "/" : '';
		$large	= $this->settings['gallery_images_path'] . '/' . $dir . $image['image_masked_file_name'];
		$orig	= $this->settings['gallery_images_path'] . '/' . $dir . $image['image_original_file_name'];
		$file	= ( file_exists( $orig ) ) ? $orig : ( file_exists( $large ) ? $large : null );

		//-----------------------------------------
		// Found the file?
		//-----------------------------------------

		if ( $file !== null )
		{
			$exif	= $this->extractExif( $file );
			
			if ( is_array( $exif ) )
			{
				$this->DB->update( 'gallery_images', array( 'image_metadata' => serialize( $exif ) ), 'image_id=' . intval( $image['image_id'] ) );
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Converts EXIF GPS data to lat/lon
	 * 
	 * @param	array	$exif	Exif Data
	 * @return	@e string
	 */
	public function convertExifGpsToLatLon( array $exif )
	{
		//-----------------------------------------
		// If we have the necessary GPS data, extract and return
		//-----------------------------------------

		if ( isset( $exif['GPSLatitudeRef'] ) && isset( $exif['GPSLatitude'] ) && isset( $exif['GPSLongitudeRef'] ) && isset( $exif['GPSLongitude'] ) )
		{
			return array( $this->_exifToCoords( $exif['GPSLatitudeRef'], $exif['GPSLatitude'] ), $this->_exifToCoords( $exif['GPSLongitudeRef'], $exif['GPSLongitude'] ) );
		}
		else
		{
			return array( 0, 0 );
		}
	}
	
	/**
	 * Converts degrees to coordinates
	 *
	 * @param	string		$ref
	 * @param	array		$coord
	 * @return	@e string
	 */
	private function _exifToCoords( $ref, $coord )
	{
		//-----------------------------------------
		// Format prefix
		//-----------------------------------------

		$prefix	= ( $ref == 'S' || $ref == 'W' ) ? '-' : '';
		
		return $prefix . sprintf( '%.6F', $this->_exifToNumber( $coord[0], '%.6F' ) + ( ( ( $this->_exifToNumber( $coord[1], '%.6F' ) * 60 ) + ( $this->_exifToNumber( $coord[2], '%.6F' ) ) ) / 3600 ) );
	}
	
	/**
	 * Converts degrees into int
	 *
	 * @param	string		$v
	 * @param	string		$f
	 * @return	@e string
	 */
	private function _exifToNumber( $v, $f )
	{
		//-----------------------------------------
		// Do we have a / character?
		//-----------------------------------------

		if ( strpos( $v, '/' ) === false )
		{
			return sprintf( $f, $v );
		}
		else
		{
			list( $base, $divider )	= split( "/", $v, 2 );
			
			if ( $divider == 0 )
			{
				return sprintf( $f, 0 );
			}
			else
			{
				return sprintf( $f, ( $base / $divider ) );
			}
		}
	}
	
	/**
	 * Extracts iptc data
	 *
	 * @access	public
	 * @param	string	path to file
	 * @return	@e array	exif data
	 * @since	2.1
	 */
	public function extractIptc( $file )
	{
		//-----------------------------------------
		// Load language file if necessary
		//-----------------------------------------

		if( !$this->lang->words['check_key'] )
		{
			$this->lang->loadLanguageFile( array( 'public_meta' ), 'gallery' );
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$return = array();

		//-----------------------------------------
		// Make sure file exists
		//-----------------------------------------

		if( !file_exists( $file ) )
		{
			return $return;
		}

		//-----------------------------------------
		// Retrieve marker
		//-----------------------------------------

		$size	= @getimagesize( $file, $info );	  

		if( is_array($info) ) 
		{
			//-----------------------------------------
			// Parse IPTC data
			//-----------------------------------------

			$iptc	= iptcparse( $info["APP13"] );
			
			if( is_array($iptc) AND count($iptc) )
			{
				foreach( array_keys($iptc) as $s ) 
				{
					//-----------------------------------------
					// This marker can cause issues
					//-----------------------------------------

					if( $s == '2#000' )
					{
						continue;
					}

					//-----------------------------------------
					// Retrieve IPTC key
					//-----------------------------------------

					$key = $this->getIptcKey( $s );

					//-----------------------------------------
					// Format return array
					//-----------------------------------------

					$c	= count( $iptc[$s] );

					for ($i=0; $i < $c; $i++)
					{
						$return[$key] = htmlspecialchars($iptc[$s][$i]);
					}
				}
			}
		}
			
		return $return;
	}
	
	/**
	 * Returns IPTC Name
	 *
	 * @param	string		$key	Key to get the IPTC name for
	 * @return	@e string
	 */
	private function getIptcKey( $key )
	{
		//-----------------------------------------
		// Retrieve IPTC key mapped to lang string if available
		//-----------------------------------------

		$keys	= array(	'2#003'	=> 'IPTC.ObjectTypeReference',
							'2#010'	=> 'IPTC.Urgency',
							'2#005'	=> 'IPTC.ObjectName',
							'2#120'	=> 'IPTC.Caption',
							'2#110'	=> 'IPTC.Credit',
							'2#015'	=> 'IPTC.Category',
							'2#020'	=> 'IPTC.SupplementalCategories',
							'2#040'	=> 'IPTC.ActionAdvised',
							'2#055'	=> 'IPTC.DateCreated',
							'2#060'	=> 'IPTC.TimeCreated',
							'2#025'	=> 'IPTC.Keywords',
							'2#080'	=> 'IPTC.By-line',
							'2#085'	=> 'IPTC.By-LineTitle',
							'2#090'	=> 'IPTC.City',
							'2#095'	=> 'IPTC.State',
							'2#101'	=> 'IPTC.Country',
							'2#103'	=> 'IPTC.OTR',
							'2#105'	=> 'IPTC.Headline',
							'2#115'	=> 'IPTC.Source',
							'2#116'	=> 'IPTC.CopyrightNotice',
							'2#118'	=> 'IPTC.Contact',
							'2#122'	=> 'IPTC.CaptionWriter',
						);
		
		if( $this->lang->words[ 'IPTC' . str_replace( "#", "", $key ) ] )
		{
			return $this->lang->words[ 'IPTC' . str_replace( "#", "", $key ) ];
		}
		else if( array_key_exists( $key, $keys ) )
		{
			return $keys[$key];
		}
		else
		{
			return $key;
		}
	}
	
	/**
	 * Returns true if the image should get a watermark applied
	 * 
	 * @param	mixed		$image		Image ID or array
	 * @return	@e bool
	 */
	public function applyWatermark( $image )
	{
		//-----------------------------------------
		// Get image data if needed
		//-----------------------------------------

		if ( is_numeric( $image ) )
		{
			$image	= $this->fetchImage( $image );
		}
		
		if ( empty( $image['image_id'] ) )
		{
			return false;
		}

		//-----------------------------------------
		// Get category
		//-----------------------------------------

		$_category	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'] );

		//-----------------------------------------
		// Does category disable watermarks?
		//-----------------------------------------

		if( $_category['category_watermark'] == 0 )
		{
			return false;
		}

		//-----------------------------------------
		// Does category force watermarks?
		//-----------------------------------------

		if( $_category['category_watermark'] == 2 )
		{
			return true;
		}

		//-----------------------------------------
		// Otherwise, is this in an album and does album allow watermarks?
		//-----------------------------------------

		if( $image['image_album_id'] AND $this->registry->gallery->helper('albums')->canWatermark( $image['image_album_id'] ) )
		{
			$album	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['image_album_id'] );

			return $album['album_watermark'] ? true : false;
		}

		//-----------------------------------------
		// If we're still here, no watermark
		//-----------------------------------------

		return false;
	}

	/**
	 * Builds an image sized copies based on the settings
	 * 
	 * @param	array		$image		Image data
	 * @param	array		$opts		Build options (album_id|destination|watermark)
	 * @return	@e bool
	 */
	public function buildSizedCopies( $image=array(), $opts=array() )
	{
		//-----------------------------------------
		// Got an image?
		//-----------------------------------------

		if ( ! count($image) )
		{
			return false;
		}

		//-----------------------------------------
		// Is it a media file?
		//-----------------------------------------

		if ( $image['image_media'] )
		{
			return false;
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$dir		= $image['image_directory'] ? $image['image_directory'] . "/" : '';
		$_return	= array();
		$imData		= array();
		$_save		= array();
		$settings	= array(
							'image_path'	=> $this->settings['gallery_images_path'] . '/' . $dir, 
							'image_file'	=> $image['image_masked_file_name'],
							'im_path'		=> $this->settings['gallery_im_path'],
							'temp_path'		=> DOC_IPS_ROOT_PATH . '/cache/tmp',
							'jpg_quality'	=> GALLERY_JPG_QUALITY,
							'png_quality'	=> GALLERY_PNG_QUALITY
							);

		$this->settings['gallery_size_thumb_width']		= ( $this->settings['gallery_size_thumb_width'] > 0 ) ? $this->settings['gallery_size_thumb_width'] : 100;
		$this->settings['gallery_size_thumb_height']	= ( $this->settings['gallery_size_thumb_height'] > 0 ) ? $this->settings['gallery_size_thumb_height'] : $this->settings['gallery_size_thumb_width'];

		//-----------------------------------------
		// Copy original image before rebuilding
		//-----------------------------------------

		if ( $image['image_original_file_name'] AND $image['image_original_file_name'] != $image['image_masked_file_name'] )
		{
			@unlink( "{$this->settings['gallery_images_path']}/{$image['image_directory']}/{$image['image_masked_file_name']}" );
			@copy( "{$this->settings['gallery_images_path']}/{$image['image_directory']}/{$image['image_original_file_name']}", "{$this->settings['gallery_images_path']}/{$image['image_directory']}/{$image['image_masked_file_name']}" );
		}

		//-----------------------------------------
		// Check image data
		//-----------------------------------------

		$_default	= $settings['image_path'] . $image['image_masked_file_name'];
		$thumb		= $settings['image_path'] . 'tn_' . $image['image_masked_file_name'];
		$medium		= $settings['image_path'] . 'med_' . $image['image_masked_file_name'];
		$small		= $settings['image_path'] . 'sml_' . $image['image_masked_file_name'];
		$watermark	= ( empty($this->settings['gallery_watermark_path']) || !is_file($this->settings['gallery_watermark_path']) ) ? false : ( empty($opts['watermark']) ? $this->applyWatermark($image) : $opts['watermark'] );

		//-----------------------------------------
		// Regular image, or temp?
		//-----------------------------------------

		if ( empty($opts['destination']) AND !is_numeric($image['image_id']) AND strlen($image['image_id']) == 32 )
		{
			$opts['destination']	= 'uploads';
		}

		//-----------------------------------------
		// Figure out query params
		//-----------------------------------------

		if ( $opts['destination'] == 'uploads' )
		{
			$_table		= 'gallery_images_uploads';
			$_field		= 'upload_medium_name';
			$_original	= 'upload_file_name_original';
			$_thumb		= 'upload_thumb_name';
			$_dataField	= 'upload_data';
			$_where		= "upload_key='{$image['image_id']}'";
			$_size		= 'upload_file_size';
		}
		else
		{
			$_table		= 'gallery_images';
			$_field		= 'image_medium_file_name';
			$_original	= 'image_original_file_name';
			$_thumb		= '';
			$_dataField	= 'image_data';
			$_where		= 'image_id=' . $image['image_id'];
			$_size		= 'image_file_size';
		}
		
		//-----------------------------------------
		// A little more set up
		//-----------------------------------------

		$_save[ $_dataField ]	= ( IPSLib::isSerialized( $image[ $_dataField ] ) ) ? unserialize( $image[ $_dataField ] ) : '';
		$_save[ $_field ]		= '';
		
		if ( ! empty( $_thumb  ) )
		{
			$_save[ $_thumb ]	= '';
		}
		
		//-----------------------------------------
		// Check we have the file
		//-----------------------------------------

		if ( ! file_exists( $_default ) )
		{
			return false;
		}
		else
		{
			@chmod( $_default, IPS_FILE_PERMISSION );
		}
		
		//-----------------------------------------
		// Get image library from kernel
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
		
		//-----------------------------------------
		// Get rid of thumb images if they exist
		//-----------------------------------------

		if ( file_exists( $thumb ) )
		{
			@unlink( $thumb );
		}

		if ( file_exists( $small ) )
		{
			@unlink( $small );
		}

		if ( file_exists( $medium ) AND $medium != $_default )
		{
			@unlink( $medium );
		}

		//-----------------------------------------
		// Initialize library
		//-----------------------------------------

		$img	= ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
		
		//-----------------------------------------
		// Build thumbnail
		//-----------------------------------------

		if ( $img->init( $settings ) )
		{
			//-----------------------------------------
			// Are we cropping?
			//-----------------------------------------

			if ( $this->settings['gallery_use_square_thumbnails'] )
			{
				$return	= $img->croppedResize( $this->settings['gallery_size_thumb_width'], $this->settings['gallery_size_thumb_height'] );

				$_save[ $_dataField ]['sizes']['thumb']		= array( $return['newWidth'], $return['newHeight'] );
				$_save[ $_dataField ]['sizes']['original']	= array( $return['originalWidth'], $return['originalHeight'] );
			}
			else
			{
				$return	= $img->resizeImage( $this->settings['gallery_size_thumb_width'], $this->settings['gallery_size_thumb_height'], false, false, array( $this->settings['gallery_size_thumb_width'], $this->settings['gallery_size_thumb_height'] ) );

				$_save[ $_dataField ]['sizes']['thumb']		= array( $return['newWidth'], $return['newHeight'] );
				$_save[ $_dataField ]['sizes']['original']	= array( $return['originalWidth'], $return['originalHeight'] );
			}
			
			if( $return['originalWidth'] != $return['newWidth'] OR $return['originalHeight'] != $return['newHeight'] )
			{
				if ( $img->writeImage( $thumb ) )
				{
					@chmod( $thumb, IPS_FILE_PERMISSION );
					
					if ( ! empty( $_thumb  ) )
					{
						$_save[ $_thumb ] = 'tn_'  . $image['image_masked_file_name'];
					}
				}
			}
			else
			{
				@copy( $settings['image_path'] . '/' . $settings['image_file'], $thumb );
				@chmod( $thumb, IPS_FILE_PERMISSION );

				if ( ! empty( $_thumb  ) )
				{
					$_save[ $_thumb ] = 'tn_'  . $image['image_masked_file_name'];
				}
			}
		}
		
		unset( $img );
		
		//-----------------------------------------
		// Build medium image, if admin enables
		//-----------------------------------------

		if( $this->settings['gallery_medium_width'] AND $this->settings['gallery_medium_height'] )
		{
			//-----------------------------------------
			// Re-initialize image library
			//-----------------------------------------

			$img	= ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
			
			if ( $img->init( $settings ) )
			{
				//-----------------------------------------
				// Resize
				//-----------------------------------------

				$return	= $img->resizeImage( $this->settings['gallery_medium_width'], $this->settings['gallery_medium_height'] );
				
				$_save[ $_dataField ]['sizes']['medium']	= array( $return['newWidth'], $return['newHeight'] );
			
				//-----------------------------------------
				// Watermark?
				//-----------------------------------------

				if ( $watermark )
				{
					$img->addWatermark( $this->settings['gallery_watermark_path'], intval($this->settings['gallery_watermark_opacity']) );
				}

				//-----------------------------------------
				// Got something?
				//-----------------------------------------

				if ( $watermark || ( count($return) && ( $return['originalWidth'] != $return['newWidth'] OR $return['originalHeight'] != $return['newHeight'] ) ) )
				{
					if ( $img->writeImage( $medium ) )
					{
						@chmod( $medium, IPS_FILE_PERMISSION );
						
						$_save[ $_field ] = 'med_' . $image['image_masked_file_name'];
					}
					else
					{
						$_save[ $_field ] = $image['image_masked_file_name'];
					}
				}
				else
				{
					$_save[ $_field ]	= $image['image_masked_file_name'];
				}
			}
			
			unset( $img );
		}
		
		//-----------------------------------------
		// Build small image, if user enables
		//-----------------------------------------

		if( $this->settings['gallery_small_image_width'] AND $this->settings['gallery_small_image_height'] )
		{
			//-----------------------------------------
			// Re-initialize image library
			//-----------------------------------------

			$img	= ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
			
			if ( $img->init( $settings ) )
			{
				//-----------------------------------------
				// Resize
				//-----------------------------------------

				$return	= $img->resizeImage( $this->settings['gallery_small_image_width'], $this->settings['gallery_small_image_height'] );

				$_save[ $_dataField ]['sizes']['small']	= array( $return['newWidth'], $return['newHeight'] );

				//-----------------------------------------
				// Save to disk
				//-----------------------------------------

				if( $return['originalWidth'] != $return['newWidth'] OR $return['originalHeight'] != $return['newHeight'] )
				{
					if ( $img->writeImage( $small ) )
					{
						@chmod( $small, IPS_FILE_PERMISSION );
					}
				}
				else
				{
					@copy( $settings['image_path'] . '/' . $settings['image_file'], $small );
					@chmod( $small, IPS_FILE_PERMISSION );
				}
			}
			
			unset( $img );
		}
		
		//-----------------------------------------
		// Resizing the 'large' image?
		//-----------------------------------------

		$img	= ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );

		if ( $img->init( $settings ) )
		{
			//-----------------------------------------
			// Resize
			//-----------------------------------------

			$return	= $img->resizeImage( $this->settings['gallery_max_img_width'], $this->settings['gallery_max_img_height'] );
			
			$_save[ $_dataField ]['sizes']['max']	= array( $return['newWidth'], $return['newHeight'] );

			//-----------------------------------------
			// Watermarking?
			//-----------------------------------------

			if( $watermark )
			{
				//-----------------------------------------
				// Save the original image so re-watermarking later is ok
				//-----------------------------------------

				$_save[ $_original ]	= $image['image_original_file_name'] ? $image['image_original_file_name'] : md5( IPS_UNIX_TIME_NOW . $image['image_masked_file_name'] . $this->settings['gallery_watermark_path'] . $this->settings['gallery_watermark_opacity'] ) . '.' . IPSText::getFileExtension($image['image_masked_file_name']);
				
				//-----------------------------------------
				// Copy the original image, avoiding copy()
				//-----------------------------------------

				if ( ! file_exists( $settings['image_path'] . $_save[ $_original ] ) )
				{
					$_orig	= @file_get_contents( $_default );
					
					if ( $_orig )
					{
						@file_put_contents( $settings['image_path'] . $_save[ $_original ], $_orig );
					}
				}
				else
				{
					//-----------------------------------------
					// We already have an original
					//-----------------------------------------

					unset($_save[ $_original ]);
				}
				
				$img->addWatermark( $this->settings['gallery_watermark_path'], intval($this->settings['gallery_watermark_opacity']) );
			}

			//-----------------------------------------
			// Save if necessary
			//-----------------------------------------

			if ( $watermark || ( count($return) && ( $return['originalWidth'] != $return['newWidth'] OR $return['originalHeight'] != $return['newHeight'] ) ) )
			{
				$img->writeImage( $_default );
			}

			$_save[ $_size ]		= filesize( $_default );
			
			@chmod( $_default, IPS_FILE_PERMISSION );
		}
		
		unset( $img );
		
		//-----------------------------------------
		// Serialize the image_data field containing sizes
		//-----------------------------------------

		if ( ! empty( $_save[ $_dataField ] ) )
		{
			$_save[ $_dataField ] = serialize( $_save[ $_dataField ] );
		}

		//-----------------------------------------
		// Update and return
		//-----------------------------------------

		$this->DB->update( $_table, $_save, $_where );
		
		return $_return;
	}
	
	/**
	 * Rotates an image
	 *
	 * @param	array		$imgData		Array of image data
	 * @param	integer		$angle			Angle to rotate the image
	 * @return	@e bool
	 * @link	http://www.php.net/manual/en/function.imagerotate.php#93692
	 * @todo	[Future] Move the image manipulation functionality to kernel
	 */
	public function rotateImage( $imgData, $angle=90 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$hasOriginal	= false;
		$fullImgPath	= $this->settings['gallery_images_path'] . '/' . $imgData['image_directory'] . '/' . $imgData['image_masked_file_name'];
		
		//-----------------------------------------
		// Check details
		//-----------------------------------------

		if ( $imgData['image_original_file_name'] && $imgData['image_original_file_name'] != $imgData['image_masked_file_name'] && is_file( $this->settings['gallery_images_path'] . '/' . $imgData['image_directory'] . '/' . $imgData['image_original_file_name'] ) )
		{
			$hasOriginal	= true;
			$fullImgPath	= $this->settings['gallery_images_path'] . '/' . $imgData['image_directory'] . '/' . $imgData['image_original_file_name'];
		}
		
		//-----------------------------------------
		// Using imagemagick?
		//-----------------------------------------

		if( $this->settings['gallery_img_suite'] == 'im' )
		{
			$angle	= ($angle == "-90") ? "90" : "-90";
			//print str_replace( "&#092;", "\\",  $this->settings['gallery_im_path'] ) . "/convert -rotate \"{$angle}\" {$fullImgPath} {$fullImgPath}";exit;
			system( str_replace( "&#092;", "\\",  $this->settings['gallery_im_path'] ) . "/convert -rotate \"{$angle}\" {$fullImgPath} {$fullImgPath}" );
		}

		//-----------------------------------------
		// Using GD
		//-----------------------------------------

		else
		{
			//-----------------------------------------
			// What type of image?
			//-----------------------------------------

			$imgExtension	= IPSText::getFileExtension( $imgData['image_masked_file_name'] );
        	
			//-----------------------------------------
			// Create appropriate resource
			//-----------------------------------------

			switch( $imgExtension )
			{
				case 'gif':
					$image	= imagecreatefromgif( $fullImgPath );
				break;
				
				case 'jpeg':
				case 'jpg':
				case 'jpe':
					$image	= imagecreatefromjpeg( $fullImgPath );
				break;
				
				case 'png':
					$image	= imagecreatefrompng( $fullImgPath );
				break;
			}

			if( ! $image )
			{
				return false;
			}

			//-----------------------------------------
			// Use imagerotate if possible
			//-----------------------------------------

			if( function_exists('imagerotate') )
			{
				$rotatedImg	= imagerotate( $image, $angle, 0 );
			}
			else
			{
				//-----------------------------------------
				// Workaround if imagerotate is not available
				//-----------------------------------------

				$width	= imagesx( $image );
				$height	= imagesy( $image );

				switch( $angle )
				{
					case 0:
					case 360:
						$rotatedImg	= null;
					break;

					case 90:
						$rotatedImg	= imagecreatetruecolor( $height, $width );
					break;

					case 180:
						$rotatedImg	= imagecreatetruecolor( $width, $height );
					break;

					case 270:
						$rotatedImg	= imagecreatetruecolor( $height, $width );
					break;
				}

				if( $rotatedImg )
				{
					for( $i = 0; $i < $width; $i++ )
					{
						for( $j = 0; $j < $height; $j++ )
						{
							if( !$rotatedImg )
							{
								break 2;
							}

							$reference	= imagecolorat( $image, $i, $j );

							switch( $angle )
							{
								case 90:
									if( !@imagesetpixel( $rotatedImg, ($height - 1) - $j, $i, $reference ) )
									{
										$rotatedImg	= null;
									}
								break;

								case 180:
									if( !@imagesetpixel( $rotatedImg, $width - $i, ($height - 1) - $j, $reference ) )
									{
										$rotatedImg	= null;
									}
								break;

								case 270:
									if( !@imagesetpixel( $rotatedImg, $j, $width - $i, $reference ) )
									{
										$rotatedImg	= null;
									}
								break;
							}
						}
					}
				}
			}

			//-----------------------------------------
			// If rotated image does not exist, we're done
			//-----------------------------------------

			if( ! $rotatedImg )
			{
				return false;
			}
			
			//-----------------------------------------
			// Save the image
			//-----------------------------------------

			switch( $imgExtension )
			{
				case 'gif':
					$image	= imagegif( $rotatedImg, $fullImgPath );
				break;
				
				case 'jpeg':
				case 'jpg':
				case 'jpe':
					$image	= imagejpeg( $rotatedImg, $fullImgPath );
				break;
				
				case 'png':
					$image	= imagepng( $rotatedImg, $fullImgPath );
				break;
			}

			//-----------------------------------------
			// Clean up resource usage
			//-----------------------------------------

			imagedestroy( $rotatedImg );
			
			if( ! $image )
			{
				return false;
			}
		}

		//-----------------------------------------
		// Copy the original image back over
		//-----------------------------------------

		if ( $hasOriginal )
		{
			@unlink( $this->settings['gallery_images_path'] . '/' . $imgData['image_directory'] . '/' . $imgData['image_masked_file_name'] );
			@copy( $this->settings['gallery_images_path'] . '/' . $imgData['image_directory'] . '/' . $imgData['image_original_file_name'], $this->settings['gallery_images_path'] . '/' . $imgData['image_directory'] . '/' . $imgData['image_masked_file_name'] );
		}
		
		//-----------------------------------------
		// Rebuild thumbnails and return
		//-----------------------------------------

		$this->buildSizedCopies( $imgData );

		return true;
	}

	/**
	 * Fetch images: Central function.
	 *
	 * @param	array		Array of 'where' information
	 * @param	array		Array of 'filter' information
	 * @return	@e array	Array of 'IDs'
	 */
	private function _fetchImages( $where, $filters )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$images	= array();
		
		//-----------------------------------------
		// Clean filters if necessary
		//-----------------------------------------

		if ( ! isset( $filters['_cleaned'] ) )
		{
			$filters	= $this->_setFilters( $filters );
		}
		
		//-----------------------------------------
		// Date cutoff?
		//-----------------------------------------

		if ( $filters['unixCutOff'] )
		{
			$where[]	= "i.image_date > " . $filters['unixCutOff'];
		}
		
		//-----------------------------------------
		// Limiting to an album?  
		// Note that albumId can validly be set to 0
		//-----------------------------------------

		if ( isset( $filters['albumId'] ) AND $filters['albumId'] !== null )
		{
			$where[]	= ( is_numeric( $filters['albumId'] ) ) ? "i.image_album_id=" . $filters['albumId'] : "i.image_album_id IN(" . implode( ",", $filters['albumId'] ) . ')';
		}

		//-----------------------------------------
		// Limiting to a category?  
		//-----------------------------------------

		if ( isset( $filters['categoryId'] ) )
		{
			$where[]	= ( is_numeric( $filters['categoryId'] ) ) ? "i.image_category_id=" . $filters['categoryId'] : "i.image_category_id IN(" . implode( ",", $filters['categoryId'] ) . ')';
		}
		
		//-----------------------------------------
		// Limiting to a specific owner?  
		//-----------------------------------------

		if ( ! empty( $filters['ownerId'] ) )
		{
			$where[]	= 'i.image_member_id=' . intval( $filters['ownerId'] );
		}
		
		//-----------------------------------------
		// Limiting to featured images?  
		//-----------------------------------------

		if ( $filters['featured'] )
		{
			$where[]	= ( $filters['featured'] ) ? "i.image_feature_flag=1" : '';
		}
		
		//-----------------------------------------
		// Limiting to media or non-media?  
		//-----------------------------------------

		if ( isset( $filters['media'] ) )
		{
			$where[]	= ( $filters['media'] === true ) ? 'i.image_media=1' : 'i.image_media=0';
		}
		
		//-----------------------------------------
		// Looking for images with comments?  
		//-----------------------------------------

		if ( ! empty( $filters['hasComments'] ) )
		{
			$where[]	= 'i.image_last_comment > 0';
		}
		
		//-----------------------------------------
		// Exclude Media?
		//-----------------------------------------
		
		if ( ! empty( $filters['excludeMedia'] ) )
		{
			$where[]	= 'i.image_media = 0';
		}
		
		//-----------------------------------------
		// Build joins
		//-----------------------------------------

		$joins	= array(
						array(
							'select'	=> 'a.*',
 					  		'from'		=> array( 'gallery_albums' => 'a' ),
 					  	 	'where'		=> 'a.album_id=i.image_album_id',
 					  		'type'		=> 'left'
 					  		),
 					    array(
 					    	'select'	=> 'm.members_display_name, m.members_seo_name, m.member_id',
 					        'from'		=> array( 'members' => 'm' ),
 					        'where'		=> 'm.member_id=i.image_member_id',
 					        'type'		=> 'left'
 					        )
 					    );
		
		//-----------------------------------------
		// Retrieve latest comment?
		//-----------------------------------------

		if ( ! empty( $filters['getLatestComment'] ) AND empty( $filters['retrieveByComments'] ) )
		{	
			$joins[]	= array(
								'select' => 'c.*',
								'from'   => array( 'gallery_comments' => 'c' ),
								'where'  => 'c.comment_post_date=i.image_last_comment AND c.comment_img_id=i.image_id',
								'type'   => 'left'
								);
		}
		else if( !empty( $filters['retrieveByComments'] ) )
		{
			array_unshift( $joins, array(
								'select' => 'i.*',
								'from'   => array( 'gallery_images' => 'i' ),
								'where'  => 'c.comment_img_id=i.image_id',
								'type'   => 'left'
								) );
		}
		
		//-----------------------------------------
		// Retrieving tags?
		//-----------------------------------------

		if ( ! empty( $filters['getTags'] ) )
		{
			$joins[]	= $this->registry->galleryTags->getCacheJoin( array( 'meta_id_field' => 'i.image_id' ) );	
		}
		
		//-----------------------------------------
		// Retrieve a count?
		//-----------------------------------------

		if ( ! empty( $filters['getTotalCount'] ) AND empty( $filters['retrieveByComments'] ) )
		{
			$_joins	= array();
			
			//-----------------------------------------
			// Remove the selects 
			//-----------------------------------------

			foreach( $joins as $id => $join )
			{
				$join['select']	= '';
				$_joins[]		= $join;	
			}

			$row	= $this->DB->buildAndFetch( array(
													'select'	=> 'count(*) as count',
													'from'		=> array( 'gallery_images' => 'i' ),
													'where'		=> ( count( $where ) ) ? implode( ' AND ', $where ) : '',
													'add_join'	=> $_joins
												)		);

			$this->_imageCount	= intval( $row['count'] );
		}
		else if ( ! empty( $filters['getTotalCount'] ) AND ! empty( $filters['retrieveByComments'] ) )
		{
			$_joins	= array();
			
			//-----------------------------------------
			// Remove the selects 
			//-----------------------------------------

			foreach( $joins as $id => $join )
			{
				$join['select']	= '';
				$_joins[]		= $join;	
			}

			$row	= $this->DB->buildAndFetch( array(
													'select'	=> 'count(*) as count',
													'from'		=> array( 'gallery_comments' => 'c' ),
													'where'		=> ( count( $where ) ) ? implode( ' AND ', $where ) : '',
													'add_join'	=> $_joins
												)		);

			$this->_imageCount	= intval( $row['count'] );
		}
		
		//-----------------------------------------
		// Fetch teh images
		//-----------------------------------------

		if( ! empty( $filters['retrieveByComments'] ) )
		{
			if( $filters['getLatestComment'] AND $filters['sortKey'] == 'i.image_date' )
			{
				$filters['sortKey']	= 'c.comment_post_date';
			}

			$this->DB->build( array(
										'select'	=> 'c.*',
										'from'		=> array( 'gallery_comments' => 'c' ),
										'where'		=> ( count( $where ) ) ? implode( ' AND ', $where ) : '',
										'order'		=> ( isset($filters['orderBy']) AND $filters['orderBy'] === false ) ? null : $filters['sortKey'] . ' ' . $filters['sortOrder'],
										'limit'		=> ( $filters['offset'] || $filters['limit'] ) ? array( $filters['offset'], $filters['limit'] ) : null,
										'add_join'	=> $joins
								)		);
		}
		else
		{
			$this->DB->build( array(
									'select'	=> 'i.*',
									'from'		=> array( 'gallery_images' => 'i' ),
									'where'		=> ( count( $where ) ) ? implode( ' AND ', $where ) : '',
									'order'		=> ( isset($filters['orderBy']) AND $filters['orderBy'] === false ) ? null : $filters['sortKey'] . ' ' . $filters['sortOrder'],
									'limit'		=> ( $filters['offset'] || $filters['limit'] ) ? array( $filters['offset'], $filters['limit'] ) : null,
									'add_join'	=> $joins
							)		);
		}

		$o = $this->DB->execute();	
		
		$commentAuthorIds	= array();
		$ownerIds			= array();

		//-----------------------------------------
		// Loop over results
		//-----------------------------------------

		while( $row = $this->DB->fetch( $o ) )
		{
			//-----------------------------------------
			// Grab member IDs we need
			//-----------------------------------------

			$ownerIds[ $row['member_id'] ]	= $row['member_id'];

			//-----------------------------------------
			// Grab some comment data
			//-----------------------------------------

			if ( isset( $row['comment_text'] ) )
			{
				$row['_commentShort']	= IPSText::truncate( IPSText::stripTags( IPSText::getTextClass('bbcode')->stripAllTags( IPSText::unconvertSmilies( $row['comment_text'] ) ) ), 200 );
				
				if ( ! empty( $row['comment_author_id'] ) )
				{
					$commentAuthorIds[ $row['comment_author_id'] ]	= $row['comment_author_id'];
				}
			}
			
			//-----------------------------------------
			// Format tags
			//-----------------------------------------

			if ( ! empty( $row['tag_cache_key'] ) )
			{
				$row['tags']	= $this->registry->galleryTags->formatCacheJoinData( $row );
			}

			//-----------------------------------------
			// Pull in category data if needed
			//-----------------------------------------

			if( !$row['image_album_id'] AND $row['image_category_id'] )
			{
				$row	= array_merge( $row, $this->registry->gallery->helper('categories')->fetchCategory( $row['image_category_id'] ) );
			}

			//-----------------------------------------
			// Sort pinned flag
			//-----------------------------------------

			$row['_image_pinned']	= $filters['honorPinned'] ? $row['image_pinned'] : 0;

			//-----------------------------------------
			// Format image and store
			//-----------------------------------------

			$row	= $this->_setUpImage( $row, $filters['parseDescription'], $filters['thumbClass'], $filters['link-thumbClass'] );

			$images[ ( ! empty( $filters['retrieveByComments'] ) ? $row['comment_id'] : $row['image_id'] ) ]	= $row;		
		}

		//-----------------------------------------
		// Parse the image owner?
		//-----------------------------------------

		if ( ! empty( $filters['parseImageOwner'] ) )
		{
			if ( count( $ownerIds ) )
			{
				$mems	= IPSMember::load( $ownerIds, 'all' );
				
				foreach( $images as $id => $r )
				{
					if ( ! empty( $r['member_id'] ) AND isset( $mems[ $r['member_id'] ] ) )
					{
						$mems[ $r['member_id'] ]['m_posts']	= $mems[ $r['member_id'] ]['posts'];
						
						$_mem	= IPSMember::buildDisplayData( $mems[ $r['member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );

						$images[ $id ]	= array_merge( $images[ $id ], $_mem );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Grab comment authors?
		//-----------------------------------------

		if ( count( $commentAuthorIds ) )
		{
			$members	= IPSMember::load( $commentAuthorIds, 'all' );
			
			foreach( $images as $id => $data )
			{
				if ( ! empty( $data['comment_author_id'] ) )
				{
					if ( $members[ $data['comment_author_id'] ] )
					{
						$images[ $id ]['_commentAuthor']	= IPSMember::buildProfilePhoto( $members[ $data['comment_author_id'] ] );
					}
				}
			}
		}

		//-----------------------------------------
		// Return the images
		//-----------------------------------------

		return $images;
	}
	
	/**
	 * Set filters
	 * Takes user input and cleans it up a bit
	 *
	 * @access	private
	 * @param	array		Incoming filters
	 * @return	@e array
	 */
	private function _setFilters( $filters )
	{
		//-----------------------------------------
		// Check filter basics
		//-----------------------------------------

		if( $filters['_cleaned'] )
		{
			return $filters;
		}

		$filters['sortOrder']			= ( isset( $filters['sortOrder'] ) )	? strtolower( $filters['sortOrder'] )	: '';
		$filters['sortKey']				= ( isset( $filters['sortKey'] ) )		? $filters['sortKey']					: '';
		$filters['featured']			= ( isset( $filters['featured'] ) )		? $filters['featured']					: '';
		$filters['offset']				= ( isset( $filters['offset'] ) )		? intval( $filters['offset'] )			: 0;
		$filters['limit']				= ( isset( $filters['limit'] ) )		? intval( $filters['limit'] )			: 0;
		$filters['unixCutOff']			= intval( $filters['unixCutOff'] );
		$filters['albumId']				= ( isset( $filters['albumId'] ) && is_numeric( $filters['albumId'] ) ) ? intval( $filters['albumId'] ) : $filters['albumId'];
		$filters['categoryId']			= ( isset( $filters['categoryId'] ) && is_numeric( $filters['categoryId'] ) ) ? intval( $filters['categoryId'] ) : $filters['categoryId'];
		$filters['parseDescription']	= ( isset( $filters['parseDescription'] ) ) ? $filters['parseDescription'] : false;
		$filters['excludeMedia']		= ( isset( $filters['excludeMedia'] ) ) ? $filters['excludeMedia'] : false;
		
		//-----------------------------------------
		// Clean up sortKey
		//-----------------------------------------

		switch( $filters['sortKey'] )
		{
			default:
			case 'idate':
			case 'date':
			case 'time':
			case 'image_date':
				$filters['sortKey']		= 'i.image_date';
			break;
			case 'name':
			case 'caption':
			case 'caption_seo':
			case 'image_caption':
				$filters['sortKey']		= 'i.image_caption';
			break;
			case 'file_name':
				$filters['sortKey']		= 'i.image_file_name';
			break;
			case 'size':
			case 'file_size':
			case 'image_size':
				$filters['sortKey']		= 'i.image_file_size';
			break;
			case 'rand':
			case 'random':
				$filters['sortKey']		= $this->DB->buildRandomOrder();
			break;
			case 'lastcomment':
			case 'commentdate':
				$filters['sortKey']		= 'i.image_last_comment';
			break;
			case 'views':
			case 'image_views':
				$filters['sortKey']		= 'i.image_views';
			break;
			case 'comments':
			case 'image_comments':
				$filters['sortKey']		= 'i.image_comments';
			break;
			case 'rating':
			case 'image_rating':
				$filters['sortKey']		= 'i.image_rating';
			break;
		}

		if( $filters['honorPinned'] )
		{
			$filters['sortKey']	= "i.image_pinned DESC, " . $filters['sortKey'];
		}

		//-----------------------------------------
		// Clean up sortOrder
		//-----------------------------------------

		switch( $filters['sortOrder'] )
		{
			case 'desc':
			case 'descending':
			case 'z-a':
				$filters['sortOrder']	= 'desc';
			break;

			default:
			case 'asc':
			case 'ascending':
			case 'a-z':
				$filters['sortOrder']	= 'asc';
			break;
		}
		
		//-----------------------------------------
		// Set a flag so we don't do this again
		//-----------------------------------------

		$filters['_cleaned']   = true;
		
		return $filters;
	}
	
	/**
	 * Set up image data
	 *
	 * @param	array
	 * @param	bool		If we're loading lots of images in the same view and not displaying descriptions, pass false to save some resources
	 * @param	mixed		Null, or a CSS class to use for the image
	 * @param	mixed		Null, or a CSS class to use for the link
	 * @return	@e array
	 */
	protected function _setUpImage( $image, $parseDescription=TRUE, $thumbClass=null, $linkThumbClass=null )
	{
		//-----------------------------------------
		// Got the image data?
		//-----------------------------------------

		if ( ! is_array( $image ) || ! count( $image ) )
		{
			return array();
		}
		
		//-----------------------------------------
		// Auto-fix missing FURL markers
		//-----------------------------------------

		if ( !$image['image_caption_seo'] && is_numeric($image['image_id']) && $image['image_id'] && $image['image_caption'] )
		{
			$image['image_caption_seo']	= IPSText::makeSeoTitle( $image['image_caption'] );
			
			$this->DB->update( 'gallery_images', array( 'image_caption_seo' => $image['image_caption_seo'] ), 'image_id=' . $image['image_id'] );
		}

		//-----------------------------------------
		// Fix guest name
		//-----------------------------------------

		if ( empty($image['image_member_id']) || empty($image['members_display_name']) )
		{
			$image['members_display_name']	= $this->lang->words['global_guestname'];
		}

		//-----------------------------------------
		// Have we viewed image?
		//-----------------------------------------

		$image['_isRead']	= ( !isset($image['_isRead']) && $this->registry->isClassLoaded('classItemMarking') ) ? $this->registry->classItemMarking->isRead( array( 'albumID' => $image['image_album_id'], 'categoryID' => $image['image_category_id'], 'itemID' => $image['image_id'], 'itemLastUpdate' => $image['image_last_comment'] ? $image['image_last_comment'] : $image['image_date'] ), 'gallery' ) : $image['_isRead'];

		//-----------------------------------------
		// Extract image data
		//-----------------------------------------

		if ( IPSLib::isSerialized( $image['image_data'] ) )
		{
			$image['_data'] = unserialize( $image['image_data'] );
		}

		//-----------------------------------------
		// Check view style
		//-----------------------------------------

		static $hasBeenSet = false;

		if ( !$hasBeenSet AND isset( $this->request['view_style'] ) && in_array( $this->request['view_style'], array( 'thumbs', 'large' ) ) )
		{
			$hasBeenSet	= true;

			IPSCookie::set( 'gallery_view_size', $this->request['view_style'], 1 );
		}

		//-----------------------------------------
		// Build thumbnail
		//-----------------------------------------

		$opts				= array( 'type' => 'thumb', 'link-type' => 'page' );

		if( $thumbClass )
		{
			$opts['thumbClass']	= $thumbClass;
		}

		if( $linkThumbClass )
		{
			$opts['link-thumbClass']	= $linkThumbClass;
		}

		$image['thumb']		= $this->makeImageLink( $image, $opts );

		//-----------------------------------------
		// Build medium thumbnail
		//-----------------------------------------

		$opts				= array( 'type' => 'medium', 'link-type' => 'page' );

		if( $linkThumbClass )
		{
			$opts['link-thumbClass']	= $linkThumbClass;
		}

		$image['mediumThumb']	= $this->makeImageLink( $image, $opts );
	
		//-----------------------------------------
		// Parse bbcode
		//-----------------------------------------

		if ( $parseDescription AND ! empty( $image['image_description'] ) AND class_exists('ipsCommand') )
		{
			$image['_descriptionParsed']	= IPSText::getTextClass('bbcode')->preDisplayParse( $image['image_description'] );
		}

		//-----------------------------------------
		// Return image data
		//-----------------------------------------

		return $image;
	}

	/**
	 * Unpack notes
	 *
	 * @param	array		Image data
	 * @return	@e array	Image data with notes unpacked
	 */
	public function unpackNotes( $image )
	{
		$image['image_notes']	= unserialize( $image['image_notes'] );
		$image['image_notes']	= is_array( $image['image_notes'] ) && count( $image['image_notes'] ) ? $image['image_notes'] : array();
		
		foreach( $image['image_notes'] as $k => $v )
		{
			$image['image_notes'][ $k ]['note']	= IPSText::getTextClass('bbcode')->stripBadWords( $v['note'] );
		}
		
		$tmp						= array_keys( $image['image_notes'] );
		$image['_last_image_note']	= array_pop( $tmp );

		return $image;
	}
}