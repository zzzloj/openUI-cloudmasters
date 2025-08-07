<?php
/**
 * @file		albums.php 	IP.Gallery album library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2013-02-15 12:08:49 -0500 (Fri, 15 Feb 2013) $
 * @version		v5.0.5
 * $Revision: 11996 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_albums
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
	 * Number of albums
	 * 
	 * @var		int
	 */
	private $_albumCount	= 0;
	
	/**
	 * Flag to indicate if there are more albums
	 * 
	 * @var		bool
	 */
	private $_hasMore		= false;
	
	/**
	 * Constructor
	 *
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{ 
		//-----------------------------------------
		// Set registry objects
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
		// Reset permissions
		//-----------------------------------------

		$this->registry->permissions->setMemberData( $this->memberData );
	}
	
	/**
	 * Returns the album count from a query which asks for it.
	 *
	 * @return	@e int
	 */
	public function getCount()
	{
		return $this->_albumCount;
	}
	
	/**
	 * Returns the designated member's album
	 * 
	 * @return @e int
	 */
	public function getMembersAlbumId()
	{
		return intval( $this->settings['gallery_members_album'] );
	}
	
	/**
	 * Returns if there were more rows matched. Set in _fetchAlbums
	 *
	 * @return	@e bool
	 */
	public function hasMore()
	{
		return $this->_hasMore;
	}	
	
	/**
	 * Save updated album information
	 *
	 * @param	array	array( id => array( ... ) )
	 * @return	@e boolean
	 */
	public function save( array $array )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albums				= array();
		$return				= true;
		$categoryChanged	= false;
		
		if ( isset( $array['album_id'] ) )
		{
			$array = array( $array['album_id'] => $array );	
		}

		//-----------------------------------------
		// Loop through albums
		//-----------------------------------------

		foreach( $array as $id => $data )
		{
			if ( $id )
			{
				if ( count( $data ) )
				{
					$id	= intval($id);

					if ( ! empty( $data['album_name'] ) )
					{
						$data['album_name_seo'] = IPSText::makeSeoTitle( $data['album_name'] );
					}
					
					//-----------------------------------------
					// Check the parent
					//-----------------------------------------

					if ( isset( $data['album_category_id'] ) )
					{
						$_category	= $this->registry->gallery->helper('categories')->fetchCategory( $data['album_category_id'] );
						
						if ( $_category['category_type'] == 1 )
						{
							$categoryChanged	= true;
						}
						else
						{
							unset($data['album_category_id']);
						}
					}
					
					//-----------------------------------------
					// Save album
					//-----------------------------------------

					$this->DB->update( 'gallery_albums', $data, 'album_id=' . $id );
						
					//-----------------------------------------
					// Update permissions for images
					//-----------------------------------------

					$this->registry->gallery->helper('image')->updatePermissionFromParent( $id );

					$_imageUpdate	= array();

					if( $data['album_category_id'] )
					{
						$_imageUpdate['image_category_id']	= $data['album_category_id'];
					}

					if( $data['album_owner_id'] )
					{
						$_imageUpdate['image_member_id']	= $data['album_owner_id'];
					}

					if( count($_imageUpdate) )
					{
						$this->DB->update( 'gallery_images', $_imageUpdate, 'image_album_id=' . $id );
					}
						
					//-----------------------------------------
					// Resynchronize album
					//-----------------------------------------

					$this->resync( $id );
				}
			}
		}

		//-----------------------------------------
		// Resynchronize categories
		//-----------------------------------------

		if( $categoryChanged )
		{
			$this->registry->gallery->helper('categories')->rebuildCategory( 'all' );
		}
		
		return $return;
	}

	/**
	 * Remove album
	 *
	 * @param	mixed	$album			Album array or ID
	 * @param	mixed 	$moveToAlbum	Album array or ID to move images to, leave blank to delete images
	 * @param	mixed	$moveToCategory	Category ID or array to move images to, leave blank to delete images
	 * @return	@e boolean
	 */
	public function remove( $album, $moveToAlbum=array(), $moveToCategory=array() )
	{
		//-----------------------------------------
		// Fetch data and check it
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbum( $album, true );
		}
		
		if ( empty( $album['album_id'] ) )
		{
			return false;
		}
		
		//-----------------------------------------
		// Verify 'move to' data
		//-----------------------------------------

		if ( is_numeric($moveToAlbum) && $moveToAlbum )
		{
			$moveToAlbum	= $this->fetchAlbum( $moveToAlbum );
		}
		else if( is_numeric($moveToCategory) && $moveToCategory )
		{
			$moveToCategory	= $this->registry->gallery->helper('categories')->fetchCategory( $moveToCategory );
		}
		
		//-----------------------------------------
		// Fetch images
		//-----------------------------------------

		$images		= $this->registry->gallery->helper('image')->fetchAlbumImages( $album['album_id'], array( 'bypassModerationChecks' => true ) );
		
		//-----------------------------------------
		// Move or delete the images
		//-----------------------------------------

		if ( !empty( $moveToAlbum['album_id'] ) OR !empty( $moveToCategory['category_id'] ) )
		{
			if ( count( $images ) )
			{
				$result	= $this->registry->gallery->helper('moderate')->moveImages( array_keys( $images ), $moveToAlbum, $moveToCategory );
			}
		}
		else
		{
			$this->registry->gallery->helper('moderate')->deleteImages( $images );
		}
		
		//-----------------------------------------
		// Delete the album and other data
		//-----------------------------------------

		$this->DB->delete( 'gallery_albums', 'album_id=' . $album['album_id'] );
		$this->DB->delete( 'gallery_ratings', "rate_type='album' AND rate_type_id=" . $album['album_id'] );

        require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
        $_follow	= classes_like::bootstrap( 'gallery', 'albums' );
        $_follow->remove( $album['album_id'] );

		//-----------------------------------------
		// Check for a per-album directory
		//-----------------------------------------

		$dir	= $this->settings['gallery_images_path'] . '/gallery/album_' . $album['album_id'];

		if ( is_dir( $dir ) )
		{
			$files	= @scandir( $dir );

			//-----------------------------------------
			// Remove folder but only if it's empty.
			// Images are not always physically moved
			//-----------------------------------------

			if ( count( $files ) <= 2 )
			{
				@unlink( $dir );
			}
		}

		//-----------------------------------------
		// Rebuild category and other caches
		//-----------------------------------------

		$this->registry->gallery->helper('categories')->rebuildCategory( 'all' );
		$this->registry->gallery->rebuildStatsCache();
		$this->cache->rebuildCache( 'gallery_fattach', 'gallery' );
		$this->registry->gallery->resetHasGallery( $album['album_owner_id'] );

		//-----------------------------------------
		// And return
		//-----------------------------------------

		return true;
	}
	
	/**
	 * Fetch one or more albums by ID
	 *
	 * @param	mixed		INT = Album ID, array = many IDs
	 * @param	boolean		Force fresh load or not
	 * @return	@e array
	 */
	public function fetchAlbumsById( $id, $forceLoad=false )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$singleOrder	= is_numeric( $id ) ? true : false;
		$_returnData	= array();

		if ( ! is_numeric( $id ) AND ( ! is_array( $id ) OR ! count( $id ) ) )
		{
			return array();
		}

		//-----------------------------------------
		// Cache
		//-----------------------------------------

		static $albumsCache = array();

		if ( ! $forceLoad )
		{
			if ( $singleOrder )
			{
				//-----------------------------------------
				// Found it in cache?
				//-----------------------------------------

				if ( isset($albumsCache[ $id ]) )
				{
					return $albumsCache[ $id ];
				}
			}
			else
			{
				$_new_ids	= array();
				
				foreach( $id as $_ci )
				{
					//-----------------------------------------
					// Found in cache?
					//-----------------------------------------

					if ( isset($albumsCache[ $_ci ]) )
					{
						$_returnData[ $_ci ] = $albumsCache[ $_ci ];
					}
					else
					{
						//-----------------------------------------
						// Need to load this one
						//-----------------------------------------

						$_new_ids[] = $_ci;
					}
				}

				//-----------------------------------------
				// Need to load any, or do we have them all?
				//-----------------------------------------

				if ( count($_new_ids) )
				{
					$id = $_new_ids;
				}
				else
				{
					return $_returnData;
				}
			}
		}
		
		//-----------------------------------------
		// Format our joins
		//-----------------------------------------

		$joins  = array(
						array(
							'select'	=> 'i.*',
							'from'		=> array( 'gallery_images' => 'i' ),
							'where'		=> 'i.image_id=a.album_cover_img_id',
							'type'		=> 'left'
							),
						array(
							'select'	=> 'm.members_display_name, m.members_seo_name, m.member_id',
							'from'		=> array( 'members' => 'm' ),
							'where'		=> 'i.image_member_id=m.member_id',
							'type'		=> 'left'
							),
						array(
							'select'	=> 'mx.members_display_name as owners_members_display_name, mx.members_seo_name as owners_members_seo_name',
							'from'		=> array( 'members' => 'mx' ),
							'where'		=> 'a.album_owner_id=mx.member_id',
							'type'		=> 'left'
							)
						);
		
		//-----------------------------------------
		// Get rating join
		//-----------------------------------------

		$join = $this->registry->gallery->helper('rate')->getTableJoins( 'a.album_id', 'album', $this->memberData['member_id'] );
		
		if ( $join !== false && is_array( $join ) )
		{
			array_push( $joins, $join );
		}

		//-----------------------------------------
		// Get the album data
		//-----------------------------------------

		$this->DB->build( array(
								'select'	=> 'a.*',
								'from'		=> array( 'gallery_albums' => 'a' ),
								'where'		=> 'a.album_id ' . ( is_array($id) ? ( 'IN(' . implode( ',', $id ) . ')' ) : ( '=' . $id ) ),
								'add_join'	=> $joins,
						)		);
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$r['thumb']					= $this->registry->gallery->helper('image')->makeImageLink( $r, array( 'type' => 'thumb', 'coverImg' => true ) );
			$r['small']					= $r['thumb'];
			$albums[ $r['album_id'] ]	= $r;
		}

		//-----------------------------------------
		// Cache and return
		//-----------------------------------------

		if ( $singleOrder )
		{
			$albumsCache[ $id ]	= $this->_setUpAlbum( $albums[ $id ] );

			return $albumsCache[ $id ];
		}
		else
		{
			if ( !count($albums) )
			{
				return $_returnData;
			}
			
			foreach( $albums as $id => $album )
			{
				$albumsCache[ $id ]	= $this->_setUpAlbum( $album );
				$_returnData[ $id ]	= $albumsCache[ $id ];
			}
			
			return $_returnData;
		}
	}
	
	/**
	 * Gets all the albums for the specified member id
	 * 
	 * @param	integer		$memberId	Member ID
	 * @param	array 		$filters	Additional filters
	 * @return	@e mixed	Albums or false
	 */		
	public function fetchAlbumsByOwner( $memberId, $filters=array() )
	{
		//-----------------------------------------
		// Reset filters
		//-----------------------------------------

		$filters['album_owner_id']	= intval( $memberId );

		if ( ! isset( $filters['isViewable'] ) AND ! isset( $filters['isUploadable'] ) )
		{
			$filters['isViewable']	= 1;
		}

		//-----------------------------------------
		// Fetch and return
		//-----------------------------------------

		return $this->fetchAlbumsByFilters( $filters );
	}
	
	/**
	 * Fetches albums by filters
	 *
	 * Available options for $filters:
	 * album_id					- INT or ARRAY of album ids
	 * album_category_id		- INT or ARRAY of category ids
	 * sortKey					- DB row to sort on
	 * sortOrder				- ASC OR DESC
	 * isUploadable				- Returns only albums that you have permission to upload into
	 * isViewable				- Returns only albums that you have permission to view
	 * notEmpty					- Returns only albums with at least one image in them
	 * limit					- Return X matches (SQL limit)
	 * offset					- Offset by X (SQL offset)
	 * checkForMore				- Works in conjunction with LIMIT, check with (this)->hasMore() [boolean]
	 * unixCutOff				- Returns albums last updated > unixtime
	 * albumNameContains		- Returns albums whose name contains STRING
	 * album_owner_id			- Returns albums owned by supplied member ID
	 * getTotalCount			- BOOLEAN - performs a COUNT(*) first and stores in $this->getCount()
	 * skip						- INT or ARRAY of album_ids to skip
	 * getAlbumTableResultsOnly	- BOOLEAN - fetches results from gallery_albums only
	 * getFields				- Specify which fields to select (use a.* when getAlbumTableResultsOnly is TRUE, otherwise use plain table fields)
	 * moderatingData (array)	- array including 'action', 'owner_id', 'moderator', 'album_id'
	 * skipImageParsing			- Do not parse the image description
	 * 
	 * @param	array	Filters (...seeAbove...)
	 * @param	bool	Bypass permission checks and return album even if user can't access
	 * @return	@e mixed	Albums or false
	 */		
	public function fetchAlbumsByFilters( $filters=array(), $byPassPermissionChecks=false )
	{
		//-----------------------------------------
		// Set filters and INIT
		//-----------------------------------------

		$filters	= $this->_setFilters( $filters );
		$where		= array();
		
		//-----------------------------------------
		// Filtering by album ID?
		//-----------------------------------------

		if ( isset( $filters['album_id'] ) )
		{
			$_as		= ( ! is_array( $filters['album_id'] ) ) ? array( $filters['album_id'] ) : $filters['album_id'];
			
			$where[]	= "a.album_id IN (" . implode( ',', IPSLib::cleanIntArray( $_as ) ) . ")";

			unset($filters['album_id']);
		}
		
		//-----------------------------------------
		// Filtering by category ID?
		//-----------------------------------------

		if ( isset( $filters['album_category_id'] ) )
		{
			$_as		= ( ! is_array( $filters['album_category_id'] ) ) ? array( $filters['album_category_id'] ) : $filters['album_category_id'];
			
			$where[]	= "a.album_category_id IN (" . implode( ',', IPSLib::cleanIntArray( $_as ) ) . ")";

			unset($filters['album_category_id']);
		}
		
		//-----------------------------------------
		// Route to central processing function (aka CPF ;))
		//-----------------------------------------

		return $this->_fetchAlbums( $where, $filters, $byPassPermissionChecks );
	}

	/**
	 * Fetch a single album by ID
	 *
	 * @param	integer		$id			Album ID
	 * @param	bool		[$bypass]	Bypass permissions
	 * @return	@e array
	 */
	public function fetchAlbum( $albumId, $bypass=false )
	{
		//-----------------------------------------
		// Fetch the album
		//-----------------------------------------

		$albumId	= intval($albumId);
		$album		= $this->fetchAlbumsById( $albumId );
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		if ( ! $bypass )
		{
			//-----------------------------------------
			// Found the album?
			//-----------------------------------------

			if ( ! $album['album_id'] )
			{
				$this->registry->output->showError( 'gallery_404', 107141.1, null, null, 404 );
			}

			//-----------------------------------------
			// Can we view (category permissions)?
			//-----------------------------------------

			if ( ! $this->isViewable( $album ) )
			{
				$this->registry->output->showError( 'no_permission', 107141, null, null, 403 );
			}

			//-----------------------------------------
			// Can we access (public, friend-only, etc.)?
			//-----------------------------------------

			if ( $this->isPublic( $album ) !== true )
			{
				if ( ! ( $this->isOwner( $album ) OR $this->canModerate( $album ) ) )
				{
					if ( $this->isFriends( $album ) )
					{
						if ( ! IPSMember::checkFriendStatus( $album['album_owner_id'], 0, true ) )
						{
							$this->registry->output->showError( 'no_permission', 107140, null, null, 403 );
						}
					}
					else
					{
						$this->registry->output->showError( 'no_permission', 107140.1, null, null, 403 );
					}
				}
			}
		}
		
		return $album;
	}
	
	/**
	 * Determines if the user owns the album
	 *
	 * @param	mixed	Either array (album array) or int (album id)
	 * @param	mixed	Either array (memberData array) or int (member id) or null (current member)
	 * @return	@e bool
	 */
	public function isOwner( $album, $member=null )
	{
		//-----------------------------------------
		// INIT and load any needed data
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbumsById( $album );
		}
		
		if ( $member !== null )
		{
			if ( is_numeric( $member ) )
			{
				$member = IPSMember::load( $member, 'core' );
			}
		}
		else
		{
			$member = $this->memberData;
		}

		//-----------------------------------------
		// Now check
		//-----------------------------------------

		if ( $member['member_id'] AND $album['album_id'] AND $album['album_owner_id'] )
		{
			if ( $member['member_id'] == $album['album_owner_id'] )
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Determines if the album is empty not
	 *
	 * @param	mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function isEmpty( $album )
	{
		//-----------------------------------------
		// Load album data if needed
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbumsById( $album );
		}

		//-----------------------------------------
		// Determine if it is empty or not
		//-----------------------------------------

		if ( is_array( $album ) )
		{
			return ( ( $album['album_count_imgs'] + $album['album_count_imgs_hidden'] ) < 1 ) ? true : false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Determines if the album is private
	 *
	 * @param	mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function isPrivate( $album )
	{
		//-----------------------------------------
		// Load album data if needed
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbumsById( $album );
		}

		//-----------------------------------------
		// Check if it's private
		//-----------------------------------------

		if ( is_array( $album ) )
		{
			return ( $album['album_type'] == 2 ) ? true : false;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Determines if the album is public
	 *
	 * @param	mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function isPublic( $album )
	{
		//-----------------------------------------
		// Load album data if needed
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbumsById( $album );
		}

		//-----------------------------------------
		// Check if it's public
		//-----------------------------------------

		if ( is_array( $album ) )
		{
			return ( $album['album_type'] == 1 ) ? true : false;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Determines if the album is friends only
	 *
	 * @param	mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function isFriends( $album )
	{
		//-----------------------------------------
		// Load album data if needed
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbumsById( $album );
		}

		//-----------------------------------------
		// Check if album is friend-only
		//-----------------------------------------

		if ( is_array( $album ) )
		{
			return ( $album['album_type'] == 3 ) ? true : false;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Determines if the album is uploadble by $this->memberData
	 *
	 * @param	mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function isUploadable( $album )
	{
		//-----------------------------------------
		// Can we upload at all?
		//-----------------------------------------

		if ( ! $this->registry->gallery->getCanUpload() )
		{
			return false;
		}

		//-----------------------------------------
		// Load album data if needed
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbumsById( $album );
		}
		
		//-----------------------------------------
		// Found album?
		//-----------------------------------------

		if ( ! $album['album_id'] )
		{
			return false;
		}
		
		//-----------------------------------------
		// Can we upload within this category?
		//-----------------------------------------

		if( !$this->registry->permissions->check( 'post', $this->registry->gallery->helper('categories')->fetchCategory( $album['album_category_id'] ) ) )
		{
			return false;
		}

		//-----------------------------------------
		// Are we the album owner?
		//-----------------------------------------

		if ( $this->isOwner( $album ) )
		{
			return true;
		}

		//-----------------------------------------
		// Guess we can't upload!
		//-----------------------------------------

		return false;
	}

	/**
	 * Determines if the album is viewable
	 *
	 * @param	mixed	Either array (album array) or int (album id)
	 * @param	boolean	Return true/false or throw output error
	 * @param	mixed	Either array (member data) or int (member id ) or null (memberData)
	 * @return	@e bool
	 */
	public function isViewable( $album, $inlineError=false, $member=null )
	{
		//-----------------------------------------
		// Figure out member
		//-----------------------------------------

		if ( is_numeric( $member ) )
		{
			$member	= IPSMember::load( $member, 'all' );
		}
		elseif ( ! is_array($member) OR ! isset($member['member_id']) )
		{
			$member	= $this->memberData;
		}

		//-----------------------------------------
		// Load album data if necessary
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbumsById( $album );
		}
		
		//-----------------------------------------
		// Did we find the album?
		//-----------------------------------------

		if ( ! $album['album_id'] )
		{
			if ( $inlineError )
			{
		  		$this->registry->output->showError( $this->lang->words['4_no_album'], 'class-albums-viewable-0', null, null, 404 );
			}
			else
			{
				return false;
			}
		}
		
		//-----------------------------------------
		// Can we moderate?
		//-----------------------------------------

		if ( $this->canModerate( $album, $member ) )
		{
			return true;
		}

		//-----------------------------------------
		// Can we view category?
		//-----------------------------------------

		if( !$this->registry->permissions->check( 'images', $this->registry->gallery->helper('categories')->fetchCategory( $album['album_category_id'] ) ) )
		{
			if ( $inlineError )
			{
				$this->registry->output->showError( 'no_permission', 107142.77, null, null, 403 );
			}
			else
			{
				return false;
			}
		}
		
		//-----------------------------------------
		// Is this our album, public, or friend-only and we're friends?
		//-----------------------------------------
		
		if ( $this->isOwner( $album, $member ) )
		{
			return true;
		}
		else if ( $this->isPublic( $album ) )
		{ 
			return true;
		}
		else if ( $this->isFriends( $album ) AND IPSMember::checkFriendStatus( $album['album_owner_id'], $member['member_id'], true ) )
		{
			return true;
		}

		//-----------------------------------------
		// If we're still here, we can't view
		//-----------------------------------------
		
		if ( $inlineError )
		{
			$this->registry->output->showError( 'no_permission', 10714.722, null, null, 403 );
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Rebuilds the album data (counts, last img, etc)
	 *
	 * @param	mixed	Either array (album array) or int (album id)
	 * @return	@e array
	 */
	public function resync( $album )
	{
		//-----------------------------------------
		// INIT and load album data if needed
		//-----------------------------------------

		$image		= array();
		$saveIds	= '';
		
		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbumsById( $album, true );
		}		
		
		//-----------------------------------------
		// Fetch approved counts
		//-----------------------------------------

		$stats	= $this->DB->buildAndFetch( array(
												'select'	=> 'COUNT(*) as images, SUM(image_comments) as comments, SUM(image_comments_queued) as comments_queued',
												'from'		=> 'gallery_images',
												'where'		=> "image_album_id=" . $album['album_id'] . " AND image_approved=1"
										)		);
		
		//-----------------------------------------
		// Fetch unapproved counts
		//-----------------------------------------

		$modque	= $this->DB->buildAndFetch( array(
												'select'	=> 'COUNT(*) as modimages, SUM(image_comments_queued) as comments_queued',
												'from'		=> 'gallery_images',
												'where'		=> "image_album_id=" . $album['album_id'] . " AND image_approved=0"
										)		);
												   

		//-----------------------------------------
		// Latest image data
		//-----------------------------------------

		$last	= array();
		$lastx	= array();

		$this->DB->build( array(
								'select'	=> 'image_id, image_date',
								'from'		=> 'gallery_images',
								'where'		=> "image_album_id=" . $album['album_id'] . " AND image_approved=1",
								'order'		=> 'image_date DESC',
								'limit'		=> array( 11 )
						)		);
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			if( !$last['image_id'] )
			{
				$last	= $r;
			}
			else
			{
				$lastx[]	= $r['image_id'];
			}
		}
		
		//-----------------------------------------
		// Verify cover image is still valid
		//-----------------------------------------

		if ( $album['album_cover_img_id'] )
		{
			$img	= $this->registry->gallery->helper('image')->fetchImage( $album['album_cover_img_id'], true, false );
			
			if ( ! $img['image_id'] OR ( $img['image_album_id'] != $album['album_id'] ) )
			{
				$album['album_cover_img_id'] = 0;
			}
		}
		
		//-----------------------------------------
		// If no cover image specified, use latest image
		//-----------------------------------------

		if ( ! $album['album_cover_img_id'] )
		{
			$_i		= $this->registry->gallery->helper('image')->fetchImages( 0, array( 'albumId' => $album['album_id'], 'limit' => 1, 'sortKey' => $album['album_sort_options__key'], 'sortOrder' => $album['album_sort_options__dir'] ), GALLERY_IMAGE_BYPASS_PERMS );
			$image	= array_shift( $_i );
			
			if ( ! isset( $image['image_id'] ) )
			{
				$image['image_id']	= 0;
			}
		}
		
		//-----------------------------------------
		// Format the save array
		//-----------------------------------------

		$save	= array(
						'album_count_imgs'				=> intval( $stats['images'] ),
						'album_count_comments'			=> intval( $stats['comments'] ),
						'album_count_imgs_hidden'		=> intval( $modque['modimages'] ),
						'album_count_comments_hidden'	=> intval( $stats['comments_queued'] ) + intval( $modque['comments_queued'] ),
						'album_name_seo'				=> ( $album['album_name_seo'] ) ? $album['album_name_seo'] : IPSText::makeSeoTitle( $album['album_name'] ),
						'album_last_img_id'				=> intval( $last['image_id'] ),
						'album_cover_img_id'			=> $album['album_cover_img_id'] ? $album['album_cover_img_id'] : intval( $image['image_id'] ),
						'album_last_img_date'			=> $last['image_date'] ? $last['image_date'] : 0,
						'album_last_x_images'			=> serialize( $lastx ),
						);

		//-----------------------------------------
		// Save, update caches and return
		//-----------------------------------------

		$this->DB->update( 'gallery_albums', $save, 'album_id=' . $album['album_id'] );

		$this->registry->gallery->helper('categories')->rebuildCategory( $album['album_category_id'] );

	 	return $save;
	}

	/**
	 * Can create a new album?
	 * That is the question we ask and we expect a reply
	 * 
	 * @param	mixed		$memberId		Either member ID, memberData or null (will use $this->memberData)
	 * @param	boolean		$checkLimit		Checks if the member has already more albums than the limit (returns false if limit is hit)
	 * @param	int			$type			If 0, checks if user can create any type of album.  Otherwise pass 1 for public, 2 for private and 3 for friend-only.
	 * @param	int			$categoryId		If supplied, verifies we can create albums in this category
	 * @return	@e boolean
	 */
	public function canCreate( $memberId=null, $checkLimit=true, $type=0, $categoryId=0 )
	{
		//-----------------------------------------
		// Get member
		//-----------------------------------------

		$memberData	= array();	

		if ( $memberId !== null )
		{
			if ( is_numeric( $memberId ) )
			{
				$memberData	= IPSMember::load( $memberId );
			}
			else
			{
				$memberData	= $memberId;
			}
		}
		else
		{
			$memberData	= $this->memberData;
		}

		if( !$memberData['member_id'] )
		{
			return false;
		}
		
		//-----------------------------------------
		// Check category
		//-----------------------------------------

		if( $categoryId )
		{
			$_category	= $this->registry->gallery->helper('categories')->fetchCategory( $categoryId );

			if( !$_category['category_id'] )
			{
				return false;
			}

			if( $_category['category_type'] != 1 )
			{
				return false;
			}

			if( !$this->registry->permissions->check( 'images', $_category ) OR !$this->registry->permissions->check( 'post', $_category ) )
			{
				return false;
			}
		}

		//-----------------------------------------
		// Check group permission
		//-----------------------------------------

		if( !$type )
		{
			if( !$memberData['g_create_albums'] AND !$memberData['g_create_albums_private'] AND !$member['g_create_albums_fo'] )
			{
				return false;
			}
		}
		else
		{
			switch( $type )
			{
				case 1:
					if( !$memberData['g_create_albums'] )
					{
						return false;
					}
				break;

				case 2:
					if( !$memberData['g_create_albums_private'] )
					{
						return false;
					}
				break;

				case 3:
					if( !$memberData['g_create_albums_fo'] )
					{
						return false;
					}
				break;
			}
		}
		
		//-----------------------------------------
		// Have we checked our group limit?
		//-----------------------------------------

		if( $checkLimit && $memberData['g_album_limit'] > 0 )
		{
		 	$total	= $this->DB->buildAndFetch( array(
		 											'select'	=> 'count(album_id) AS total',
		 											'from'		=> 'gallery_albums',
		 											'where'		=> "album_owner_id=" . intval( $memberData['member_id'] ) 
		 									)		);
			
		 	if( $total['total'] < $memberData['g_album_limit'] )
		 	{
		 		if( count($this->registry->gallery->helper('categories')->fetchAlbumCategories()) )
		 		{
					return true;
				}
		 	}
		}
		elseif( $memberData['g_album_limit'] == -1 )
		{
			if( count($this->registry->gallery->helper('categories')->fetchAlbumCategories()) )
			{
				return true;
			}
		}

		//-----------------------------------------
		// If we're still here, we did not pass the checks
		//-----------------------------------------

		return false;
	}

	/**
	 * Do we have any albums?
	 * 
	 * @param	mixed		$memberId		Either member ID, memberData or null (will use $this->memberData)
	 * @return	@e boolean
	 */
	public function hasAlbums( $memberId=null )
	{
		//-----------------------------------------
		// Get member
		//-----------------------------------------

		if ( $memberId !== null )
		{
			if ( is_array( $memberId ) )
			{
				$memberId	= $memberId['member_id'];
			}
		}
		else
		{
			$memberId	= $this->memberData['member_id'];
		}

		if( !$memberId )
		{
			return false;
		}

		$total	= $this->DB->buildAndFetch( array(
												'select'	=> 'count(album_id) AS total',
												'from'		=> 'gallery_albums',
												'where'		=> "album_owner_id=" . intval( $memberId ) 
										)		);

		//-----------------------------------------
		// If we're still here, we did not pass the checks
		//-----------------------------------------

		return ( $total['total'] ) ? true : false;
	}

	/**
	 * Force admin album presets on an album to be created
	 *
	 * @param	array 	Album data
	 * @return	@e array
	 */
	public function forceAdminPresets( $album )
	{
		//-----------------------------------------
		// Get presets
		//-----------------------------------------

		$presets	= $this->cache->getCache('gallery_album_defaults');

		//-----------------------------------------
		// Anything that can't be changed, set to default
		//-----------------------------------------

		if( !$presets['album_type_edit'] )
		{
			$album['album_type']			= $presets['album_type'];
		}

		if( !$presets['album_sort_edit'] )
		{
			$album['album_sort_options']	= $presets['album_sort_options'];
		}

		if( !$presets['album_watermark_edit'] )
		{
			$album['album_watermark']		= $presets['album_watermark'];
		}

		if( !$presets['album_comments_edit'] )
		{
			$album['album_allow_comments']	= $presets['album_allow_comments'];
		}

		if( !$presets['album_ratings_edit'] )
		{
			$album['album_allow_rating']	= $presets['album_allow_rating'];
		}

		//-----------------------------------------
		// Force public albums outside of members gallery
		//-----------------------------------------

		if( $album['album_category_id'] != $this->settings['gallery_members_album'] )
		{
			$album['album_type']	= 1;
		}

		//-----------------------------------------
		// Does category allow us to override?
		//-----------------------------------------

		$_category	= $this->registry->gallery->helper('categories')->fetchCategory( $album['album_category_id'] );

		if( !$_category['category_allow_comments'] )
		{
			$album['album_allow_comments']	= 0;
		}

		if( !$_category['category_allow_rating'] )
		{
			$album['album_allow_rating']	= 0;
		}

		if( $_category['category_watermark'] != 1 )
		{
			$album['album_watermark']	= $_category['category_watermark'] ? 1 : 0;
		}

		return $album;
	}
	
	/**
	 * Can delete an album
	 * 
	 * @param	mixed		Album ID or array
	 * @param	mixed		Either member ID, memberData or null (will use $this->memberData)
	 * @return	@e boolean
	 */
	public function canDelete( $album, $member=null )
	{
		//-----------------------------------------
		// Get member data
		//-----------------------------------------

		$memberData	= array();	

		if ( $memberId !== null )
		{
			if ( is_numeric( $memberId ) )
			{
				$memberData	= IPSMember::load( $memberId );
			}
			else
			{
				$memberData	= $memberId;
			}
		}
		else
		{
			$memberData	= $this->memberData;
		}
		
		if ( ! $memberData['member_id'] )
		{
			return false;
		}

		//-----------------------------------------
		// Load album data if needed
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbumsById( $album, true );
		}

		//-----------------------------------------
		// If we can moderate, we're good
		//-----------------------------------------

		if( $this->canModerate( $album, $member ) )
		{
			return true;
		}

		//-----------------------------------------
		// If we can delete our own, and we are the owner, we're good
		//-----------------------------------------

		if ( $this->isOwner( $album, $member ) AND $memberData['g_delete_own_albums']  )
		{
			return true;
		}

		//-----------------------------------------
		// Nope, we can't delete
		//-----------------------------------------

		return false;
	}
	
	/**
	 * Can edit an album
	 * 
	 * @param	mixed	Album ID or array
	 * @param	mixed	Either member ID, memberData or null (will use $this->memberData)
	 * @return	@e boolean
	 */
	public function canEdit( $album, $member=null )
	{
		//-----------------------------------------
		// Get member data
		//-----------------------------------------

		$memberData	= array();	

		if ( $memberId !== null )
		{
			if ( is_numeric( $memberId ) )
			{
				$memberData	= IPSMember::load( $memberId );
			}
			else
			{
				$memberData	= $memberId;
			}
		}
		else
		{
			$memberData	= $this->memberData;
		}
		
		if ( ! $memberData['member_id'] )
		{
			return false;
		}

		//-----------------------------------------
		// Load album data if needed
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbumsById( $album, true );
		}

		//-----------------------------------------
		// If we can moderate, we're good
		//-----------------------------------------

		if( $this->canModerate( $album, $member ) )
		{
			return true;
		}

		//-----------------------------------------
		// If we are the owner, we're good
		//-----------------------------------------

		if ( $this->isOwner( $album, $member )  )
		{
			return true;
		}

		//-----------------------------------------
		// Nope, we can't edit
		//-----------------------------------------

		return false;
	}
	
	/**
	 * Returns true if the logged in user can moderate
	 *
	 * @param	mixed		$album		Album ID or array
	 * @param	mixed		$member		Member data, if null it uses the current logged in member data
	 * @return	@e boolean
	 */
	public function canModerate( $album, $member=null )
	{
		//-----------------------------------------
		// Load album data if needed
		//-----------------------------------------

		if ( is_numeric( $album ) )
		{
			$album	= $this->fetchAlbumsById( $album, true );
		}

		//-----------------------------------------
		// Pass through
		//-----------------------------------------
		
		return $this->registry->gallery->helper('categories')->checkIsModerator( $album['album_category_id'], $memberData );
	}

	/**
	 * Returns true if the logged in user can edit the watermark option
	 *
	 * @param	mixed		$album		Album ID or array
	 * @return	@e bool
	 */
	public function canWatermark( $album )
	{
		//-----------------------------------------
		// Load album data if needed
		//-----------------------------------------

		if ( is_numeric($album) )
		{
			$album = $this->fetchAlbumsById( intval($album) );
		}
		
		//-----------------------------------------
		// Got an album?
		//-----------------------------------------

		if ( !$album['album_id'] )
		{
			return false;
		}

		//-----------------------------------------
		// Get category
		//-----------------------------------------

		$_category	= $this->registry->gallery->helper('categories')->fetchCategory( $album['album_category_id'] );
		
		//-----------------------------------------
		// Check category option
		//-----------------------------------------

		if( $_category['category_watermark'] == 1 )
		{
			return true;
		}

		return false;
	}

	/**
	 * Get drop down data for albums.
	 * 
	 * @param	int		Parent (0 by default)	$rootNode
	 * @param	array	Standard filters 		$filters
	 * @return	@e mixed	HTML or false
	 */
	public function getOptionTags( $rootNode=0, $filters=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$return			= array();
		
		if ( is_numeric( $rootNode ) and $rootNode > 0 )
		{
			$filters['album_category_id']	= $rootNode;
		}

		//-----------------------------------------
		// Get album options
		//-----------------------------------------

		$albums	= $this->fetchAlbumsByFilters( $filters );
		
		foreach( $albums as $id => $data )
		{
			$checked	= ( ! empty( $filters['selected'] ) && $filters['selected'] == $id ) ? ' selected="selected" ' : ''; 
			
			$return[]	= '<option value="' . $data['album_id'] . '"' . $checked . '>' . $data['album_name'] . '</option>';		
		}

		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return count( $return ) ? implode( "\n", $return ) : false;
	}

	/**
	 * Fetch albums: Central processing function (Matt's CPF)
	 *
	 * Custom filter attributes:
	 * isViewable: Only select items the viewer has permission to see
	 * isUploadable: Only select items the viewer has permission to upload into
	 * memberData: Pass data for the viewer else $this->memberData will be used
	 *
	 * @param	array		Array of 'where' information
	 * @param	array		Array of 'filter' information
	 * @param 	bool		Skip any permission checks
	 * @return	@e array	Array of 'IDs'
	 */
	private function _fetchAlbums( $where, $filters, $byPassPermissionChecks=false )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albums		= array();
		$joins		= null;
		$_q			= array();
		
		//-----------------------------------------
		// Clean filters
		//-----------------------------------------

		if ( ! isset( $filters['_cleaned'] ) )
		{
			$filters	= $this->_setFilters( $filters );
		}

		//-----------------------------------------
		// Skipping any albums?
		//-----------------------------------------

		if ( ! empty( $filters['skip'] ) )
		{
			if( is_array( $filters['skip'] ) )
			{
				$where[]	= "a.album_id NOT IN (" . implode( ', ', IPSLib::cleanIntArray( $filters['skip'] ) ) . ')';
			}
			else
			{
				$where[]	= "a.album_id <> " . intval($filters['skip']);
			}
		}
		
		//-----------------------------------------
		// Date cut-off?
		//-----------------------------------------

		if ( $filters['unixCutOff'] )
		{
			$where[]	= "a.album_last_img_date > " . intval( $filters['unixCutOff'] );
		}
		
		//-----------------------------------------
		// If we are skipping perm checks, remove filters
		//-----------------------------------------

		if ( $byPassPermissionChecks === true )
		{
			if ( isset( $filters['isViewable'] ) )
			{
				unset( $filters['isViewable'] );
			}
			
			if ( isset( $filters['isUploadable'] ) )
			{
				unset( $filters['isUploadable'] );
			}
		}
		
		//-----------------------------------------
		// Set permission filters
		//-----------------------------------------

		$_w	= $this->_processPermissionFilters( $filters );
		
		if ( $_w )
		{
			$where[]	= $_w;
		}

		//-----------------------------------------
		// Return only non-empty albums?
		//-----------------------------------------

		if( !empty( $filters['notEmpty'] ) )
		{
			$categories	= $this->registry->gallery->helper('categories')->fetchCategories();
			$_mod		= array();

			foreach( $categories as $cat )
			{
				if( $this->registry->gallery->helper('categories')->checkIsModerator( $cat['category_id'], null, 'mod_can_approve' ) )
				{
					$_mod[]	= $cat['category_id'];
				}
			}

			if( count($_mod) )
			{
				$where[]	= "(a.album_count_imgs > 0 OR (a.album_category_id IN(" . implode( ',', $_mod ) . ") AND a.album_count_imgs_hidden > 0))";
			}
			else
			{
				$where[]	= "a.album_count_imgs > 0";
			}
		}
		
		//-----------------------------------------
		// Name matching?
		//-----------------------------------------

		if ( !empty( $filters['albumNameContains'] ) )
		{
			$where[]	= $this->DB->buildLower( "a.album_name" ) . " LIKE '%" . $this->DB->addSlashes( strtolower($filters['albumNameContains']) ) . "%'";
		}
		else if ( !empty( $filters['albumNameIs'] ) )
		{
			$where[]	= $this->DB->buildLower( "a.album_name" ) . "='" . $this->DB->addSlashes( strtolower( $filters['albumNameIs'] ) ) . "'";
		}
		
		//-----------------------------------------
		// Owner name matching?
		//-----------------------------------------

		if ( isset( $filters['albumOwnerNameContains'] ) OR isset( $filters['albumOwnerNameIs'] ) )
		{
			$_w		= ( ! empty( $filters['albumOwnerNameIs'] ) ) ? "='" . $this->DB->addSlashes( strtolower( $filters['albumOwnerNameIs'] ) ) . "'" : "LIKE '%" . $this->DB->addSlashes( strtolower( $filters['albumOwnerNameContains'] ) ) . "%'";
			$_ids	= array();
			
			$this->DB->build( array( 'select' => 'member_id',
									 'from'   => 'members',
									 'where'  => 'members_l_display_name ' . $_w,
									 'limit'  => array(0, 250) ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$_ids[]	= $row['member_id'];
			}
			
			if ( count( $_ids ) )
			{
				$where[]	= 'a.album_owner_id IN (' . implode( ',', $_ids ) . ')';
			}
			else
			{
				return array();
			}
		} 
		
		//-----------------------------------------
		// Category ID filter?
		//-----------------------------------------

		if ( ! empty( $filters['parentId'] ) )
		{
			$where[]	= "a.album_category_id=" . intval( $filters['parentId'] );
		}
		else if( ! empty( $filters['album_category_id'] ) )
		{
			$where[]	= "a.album_category_id=" . intval( $filters['album_category_id'] );
		}
		
		//-----------------------------------------
		// Filter by album owner ID?
		//-----------------------------------------

		if ( ! empty( $filters['album_owner_id'] ) )
		{
			if ( ! is_array( $filters['album_owner_id'] ) )
			{
				$filters['album_owner_id'] = array( $filters['album_owner_id'] );
			}
			
			$where[]	= "a.album_owner_id IN (" . implode( ",", IPSLib::cleanIntArray( $filters['album_owner_id'] ) ) . ")";
		}

		//-----------------------------------------
		// Get our query where clause so far
		//-----------------------------------------

		$query	= ( is_array( $where ) AND count( $where ) ) ? implode( ' AND ', $where ) : '';
		
		//-----------------------------------------
		// Get joins
		//-----------------------------------------

		if ( empty( $filters['getAlbumTableResultsOnly'] ) )
		{
			$joins	= array(
							array(
								'select'	=> 'm.members_display_name, m.members_seo_name, m.member_id',
								'from'		=> array( 'members' => 'm' ),
								'where'		=> 'a.album_owner_id=m.member_id',
								'type'		=> 'left' ),
							array(
								'select'	=> 'mx.members_display_name as owners_members_display_name, mx.members_seo_name as owners_members_seo_name',
								'from'		=> array( 'members' => 'mx' ),
								'where'		=> 'a.album_owner_id=mx.member_id',
								'type'		=> 'left' ) );
			
			//-----------------------------------------
			// Get rating join
			//-----------------------------------------

			$join = $this->registry->gallery->helper('rate')->getTableJoins( 'a.album_id', 'album', $this->memberData['member_id'] );
			
			if ( $join !== false && is_array( $join ) )
			{
				array_push( $joins, $join );
			}

			//-----------------------------------------
			// Get latest or cover image join
			//-----------------------------------------

			if( !empty( $filters['getLatestCoverImage'] ) )
			{
				$joins[]	= array(
									'select'	=> 'i.*',
									'from'		=> array( 'gallery_images' => 'i' ),
									'where'		=> 'i.image_id=IF( a.album_cover_img_id > 0, a.album_cover_img_id, a.album_last_img_id )',
									'type'		=> 'left'
									);
			}
		}
		else
		{
			$joins	= array();
		}
									 
		//-----------------------------------------
		// Get a count?
		//-----------------------------------------

		if ( ! empty( $filters['getTotalCount'] ) )
		{
			$_joins	= array();
			
			//-----------------------------------------
			// Remove selects from joins
			//-----------------------------------------

			foreach( $joins as $id => $join )
			{
				$join['select']	= '';
				$_joins[]		= $join;	
			}
			
			$row	= $this->DB->buildAndFetch( array(
													'select'	=> 'count(*) as count',
													'from'		=> array( 'gallery_albums' => 'a' ),
													'where'		=> $query,
													'add_join'	=> $_joins
											)		);
			
			$this->_albumCount	= intval( $row['count'] );
		}
		
		//-----------------------------------------
		// Set select fields, if appropriate
		//-----------------------------------------

		$select	= ( !empty( $filters['getAlbumTableResultsOnly'] ) ) ? '*' : 'a.*';

		if ( ! empty( $filters['getFields'] ) && is_array( $filters['getFields'] ) && count( $filters['getFields'] ) )
		{
			if ( !empty( $filters['getAlbumTableResultsOnly'] ) )
			{
				$select	= preg_replace( '#^,a\.#', '', implode( ',a.', $filters['getFields'] ) );
			}
			else
			{
				$select	= implode( ',', $filters['getFields'] );
			}
		}

		//-----------------------------------------
		// Reset a couple vars
		//-----------------------------------------

		$seen			= 0;
		$this->_hasMore	= false;
		$ownerIds		= array();
		$imageIds		= array();

		//-----------------------------------------
		// Start building array to fetch albums
		//-----------------------------------------

		$queryPieces	= array(
								'select'	=> $select,
								'from'		=> $filters['getAlbumTableResultsOnly'] ? 'gallery_albums' : array( 'gallery_albums' => 'a' ),
								'where'		=> '',
								'order'		=> '',
								'add_join'	=> $joins,
								);

		if( $query )
		{
			$queryPieces['where']	= $filters['getAlbumTableResultsOnly'] ? str_replace( 'a.', '', $query ) : $query;
		}

		if ( ! empty( $filters['sortKey'] ) )
		{
			if( $filters['getAlbumTableResultsOnly'] )
			{
				$queryPieces['order']	= str_replace( 'a.', '', $filters['sortKey'] ) . ' ' . $filters['sortOrder'];
			}
			else
			{
				$queryPieces['order']	= $filters['sortKey'] . ' ' . $filters['sortOrder'];
			}
		}

		if ( ! empty( $filters['offset'] ) OR ! empty( $filters['limit'] ) )
		{
			//-----------------------------------------
			// Checking for more?
			//-----------------------------------------

			if ( $filters['checkForMore'] )
			{
				$filters['limit']++;
			}

			$queryPieces['limit']	= array( $filters['offset'], $filters['limit'] );
		}

		//-----------------------------------------
		// Run query and fetch results
		//-----------------------------------------

		$this->DB->build( $queryPieces );
		$outer	= $this->DB->execute();

		while( $_a = $this->DB->fetch( $outer ) )
		{
			//-----------------------------------------
			// Verify permissions
			//-----------------------------------------

			if ( $filters['isViewable'] && ! isset( $filters['moderatingData']['action'] ) )
			{
				if ( ! $this->isViewable( $_a ) )
				{
					continue;
				}
			}
			
			if ( $filters['isUploadable'] && ! isset( $filters['moderatingData']['action'] ) )
			{
				if ( ! $this->isUploadable( $_a ) )
				{
					continue;
				}
			}
			
			//-----------------------------------------
			// Checking if album is uploadable?
			//-----------------------------------------

			if ( $filters['addUploadableFlag'] )
			{
				$_a['_canUpload']	= ( $this->isUploadable( $_a ) ) ? true: false;
			}

			//-----------------------------------------
			// Checking if there are more results?
			//-----------------------------------------

			if ( $filters['checkForMore'] && $filters['limit'] )
			{
				$seen++;
				
				if ( $seen + 1 > $filters['limit'] )
				{
					$this->_hasMore	= true;
					continue;
				}
			}

			//-----------------------------------------
			// Item marking
			//-----------------------------------------

			$rtime					= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'albumID' => $_a['album_id'] ), 'gallery' );
			$_a['_hasUnread']		= ( $_a['album_last_img_date'] && $_a['album_last_img_date'] > $rtime ) ? 1 : 0;

			//-----------------------------------------
			// Grab image ids
			//-----------------------------------------

			$_a['_coverImage']	= $_a['album_cover_img_id'] ? $_a['album_cover_img_id'] : $_a['album_last_img_id'];
			$_a['_latestImage']	= $_a['album_last_img_id'];

			$imageIds[ $_a['_coverImage'] ]		= $_a['_coverImage'];
			$imageIds[ $_a['_latestImage'] ]	= $_a['_latestImage'];
			$_a['thumb']						= $this->registry->gallery->helper('image')->makeImageLink( array(), array( 'type' => 'thumb', 'coverImg' => true ) );
			$_a['small']						= $_a['thumb'];
			$_a['thumbUrl']						= $this->registry->gallery->helper('image')->makeNoPhotoTag( array( 'link-type' => 'src' ) );
			$_a['coverUrl']						= $_a['thumbUrl'];
			
			$ownerIds[]					= $_a['album_owner_id'];
			$albums[ $_a['album_id'] ]	= $this->_setUpAlbum( $_a );
		}

		//-----------------------------------------
		// Grab the images and parse into the albums array
		//-----------------------------------------

		if( count($imageIds) )
		{
			$_albumImages	= $this->registry->gallery->helper('image')->fetchImages( null, array( 'imageIds' => $imageIds, 'parseDescription' => $filters['skipImageParsing'] ? false : true ) );

			if( count($_albumImages) )
			{
				foreach( $albums as $_albumId => $_albumRow )
				{
					$albums[ $_albumId ]['_coverImage']		= $_albumRow['_coverImage'] ? $_albumImages[ $_albumRow['_coverImage'] ] : array();
					$albums[ $_albumId ]['_latestImage']	= $_albumRow['_latestImage'] ? $_albumImages[ $_albumRow['_latestImage'] ] : array();

					if( ( !is_array($albums[ $_albumId ]['_coverImage']) OR !count($albums[ $_albumId ]['_coverImage']) ) AND is_array($albums[ $_albumId ]['_latestImage']) AND count($albums[ $_albumId ]['_latestImage']) )
					{
						$albums[ $_albumId ]['_coverImage']			= $albums[ $_albumId ]['_latestImage'];
						$albums[ $_albumId ]['album_cover_img_id']	= 0;
					}

					if( $albums[ $_albumId ]['_coverImage'] )
					{
						$albums[ $_albumId ]['_coverImage']['_isRead']	= $_albumRow['_hasUnread'] ? false : true;
					}

					$albums[ $_albumId ]['thumb']			= $this->registry->gallery->helper('image')->makeImageLink( $albums[ $_albumId ]['_coverImage'], array( 'type' => 'thumb', 'coverImg' => true ) );
					$albums[ $_albumId ]['small']			= $this->registry->gallery->helper('image')->makeImageLink( $albums[ $_albumId ]['_coverImage'], array( 'type' => 'small', 'coverImg' => true ) );
					$albums[ $_albumId ]['coverUrl']		= $this->registry->gallery->helper('image')->makeImageTag( $albums[ $_albumId ]['_coverImage'], array( 'type' => 'medium', 'link-type' => 'src' ) );
					$albums[ $_albumId ]['_latestThumb']	= $this->registry->gallery->helper('image')->makeImageLink( $albums[ $_albumId ]['_latestImage'], array( 'type' => 'thumb', 'link-type' => 'page' ) );
				}
			}
		}

		//-----------------------------------------
		// Parsing the image owner?
		//-----------------------------------------

		if ( ! empty( $filters['parseAlbumOwner'] ) )
		{
			if ( count( $ownerIds ) )
			{
				$mems	= IPSMember::load( $ownerIds, 'all' );
				
				foreach( $albums as $id => $r )
				{
					if ( ! empty( $r['album_owner_id'] ) AND isset( $mems[ $r['album_owner_id'] ] ) )
					{
						$mems[ $r['album_owner_id'] ]['m_posts']	= $mems[ $r['album_owner_id'] ]['posts'];
						
						$_mem	= IPSMember::buildDisplayData( $mems[ $r['album_owner_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );

						$albums[ $id ]	= array_merge( $albums[ $id ], $_mem );
					}
				}
			}
		}
		
		return $albums;
	}
	
	/**
	 * Sets up album data as expected by external classes
	 *
	 * @param	array		Album
	 * @return	@e array
	 */
	private function _setUpAlbum( $album )
	{
		//-----------------------------------------
		// Got an album?
		//-----------------------------------------

		if ( ! is_array( $album ) )
		{
			return array();
		}
		
		//-----------------------------------------
		// Fix FURL name automatically if needed
		//-----------------------------------------

		if ( !$album['album_name_seo'] && $album['album_id'] && $album['album_name'] )
		{
			$album['album_name_seo']	= IPSText::makeSeoTitle( $album['album_name'] );
			
			$this->DB->update( 'gallery_albums', array( 'album_name_seo' => $album['album_name_seo'] ), 'album_id=' . $album['album_id'] );
		}

		//-----------------------------------------
		// Set some other vars
		//-----------------------------------------

		$album['selfSeoUrl']		= $this->registry->output->buildSEOUrl( "app=gallery&amp;album={$album['album_id']}", 'public', $album['album_name_seo'], 'viewalbum' );
		$album['_totalImages']		= intval( $album['album_count_imgs'] );
		$album['_totalComments']	= intval( $album['album_count_comments'] );
		
		if ( $this->canModerate( $album ) )
		{
			$album['_totalImages']		+= intval( $album['album_count_imgs_hidden'] );
			$album['_totalComments']	+= intval( $album['album_count_comments_hidden'] );
		}
		
		//-----------------------------------------
		// Album sort options
		//-----------------------------------------

		$album['album_sort_options__key']	= 'image_date';
		$album['album_sort_options__dir']	= 'asc';

		if ( IPSLib::isSerialized( $album['album_sort_options'] ) )
		{
			$order	= unserialize( $album['album_sort_options'] );
			
			$album['album_sort_options__key']	= empty( $order['key'] ) ? $album['album_sort_options__key'] : $order['key'];
			$album['album_sort_options__dir']	= empty( $order['dir'] ) ? $album['album_sort_options__dir'] : $order['dir'];
		}
		
		//-----------------------------------------
		// Got a rating?
		//-----------------------------------------

		if ( isset( $album['rate_rate'] ) AND isset( $album['rate_date'] ) )
		{
			$album['_youRated']	= $album['rate_rate'];
		}

		return $album;
	}
	
	/**
	 * Takes user input and cleans it up a bit
	 *
	 * @param	array		Incoming filters
	 * @return	@e array
	 */
	private function _setFilters( $filters )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$filters['sortOrder']		= ( isset( $filters['sortOrder'] ) )		? $filters['sortOrder']				: '';
		$filters['sortKey']			= ( isset( $filters['sortKey'] ) )			? $filters['sortKey']				: '';
		$filters['offset']			= ( isset( $filters['offset'] ) )			? intval($filters['offset'])		: 0;
		$filters['limit']			= ( isset( $filters['limit'] ) )			? intval($filters['limit'])			: 0;
		$filters['isUploadable']	= ( isset( $filters['isUploadable'] ) )		? $filters['isUploadable']			: '';
		$filters['isViewable']		= ( isset( $filters['isViewable'] ) )		? $filters['isViewable']			: '';
		$filters['unixCutOff']		= ( !empty( $filters['unixCutOff'] ) )		? intval( $filters['unixCutOff'] )	: 0;
		$filters['checkForMore']	= ( !empty( $filters['checkForMore'] ) )	? true								: false;
		$filters['coverImg']		= ( !empty( $filters['unixCutOff'] ) && $filters['coverImg'] == 'latest' )	? 'album_last_img_id'	: 'album_cover_img_id';

		//-----------------------------------------
		// Check the sort order
		//-----------------------------------------

		switch( $filters['sortOrder'] )
		{
			default:
			case 'desc':
			case 'descending':
			case 'DESC':
			case 'z-a':
				$filters['sortOrder']	= 'desc';
			break;

			case 'asc':
			case 'ascending':
			case 'a-z':
			case 'ASC':
				$filters['sortOrder']	= 'asc';
			break;
		}
		
		//-----------------------------------------
		// Check the sort key
		//-----------------------------------------

		switch( $filters['sortKey'] )
		{
			case 'album_position':
			case 'position':
				$filters['sortKey']	= 'a.album_position';
			break;

			case 'album_last_img_date':
			case 'date':
			case 'time':
			case 'idate':
			case 'image_date':
			case 'image_views':
				$filters['sortKey']	= 'a.album_last_img_date';
			break;

			case 'album_name':
			case 'name':
			case 'caption':
			case 'caption_seo':
				$filters['sortKey']	= 'a.album_name_seo';
			break;

			case 'album_count_total_imgs':
			case 'album_count_imgs':
			case 'images':
				$filters['sortKey']	= 'a.album_count_imgs';
			break;

			case 'album_count_comments':
			case 'comments':
				$filters['sortKey']	= 'a.album_count_comments';
			break;

			case 'album_rating_aggregate':
			case 'rating':
			case 'rated':
				$filters['sortKey']	= 'a.album_rating_aggregate ' . $filters['sortOrder'] . ', a.album_rating_count';
			break;

			case 'rand':
			case 'random':
				$filters['sortKey']	= $this->DB->buildRandomOrder();
			break;
		}
	
		//-----------------------------------------
		// Set a flag so we don't do this again and return
		//-----------------------------------------

		$filters['_cleaned']	= true;
		
		return $filters;
	}
	
	/**
	 * Process permission filters
	 *
	 * @param	array	$filters
	 * @return	@e string
	 */
	private function _processPermissionFilters( $filters )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$where			= '';
		$memberData		= isset( $filters['memberData']['member_id'] ) ? $filters['memberData'] : $this->memberData;
		$_q				= array();
		$_or			= '';
		$perm_id_array	= $this->member->perm_id_array;
		
		//-----------------------------------------
		// Clean filters if we haven't
		//-----------------------------------------

		if ( ! isset( $filters['_cleaned'] ) )
		{
			$filters	= $this->_setFilters( $filters );
		}

		//-----------------------------------------
		// Return if we are bypassing permission checks
		//-----------------------------------------

		if ( ! empty( $filters['bypassPermissionChecks'] ) )
		{
			return '';
		}

		//-----------------------------------------
		// If we are not checking for us, use guest permission set
		//-----------------------------------------

		if ( $memberData['member_id'] != $this->memberData['member_id'] )
		{
			$perm_id_array	= explode( ",", IPSText::cleanPermString( $this->caches[ $this->settings['guest_group'] ]['g_perm_id'] ) );
		}
		
		//-----------------------------------------
		// Make sure permissions are ok
		//-----------------------------------------

		foreach( $perm_id_array as $i => $v )
		{
			if ( empty( $v ) )
			{
				unset( $perm_id_array[ $i ] );
			}
		}

		//-----------------------------------------
		// Only viewable albums?
		//-----------------------------------------

		if ( $filters['isViewable'] )
		{
			if( !count($this->registry->gallery->helper('categories')->member_access['images']) )
			{
				return 'a.album_category_id=0';
			}

			$_q[]	= "a.album_category_id IN(" . implode( ',', $this->registry->gallery->helper('categories')->member_access['images'] ) . ")";

			//-----------------------------------------
			// If we can't moderate...
			//-----------------------------------------

			if ( ! $this->canModerate( 0, $memberData ) )
			{
				//-----------------------------------------
				// Get friends (limited to 300 for now)
				//-----------------------------------------

				if ( $memberData['member_id'] == $this->memberData['member_id'] AND is_array( $this->memberData['_cache']['friends'] ) AND count( $this->memberData['_cache']['friends'] ) )
				{
					$_or	= " OR a.album_owner_id IN(" . implode( ",", array_slice( array_keys( $this->memberData['_cache']['friends'] ), 0, 300 ) ) . ')';
				}

				$_q[] = "( a.album_type=1
								OR ( a.album_type=2 AND a.album_owner_id=" . intval( $memberData['member_id'] ) . ")
								OR ( a.album_type=3 AND ( a.album_owner_id=" . intval( $memberId ) . $_or . ") ) )";
			}
		}

		//-----------------------------------------
		// Only uploadable albums?
		//-----------------------------------------

		else if ( $filters['isUploadable'] )
		{
			if( !count($this->registry->gallery->helper('categories')->member_access['images']) )
			{
				return 'a.album_category_id=0';
			}

			$_q[]	= "a.album_category_id IN(" . implode( ',', $this->registry->gallery->helper('categories')->member_access['images'] ) . ")";

			if ( isset( $filters['moderatingData']['moderator'] ) AND $this->canModerate( 0, $filters['moderatingData']['moderator'] ) )
			{
				if ( $filters['moderatingData']['action'] != 'moveImages' && $filters['moderatingData']['owner_id'] )
				{
					$_q[]	= 'a.album_owner_id=' . intval($filters['moderatingData']['owner_id']);
				}
			}
			else
			{
				$_q[]	= 'a.album_owner_id=' . intval( $memberData['member_id'] );
			}
		}
		
		//-----------------------------------------
		// Create an 'or' SQL string
		//-----------------------------------------

		if ( count( $_q ) )
		{
			$where	= '( ' . implode( ' OR ', $_q ) . ' )';
		}

		//-----------------------------------------
		// Return SQL statement
		//-----------------------------------------

		return ( $where ) ? '( ' . $where . ' )' : '';
	}

	/**
	 * Extract latest images from an array of albums and load the images
	 *
	 * @param	array	Albums
	 * @return	@e array
	 */
	public function extractLatestImages( $albums )
	{
		//-----------------------------------------
		// Extract all of our image ids
		//-----------------------------------------

		$imageIds	= array();

		foreach( $albums as $album )
		{
			$_latestIds	= unserialize( $album['album_last_x_images'] );

			if( $album['album_cover_img_id'] != $album['album_last_img_id'] )
			{
				array_unshift( $_latestIds, $album['album_last_img_id'] );
			}

			if( is_array($_latestIds) AND count($_latestIds) )
			{
				$imageIds	= array_merge( $imageIds, $_latestIds );
			}
		}

		//-----------------------------------------
		// If we didn't find any, just return now
		//-----------------------------------------

		if( !count($imageIds) )
		{
			return $albums;
		}

		//-----------------------------------------
		// Load the images
		//-----------------------------------------

		$_images	= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array( 'imageIds' => $imageIds, 'parseImageOwner' => false, 'sortKey' => 'image_date', 'sortOrder' => 'desc' ) );

		if( count($_images) )
		{
			foreach( $albums as $key => $album )
			{
				$albums[ $key ]['_latest']	= array();

				$_latestIds	= unserialize( $album['album_last_x_images'] );

				if( $album['album_cover_img_id'] != $album['album_last_img_id'] )
				{
					array_unshift( $_latestIds, $album['album_last_img_id'] );
				}

				if( is_array($_latestIds) AND count($_latestIds) )
				{
					foreach( $_latestIds as $_latestId )
					{
						$_images[ $_latestId ]['thumb']		= $this->registry->gallery->helper('image')->makeImageLink( $_images[ $_latestId ], array( 'type' => 'thumb', 'link-type' => 'page' ) );
						$_images[ $_latestId ]['thumbUrl']	= $this->registry->gallery->helper('image')->makeImageTag( $_images[ $_latestId ], array( 'type' => 'thumb', 'link-type' => 'src' ) );
						$albums[ $key ]['_latest'][]		= $_images[ $_latestId ];
					}
				}
			}
		}

		return $albums;
	}
}