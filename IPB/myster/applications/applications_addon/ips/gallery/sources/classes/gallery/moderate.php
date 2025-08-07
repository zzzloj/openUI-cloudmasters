<?php
/**
 * @file		moderate.php 	IP.Gallery moderation library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2013-02-01 11:09:38 -0500 (Fri, 01 Feb 2013) $
 * @version		v5.0.5
 * $Revision: 11929 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_moderate
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
	 * @param	ipsRegistry  $registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
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
		// Get tags library
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}
	}	
	
	/**
	 * Adds album. Warning: Does not perform any permission checks.
	 * 
	 * @param	array		$album			Data for the new album
	 * @return	@e array	Album data
	 * 
	 * @throws
	 * 	@li	BAD_PARENT_CATEGORY:			An album always requires a category ID that accepts albums
	 */
	public function createAlbum( array $album )
	{
		//-----------------------------------------
		// Set some data
		//-----------------------------------------

		$album['album_category_id']		= intval( $album['album_category_id'] );
		$album['album_owner_id']		= ( isset( $album['album_owner_id'] ) AND $album['album_owner_id'] ) ? $album['album_owner_id'] : $this->memberData['member_id'];
		$album['album_name']			= empty($album['album_name']) ? ( empty($this->lang->words['untitled_album']) ? 'Untitled Album' : $this->lang->words['untitled_album'] ) : $album['album_name'];
		$album['album_name_seo']		= IPSText::makeSeoTitle( $album['album_name'] );
		$album['album_description']		= empty( $album['album_description'] ) ? '' : trim( $album['album_description'] );
		$album['album_type']			= intval( $album['album_type'] );
		$album['album_watermark']		= intval( $album['album_watermark'] );
		$album['album_allow_comments']	= ( isset( $album['album_allow_comments'] ) ) ? $album['album_allow_comments'] : 1;
		$album['album_allow_rating']	= ( isset( $album['album_allow_rating'] ) ) ? $album['album_allow_rating'] : 1;
		$album['album_position']		= intval( $album['album_position'] );
		$album['album_sort_options']	= ( isset( $album['album_sort_options'] ) AND $album['album_sort_options'] ) ? $album['album_sort_options'] : '';

		//-----------------------------------------
		// Verify album is ok in this category
		//-----------------------------------------

		if( !$album['album_category_id'] OR !in_array( $album['album_category_id'], $this->registry->gallery->helper('categories')->fetchAlbumCategories() ) )
		{
			throw new Exception( 'BAD_PARENT_CATEGORY' );
		}

		//-----------------------------------------
		// Insert and retrieve the new ID
		//-----------------------------------------

		$this->DB->insert( 'gallery_albums', $album );

		$album['album_id']	= $this->DB->getInsertId();

		//-----------------------------------------
		// Rebuild some caches
		//-----------------------------------------

		$this->registry->gallery->helper('categories')->rebuildCategory( $album['album_category_id'] );
		$this->registry->gallery->rebuildStatsCache();
		
		//-----------------------------------------
		// Reset the 'has_gallery' flag
		//-----------------------------------------

		$this->registry->gallery->resetHasGallery( $album['album_owner_id'] );
		
		//-----------------------------------------
		// Return the album
		//-----------------------------------------

		return $album;
	}

	/**
	 * Deletes picture(s)
	 * 
	 * @param	array 	Array of images: array( id => data )
	 * @return	@e bool
	 */	
	public function deleteImages( array $images=array() )
	{
		//-----------------------------------------
		// INIT and check data
		//-----------------------------------------

		$final		= array();
		$uploads	= array();
		$albums		= array();
		$categories	= array();
		$_names		= array();
		$_ids		= array();
		
		if ( ! count( $images ) )
		{
			return false;
		}
		
		//-----------------------------------------
		// Is this a simple array of ids?
		//-----------------------------------------

		if ( is_numeric( $images[0] ) AND ! is_array( $images[0] ) )
		{
			$images	= $this->registry->gallery->helper('image')->fetchImages( null, array( 'imageIds' => $images, 'orderBy' => false ) );
		}
		
		//-----------------------------------------
		// Fetch the like class
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like		= classes_like::bootstrap( 'gallery', 'images' );
		
		//-----------------------------------------
		// Loop through images to fetch data
		//-----------------------------------------

		foreach( $images as $id => $data )
		{
			if ( ! is_numeric( $data['image_id'] ) AND strlen( $data['image_id'] ) == 32 )
			{
				$uploads[ $id ] = $data;
			}
			else if ( is_numeric( $data['image_id'] ) )
			{
				$final[ $id ] = $data;
				
				if ( $data['image_album_id'] )
				{
					$albums[ $data['image_album_id'] ] = $data['image_album_id'];
				}
				else
				{
					$categories[ $data['image_category_id'] ]	= $data['image_category_id'];
				}
			}
		}
		
		//-----------------------------------------
		// Delete any 'true' images
		//-----------------------------------------

		if ( count( $final ) )
		{
			foreach( $final as $id => $data )
			{
				$_ids[]		= intval( $data['image_id'] );
				$_names[]	= "'" . $this->DB->addSlashes( $data['image_masked_file_name'] ) . "'";
				
				//-----------------------------------------
				// Delete the physical files
				//-----------------------------------------

				$this->removeImageFiles( $data );
			}

			//-----------------------------------------
			// Remove likes
			//-----------------------------------------

			$_like->remove( $_ids );
			
			//-----------------------------------------
			// Delete from database
			//-----------------------------------------

			$_idIn	= ' IN (' . implode( ',',  $_ids )  . ')';
			
			$this->DB->delete( 'gallery_comments' , "comment_img_id" . $_idIn );
			$this->DB->delete( 'gallery_bandwidth', "file_name IN (" . implode( ',', $_names ) . ")" );
			$this->DB->delete( 'gallery_ratings'  , "rate_type_id" . $_idIn . " AND rate_type='image'");
			$this->DB->delete( 'gallery_images'   , "image_id" . $_idIn );

			//-----------------------------------------
			// Grab comments and delete
			//-----------------------------------------

			$comments	= array();

			$this->DB->build( array( 'select' => 'comment_id, comment_img_id', 'from' => 'gallery_comments', 'where' => 'comment_img_id' . $_idIn ) );
			$this->DB->execute();

			while( $k = $this->DB->fetch() )
			{
				$comments[ $k['comment_img_id'] ][]	= $k['comment_id'];
			}

			if( count($comments) )
			{
				//-----------------------------------------
				// Fetch the comment class
				//-----------------------------------------

				require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
				$_comments	= classes_comments_bootstrap::controller( 'gallery-images' );

				foreach( $comments as $_commentImageId => $_commentIds )
				{
					try
					{
						$_comments->delete( $_commentImageId, $_commentIds );
					}
					catch( Exception $e ){}
				}
			}
			
			//-----------------------------------------
			// Delete tags
			//-----------------------------------------

			$this->registry->galleryTags->deleteByMetaId( $_ids );
		}
		
		//-----------------------------------------
		// Delete any temporary uploads
		//-----------------------------------------

		if ( count( $uploads ) )
		{
			$this->registry->gallery->helper('upload')->deleteSessionImages( $uploads );
		}
		
		//-----------------------------------------
		// Resync albums as necessary
		//-----------------------------------------

		if ( count( $albums ) )
		{
			foreach( $albums as $id )
			{
				$this->registry->gallery->helper('albums')->resync( $id );
			}
		}

		//-----------------------------------------
		// Resync categories as necessary
		//-----------------------------------------

		if ( count( $categories ) )
		{
			foreach( $categories as $id )
			{
				$this->registry->gallery->helper('categories')->rebuildCategory( $id );
			}
		}
		
		//-----------------------------------------
		// Rebuild stats cache
		//-----------------------------------------

		$this->registry->gallery->rebuildStatsCache();
		
		//-----------------------------------------
		// Log and return
		//-----------------------------------------

		if ( count($_ids) )
		{
			$this->addModLog( sprintf( $this->lang->words['modlog_delete_images'], implode( ',',  $_ids ) ) );
		}
		
		return true;
	}
	
	/**
	 * Toggle image visibility
	 * 
	 * @param	array	$images		Array of image ids
	 * @param	bool	$visible	True to approve the images, defaults to FALSE to hide
	 * @return	@e bool	True if update is successful
	 * 
	 * @throws
	 * 	@li	NO_IMAGES	No image IDs passed or image don't exist anymore
	 */	
	public function toggleVisibility( array $images=array(), $visible=false )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$imageIds		= IPSLib::cleanIntArray( $images );
		$imagesByAlbum	= array();
		$imagesByCat	= array();
		
		//-----------------------------------------
		// Fetch the images
		//-----------------------------------------

		$images			= $this->registry->gallery->helper('image')->fetchImages( null, array( 'imageIds' => $imageIds, 'orderBy' => false ) );
		
		if ( !count($images) )
		{
			throw new Exception('NO_IMAGES');
		}
		
		//-----------------------------------------
		// Get album and category IDs
		//-----------------------------------------

		foreach( $images as $id => $data )
		{
			if ( $data['image_album_id'] )
			{
				$imagesByAlbum[ $data['image_album_id'] ]	= $data['image_album_id'];
			}
			else
			{
				$imagesByCat[ $data['image_category_id'] ]	= $data['image_category_id'];
			}
		}
		
		//-----------------------------------------
		// Update the images
		//-----------------------------------------

		$_imagesIn	= implode( ', ', array_keys( $images ) );
		$this->DB->update( 'gallery_images', array( 'image_approved' => ( ( $visible == true ) ? 1 : -1 ) ), 'image_id IN (' . $_imagesIn . ')' );
		
		//-----------------------------------------
		// Update the tags
		//-----------------------------------------

		$this->registry->galleryTags->updateVisibilityByMetaId( array_keys( $images ), $visible );
		
		//-----------------------------------------
		// Rebuild albums and categories
		//-----------------------------------------

		foreach( $imagesByAlbum as $albumId )
		{
			$this->registry->gallery->helper('albums')->resync( $albumId );
		}

		foreach( $imagesByCat as $categoryId )
		{
			$this->registry->gallery->helper('categories')->rebuildCategory( $categoryId );
		}
		
		//-----------------------------------------
		// Rebuild stats cache
		//-----------------------------------------

		$this->registry->gallery->rebuildStatsCache();
		
		//-----------------------------------------
		// Log and return
		//-----------------------------------------

		$this->addModLog( sprintf( $visible === true ? $this->lang->words['modlog_approve_images'] : $this->lang->words['modlog_unapprove_images'], $_imagesIn ) );
		
		return true;
	}
	
	/**
	 * Move images to another album
	 * 
	 * @param	array		$imageIds		Image ids to move
	 * @param	mixed		$toAlbumId		Album ID or array to move images to
	 * @param	int			$toCategoryId	Category ID or array to move images to
	 * @return	@e bool	TRUE if all went fine
	 * 
	 * @throws
	 * 	@li	NO_CONTAINER	There was no album or category specified, or the one specified does not exist
	 * 	@li	NO_IMAGE		None of the image IDs passed exist
	 */
	public function moveImages( array $imageIds, $toAlbumId=0, $toCategoryId=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$imageIds		= IPSLib::cleanIntArray( $imageIds );
		$imagesByAlbum	= array();
		$imagesByCat	= array();
		$toAlbumData	= array();
		$toCategoryData	= array();
		
		//-----------------------------------------
		// Fetch the images
		//-----------------------------------------

		$images			= $this->registry->gallery->helper('image')->fetchImages( null, array( 'imageIds' => $imageIds, 'orderBy' => false ) );
		
		if ( !count($images) )
		{
			throw new Exception('NO_IMAGES');
		}
		
		//-----------------------------------------
		// If we are moving to an album, check it
		//-----------------------------------------

		if( $toAlbumId )
		{
			$toAlbumData	= ( is_array($toAlbumId) AND count($toAlbumId) ) ? $toAlbumId : $this->registry->gallery->helper('albums')->fetchAlbumsById( $toAlbumId );

			if ( empty( $toAlbumData['album_id'] ) )
			{
				throw new Exception('NO_CONTAINER');
			}
		}
		else if( $toCategoryId )
		{
			$toCategoryData	= ( is_array($toCategoryId) AND count($toCategoryId) ) ? $toCategoryId : $this->registry->gallery->helper('categories')->fetchCategory( $toCategoryId );

			if ( empty( $toCategoryData['category_id'] ) )
			{
				throw new Exception('NO_CONTAINER');
			}
		}
		else
		{
			throw new Exception('NO_CONTAINER');
		}
		
		//-----------------------------------------
		// Get album and category IDs
		//-----------------------------------------

		foreach( $images as $id => $data )
		{
			if ( $data['image_album_id'] )
			{
				$imagesByAlbum[ $data['image_album_id'] ]	= $data['image_album_id'];
			}
			else
			{
				$imagesByCat[ $data['image_category_id'] ]	= $data['image_category_id'];
			}
		}
		
		//-----------------------------------------
		// Update the images and tags
		//-----------------------------------------

		if( $toAlbumId )
		{
			$this->DB->update( 'gallery_images', array(
													'image_album_id'			=> $toAlbumData['album_id'],
													'image_category_id'			=> $toAlbumData['album_category_id'],
													'image_privacy'				=> $toAlbumData['album_type'],
													'image_parent_permission'	=> $this->registry->gallery->helper('categories')->fetchCategory( $toAlbumData['album_category_id'], 'perm_view' ) 
													), 'image_id IN (' . implode( ',', $imageIds ) . ')' );

			$this->registry->galleryTags->moveTagsToParentId( $imageIds, $toAlbumData['album_id'] );

			$this->registry->gallery->helper('albums')->resync( $toAlbumData['album_id'] );
		}
		else
		{
			$this->DB->update( 'gallery_images', array(
													'image_album_id'			=> 0,
													'image_category_id'			=> $toCategoryData['category_id'],
													'image_privacy'				=> 1,
													'image_parent_permission'	=> $toCategoryData['perm_view'] 
													), 'image_id IN (' . implode( ',', $imageIds ) . ')' );

			$this->registry->galleryTags->moveTagsToParentId( $imageIds, $toCategoryData['category_id'] );

			$this->registry->gallery->helper('categories')->rebuildCategory( $toCategoryData['category_id'] );
		}

		//-----------------------------------------
		// Mark as read in the new container
		//-----------------------------------------

		foreach( $imageIds as $_iid )
		{
			$this->registry->classItemMarking->markRead( array( 'albumID' => $toAlbumId, 'categoryID' => $toCategoryId, 'itemID' => $_iid ), 'gallery' );
		}

		//-----------------------------------------
		// Rebuild albums and categories
		//-----------------------------------------

		foreach( $imagesByAlbum as $albumId )
		{
			$this->registry->gallery->helper('albums')->resync( $albumId );
		}

		foreach( $imagesByCat as $categoryId )
		{
			$this->registry->gallery->helper('categories')->rebuildCategory( $categoryId );
		}
		
		//-----------------------------------------
		// Rebuild stats cache
		//-----------------------------------------

		$this->registry->gallery->rebuildStatsCache();
		
		//-----------------------------------------
		// Log and return
		//-----------------------------------------

		$this->addModLog( sprintf( $this->lang->words['modlog_move_images'], $toAlbumId ? $toAlbumData['album_name'] : $toCategoryData['category_name'], $toAlbumId ? $toAlbumData['album_id'] : $toCategoryData['category_id'], implode( ',', $imageIds ) ) );
		
		return true;
	}

	/**
	 * Removes the specified album and then deletes or moves the images
	 * 
	 * @param	integer		$album			Data of the album to delete
	 * @param	mixed		$moveToAlbum	Data or ID of the album to move images to (optional)
	 * @param	mixed		$moveToCat		Data or ID of the category to move images to (optional)
	 * @return	@e bool
	 */		
	public function deleteAlbum( $album, $moveToAlbum=null, $moveToCategory=null )
	{
		//-----------------------------------------
		// Delete the album
		//-----------------------------------------

		$return	= $this->registry->gallery->helper('albums')->remove( $album, $moveToAlbum, $moveToCategory );
		
		//-----------------------------------------
		// Log the action
		//-----------------------------------------

		if ( $return )
		{
			if ( is_numeric($moveToAlbum) && $moveToAlbum )
			{
				$moveToAlbum	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $moveToAlbum );
			}

			if ( is_numeric($moveToCategory) && $moveToCategory )
			{
				$moveToCategory	= $this->registry->gallery->helper('categories')->fetchCategory( $moveToCategory );
			}
			
			if ( $moveToAlbum['album_id'] )
			{
				$this->addModLog( sprintf( $this->lang->words['modlog_delete_album_move'], $album['album_name'], $album['album_id'], $moveToAlbum['album_name'], $moveToAlbum['album_id'] ) );
			}
			else if ( $moveToCategory['category_id'] )
			{
				$this->addModLog( sprintf( $this->lang->words['modlog_delete_album_move'], $album['album_name'], $album['album_id'], $moveToCategory['category_name'], $moveToCategory['category_id'] ) );
			}
			else
			{
				$this->addModLog( sprintf( $this->lang->words['modlog_delete_album'], $album['album_name'], $album['album_id'] ) );
			}
		}
		
		return $return;
	}
	
	/**
	 * Remove media thumb
	 * 
	 * @param	array	$image
	 * @return	@e boolean
	 */
	public function removeMediaThumb( $image )
	{
		//-----------------------------------------
		// Basic checks
		//-----------------------------------------

		if ( ! $image['image_id'] )
		{
			return false;
		}

		//-----------------------------------------
		// Save the image data
		//-----------------------------------------

		$this->registry->gallery->helper('image')->save( array( $image['image_id'] => array( 'image_medium_file_name' => '', 'image_media_thumb' => '', 'image_thumbnail' => 0 ) ) );
		
		//-----------------------------------------
		// Delete the file(s)
		//-----------------------------------------

		$dir	= $image['image_directory'] ? $image['image_directory'] . "/" : "";
		
		if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . $image['image_medium_file_name'] ) )
		{
			@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['image_medium_file_name'] );
		}	
		
		if ( $image['image_media_thumb'] )
		{
			if( is_file( $this->settings['gallery_images_path'] . '/' . $dir . $image['image_media_thumb'] ) )
			{
				@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['image_media_thumb'] );
			}
		}

		return true;
	}
	
	/**
	 * Remove image files from disk
	 *
	 * @param	array		$image				Image data
	 * @param	boolean		$checkDirectory		Checks if the album folder is empty and deletes it
	 * @return	@e boolean	False if the array is empty or there is no ID, otherwise true
	 */
	public function removeImageFiles( $image=array(), $checkDirectory=true )
	{
		//-----------------------------------------
		// Basic checks
		//-----------------------------------------

		if ( ! count($image) )
		{
			return false;
		}
		
		if ( ! $image['image_id'] )
		{
			return false;
		}

		//-----------------------------------------
		// Delete images
		//-----------------------------------------

		$dir	= $image['image_directory'] ? $image['image_directory'] . "/" : "";
		
		@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['image_masked_file_name'] );
		
		if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . 'tn_' . $image['image_masked_file_name'] ) )
		{
			@unlink( $this->settings['gallery_images_path'] . '/' . $dir . 'tn_' . $image['image_masked_file_name'] );
		}
		
		if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . 'sml_' . $image['image_masked_file_name'] ) )
		{
			@unlink( $this->settings['gallery_images_path'] . '/' . $dir . 'sml_' . $image['image_masked_file_name'] );
		}
		
		if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . $image['image_medium_file_name'] ) )
		{
			@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['image_medium_file_name'] );
		}
		
		if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . $image['image_original_file_name'] ) )
		{
			@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['image_original_file_name'] );
		}

		//-----------------------------------------
		// Delete media thumb
		//-----------------------------------------

		if ( $image['image_media_thumb'] )
		{
			if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . $image['image_media_thumb'] ) )
			{
				@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['image_media_thumb'] );
			}
		}

		//-----------------------------------------
		// Delete directory if it is empty
		//-----------------------------------------

		if ( $checkDirectory && $image['directory'] )
		{
			$files	= array();
			
			$dh		= @opendir(  $this->settings['gallery_images_path'] . '/' . $dir );
			
			if( $dh )
			{
				while( false !== ($file = readdir($dh))) 
				{
					if( $file != "." AND $file != ".." )
					{
						$files[] = $file;
					}
				}
				
				closedir($dh);
			}
			
			if ( ( count($files) == 1 AND $files[0] == "index.html" ) OR count($files) == 0 )
			{
				if( $files[0] )
				{
					@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $files[0] );
				}

				@rmdir( $this->settings['gallery_images_path'] . '/' . $dir );
			}
		}

		return true;
	}
	
	/**
	 * Add an entry to the moderator logs
	 *
	 * @param	string		$action
	 * @return	@e void
	 */
	public function addModLog( $action )
	{
		$this->DB->insert( 'moderator_logs', array( 'member_id'   => $this->memberData['member_id'],
													'member_name' => $this->memberData['members_display_name'],
													'ip_address'  => $this->member->ip_address,
													'http_referer'=> htmlspecialchars( my_getenv( 'HTTP_REFERER' ) ),
													'ctime'       => time(),
													'action'      => $action,
													'query_string'=> htmlspecialchars( my_getenv('QUERY_STRING') ),
												), true );
	}
}