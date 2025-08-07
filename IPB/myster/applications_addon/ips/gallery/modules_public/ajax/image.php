<?php
/**
 * @file		image.php 	AJAX image manipulation functions
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2013-05-21 20:51:17 -0400 (Tue, 21 May 2013) $
 * @version		v5.0.5
 * $Revision: 12263 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_ajax_image extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'fetchAjaxView':
				$this->returnAjaxView();
			break;

			case 'rotate-left':
				$this->rotateImage( 'left' );
			break;
			
			case 'rotate-right':
				$this->rotateImage( 'right' );
			break;
			
			case 'add-note':
				$this->addNote();
			break;
			
			case 'edit-note':
				$this->editNote();
			break;
			
			case 'remove-note':
				$this->removeNote();
			break;
			
			case 'fetchUploads':
				$this->_fetchUploads();
			break;

			case 'uploadRemove':
				$this->_removeUpload();
			break;

			case 'thumbRemove':
				$this->_removeThumbUpload();
			break;

			case 'addMap':
				$this->_addMap();
			break;

			case 'removeMap':
				$this->_removeMap();
			break;

			case 'moveDialogue':
				$this->_moveDialogue();
			break;

			case 'setAsPhoto':
				$this->_setAsPhoto();
			break;

			case 'setAsCoverOptions':
				$this->_getCoverOptions();
			break;
		}
	}

	/**
	 * Remove uploaded media thumb
	 *
	 * @return	@e void
	 */
	protected function _removeThumbUpload()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$type	= trim( $this->request['type'] );
		$id		= trim( $this->request['id'] );
		
		$image	= $this->registry->gallery->helper('image')->fetchImage( $id );
		
		//-----------------------------------------
		// Remove the media thumb
		//-----------------------------------------
		
		$return	= $this->registry->gallery->helper('moderate')->removeMediaThumb( $image );

		//-----------------------------------------
		// Return
		//-----------------------------------------

		if ( $return === false )
		{
			$this->returnJsonError( 'not_removed' );
		}
		else
		{
			$image['image_medium_file_name']	= '';
			$image['image_media_thumb']			= '';
			$image['tag']				= $this->registry->gallery->helper('media')->getThumb( $image );

			return $this->returnJsonArray( $image );
		}
	}

	/**
	 * Returns the AJAX view for an image
	 *
	 * @return	@e void
	 */
	protected function returnAjaxView()
	{
		$imageId	= intval( $this->request['imageId'] );

		//-----------------------------------------
		// Set up some objects we'll need
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
		$this->_comments = classes_comments_bootstrap::controller( 'gallery-images' );

		require_once( IPS_ROOT_PATH . 'sources/classes/mapping/bootstrap.php' );/*noLibHook*/
		$this->_mapping = classes_mapping::bootstrap( IPS_MAPPING_SERVICE );

		if ( $this->settings['reputation_enabled'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
			$this->registry->setClass( 'repCache', new $classToLoad() );
		}

		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}

		//-----------------------------------------
		// Set up some table joins for our query
		//-----------------------------------------

		$joins	= array(
					array(
						'select'	=> 'm.*',
						'from'		=> array( 'members' => 'm' ),
						'where'		=> 'm.member_id=i.image_member_id',
						'type'		=> 'left' 
						),
					array(
						'select'	=> 'pp.*',
						'from'		=> array( 'profile_portal' => 'pp'),
						'where'		=> 'pp.pp_member_id=i.image_member_id',
						'type'		=> 'left'
						)
					);

		$join	= $this->registry->gallery->helper('rate')->getTableJoins( 'i.image_id', 'image', $this->memberData['member_id'] );
		
		if ( $join !== false && is_array( $join ) )
		{
			array_push( $joins, $join );
		}
					
		if ( $this->settings['reputation_enabled'] )
		{
			$joins[]	= $this->registry->getClass('repCache')->getTotalRatingJoin('image_id', $imageId, 'gallery');
			$joins[]	= $this->registry->getClass('repCache')->getUserHasRatedJoin('image_id', $imageId, 'gallery');
		}
		
		$joins[]	= $this->registry->galleryTags->getCacheJoin( array( 'meta_id_field' => 'i.image_id' ) );
		
		//-----------------------------------------
		// Fetch the image
		//-----------------------------------------

		$image	= $this->DB->buildAndFetch( array(
												'select'	=> 'i.*, i.image_member_id as mid',
												'from'		=> array( 'gallery_images' => 'i' ),
												'where'		=> 'i.image_id=' . $imageId,
												'add_join'	=> $joins
										)		);

		//-----------------------------------------
		// Verify we can view
		//-----------------------------------------

		if ( empty( $image['image_id'] ) OR ! $this->registry->gallery->helper('image')->isViewable( $image ) )
		{
			return $this->returnHtml( $this->registry->output->getTemplate('gallery_global')->bbCodeImageNoPermission() );
		}

		//-----------------------------------------
		// Format the joined tag data
		//-----------------------------------------

		if ( ! empty( $image['tag_cache_key'] ) )
		{
			$image['tags']	= $this->registry->galleryTags->formatCacheJoinData( $image );
		}
		
		//-----------------------------------------
		// Parse the author member data
		//-----------------------------------------

		$author	= IPSMember::buildDisplayData( $image );

		//-----------------------------------------
		// Unpack the image notes
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->unpackNotes( $image );

		//-----------------------------------------
		// Parse description
		//-----------------------------------------

		$image['image_description']	= empty($image['image_description']) ? '' : IPSText::getTextClass('bbcode')->preDisplayParse( $image['image_description'] );
		
		//-----------------------------------------
		// Fix guest name
		//-----------------------------------------

		if ( empty($author['member_id']) )
		{
			$author['members_display_name']	= $this->lang->words['global_guestname'];
		}

		//-----------------------------------------
		// Pull out serialized image data
		//-----------------------------------------

		if ( IPSLib::isSerialized( $image['image_data'] ) )
		{
			$image['_data'] = IPSLib::safeUnserialize( $image['image_data'] );
		}

		//-----------------------------------------
		// If this is a media file, get the HTML
		//-----------------------------------------

		if ( $image['image_media'] )
		{
			if ( ! $this->registry->gallery->helper('image')->checkBandwidth() )
			{
				$image['movie']	= $this->lang->words['bwlimit'];
			}
			else
			{
				$image['movie']	= $this->registry->gallery->helper('media')->getPlayerHtml( $image );
			}

			$dimensions	 = array( 0 => 0, 1 => 0 );
		}

		//-----------------------------------------
		// Otherwise get appropriate image HTML
		//-----------------------------------------

		else
		{
			$image['image']			= $this->registry->gallery->helper('image')->makeImageTag( $image, array( 'type' => 'medium', 'link-type' => 'none' ) );
			$image['image_url']		= $this->registry->gallery->helper('image')->makeImageTag( $image, array( 'type' => 'full'  , 'link-type' => 'src' ) );
			$image['med_image_url']	= $this->registry->gallery->helper('image')->makeImageTag( $image, array( 'type' => 'medium'  , 'link-type' => 'src' ) );
		 	$dimensions				= $image['_data']['sizes']['max'];
		}

		//-----------------------------------------
		// Format some more vars
		//-----------------------------------------

		$image['dimensions']		= intval($dimensions[0]) . 'x' . intval($dimensions[1]);
		$image['filesize']			= IPSLib::sizeFormat( $image['image_file_size'] );
		
		$image['image_copyright']	= $image['image_copyright'] ? $image['image_copyright'] : '';
		$image['image_copyright']	= str_replace( "&amp;copy", "&copy", $image['image_copyright'] );
		
		//-----------------------------------------
		// Parse out EXIF/IPTC data
		//-----------------------------------------

		$image['_date_taken']	= '';
		$image['_camera_model']	= '';
		$image['metahtml']		= '';

		if ( $image['image_metadata'] != '' )
		{
			$meta_info	= IPSLib::safeUnserialize( $image['image_metadata'] );
			
			if ( ! empty( $meta_info['GPS'] ) )
			{
				unset( $meta_info['GPS'] );
			}
			
			if ( is_array($meta_info) AND count($meta_info) )
			{
				//-----------------------------------------
				// Lang abstraction
				//-----------------------------------------

				if( !$this->lang->words['check_key'] )
				{
					$this->lang->loadLanguageFile( array( 'public_meta' ), 'gallery' );
				}

				foreach( $meta_info as $k => $v )
				{
					if( array_key_exists( $k, $this->lang->words ) )
					{
						unset( $meta_info[ $k ] );

						if( array_key_exists( $k . '_map_' . $v, $this->lang->words ) )
						{
							$meta_info[ $this->lang->words[ $k ] ]	= $this->lang->words[ $k . '_map_' . $v ] ? $this->lang->words[ $k . '_map_' . $v ] : htmlspecialchars($v);
						}
						else
						{
							$meta_info[ $this->lang->words[ $k ] ]	= $v;
						}
					}
				}

				$image['metahtml']	= $this->registry->output->getTemplate('gallery_img')->meta_html( $meta_info );
				
				if ( ! empty( $meta_info[ $this->lang->words['IFD0.Model'] ] ) )
				{
					$image['_camera_model'] = $meta_info[ $this->lang->words['IFD0.Model'] ];
					
					if ( ! empty( $meta_info[ $this->lang->words['IFD0.Make'] ] ) )
					{
						if ( ! stristr( $image['_camera_model'], $meta_info[ $this->lang->words['IFD0.Make'] ] ) )
						{
							$image['_camera_model']	= $meta_info[ $this->lang->words['IFD0.Make'] ] . ' ' . $image['_camera_model'];
						}
					}
				}
				
				if ( ! empty( $meta_info[ $this->lang->words['IFD0.DateTime'] ] ) )
				{
					$image['_date_taken'] = $meta_info[ $this->lang->words['IFD0.DateTime'] ];
				}
			}
		}

		//-----------------------------------------
		// Location services
		//-----------------------------------------

		if ( $image['image_gps_lat'] )
		{
			$image['_latLon']	= implode( ',', $this->registry->gallery->helper('image')->getLatLon( $image ) );
			$image['_locShort']	= $image['image_loc_short'];
			
			if ( ! $image['_locShort'] )
			{
				$_gps				= $this->_mapping->reverseGeoCodeLookUp( $image['image_gps_lat'], $image['image_gps_lon'] );
				$image['_locShort']	= $_gps['geocache_short'];
			}
			
			$image['_maps']		= $this->_mapping->getImageUrls( $image['image_gps_lat'], $image['image_gps_lon'], '300x180' );
			$image['_mapUrl']	= $this->_mapping->getMapUrl( $image['image_gps_lat'], $image['image_gps_lon'] );
		}
		else
		{
			$image['_latLon']	= $image['_geocode'] = $image['_locShort'] = false;
		}

		//-----------------------------------------
		// Fetch comment HTML
		//-----------------------------------------

		$comment_html	= $this->_comments->fetchFormatted( $image, array( 'offset' => intval( $this->request['st'] ) ) );

		//-----------------------------------------
		// Mark image as read
		//-----------------------------------------

		$this->registry->classItemMarking->markRead( array( 'albumID' => $image['image_album_id'], 'categoryID' => $image['image_category_id'], 'itemID' => $image['image_id'] ), 'gallery' );

		//-----------------------------------------
		// Get reputation
		//-----------------------------------------

		if ( $this->settings['reputation_enabled'] )
		{
			$image['like']	= $this->registry->repCache->getLikeFormatted( array( 'app' => 'gallery', 'type' => 'image_id', 'id' => $image['image_id'], 'rep_like_cache' => $image['rep_like_cache'] ) );
		}

		return $this->returnHtml( $this->registry->output->getTemplate('gallery_img')->ajaxShowImage( $image, $author, $comment_html ) );
	}

	/**
	 * Removes a map to the image.
	 *
	 * @return	@e void
	 */
	protected function _removeMap()
	{
		//-----------------------------------------
		// Fetch the image
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );
		
		if ( empty( $image['image_id'] ) )
		{
			return $this->returnJsonError( 'its_not_you_its_me' );
		}

		//-----------------------------------------
		// Are we the owner or a moderator?
		//-----------------------------------------

		if ( $image['image_member_id'] != $this->memberData['member_id'] AND !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) )
		{
			return $this->returnJsonError( 'its_not_you_its_me' );
		}
		
		//-----------------------------------------
		// Remove the map and return
		//-----------------------------------------

		$this->DB->update( 'gallery_images', array( 'image_gps_show' => 0 ), "image_id=" . $image['image_id'] );
		
		return $this->returnJsonArray( array( 'done' => 1 ) );
	}
    
	/**
	 * Adds a map to the image.
	 *
	 * @return	@e void
	 */
	protected function _addMap()
	{
		//-----------------------------------------
		// Fetch the image
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );
		
		if ( empty( $image['image_id'] ) )
		{
			return $this->returnJsonError( 'its_not_you_its_me' );
		}

		//-----------------------------------------
		// Are we the owner or a moderator?
		//-----------------------------------------

		if ( $image['image_member_id'] != $this->memberData['member_id'] AND !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) )
		{
			return $this->returnJsonError( 'its_not_you_its_me' );
		}

		//-----------------------------------------
		// Fetch the lat/lon values
		//-----------------------------------------

		$latLon = $this->registry->gallery->helper('image')->getLatLon( $image );
		
		if ( ! $latLon )
		{
			return $this->returnJsonError( 'i_dont_know_who_you_are_anymore' );
		}
		
		//-----------------------------------------
		// Update the image and return
		//-----------------------------------------

		$this->DB->update( 'gallery_images', array( 'image_gps_show' => 1 ), "image_id=" . $image['image_id'] );
		
		return $this->returnJsonArray( array( 'latLon' => implode( ",", $latLon ) ) );
	}
	
	/**
	 * Remove uploads for this session
	 *
	 * @return	@e void
	 */
	protected function _removeUpload()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$sessionKey	= trim( $this->request['sessionKey'] );
		$uploadKey	= trim( $this->request['uploadKey'] );

		//-----------------------------------------
		// Remove and return
		//-----------------------------------------

		if ( $uploadKey AND $sessionKey )
		{
			return $this->returnJsonArray( $this->registry->gallery->helper('upload')->removeUpload( $sessionKey, $uploadKey ) );
		}

		return $this->returnJsonError( 'its_not_you_its_me' );
	}
	
	/**
	 * Fetches all uploads for this 'session'
	 *
	 * @return	@e void
	 */
	protected function _fetchUploads()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$sessionKey	= trim( $this->request['sessionKey'] );
		$albumId	= intval( $this->request['album_id'] );
		$categoryId	= intval( $this->request['category_id'] );
		
		//-----------------------------------------
		// Fetch and return
		//-----------------------------------------

		return $this->returnJsonArray( $this->registry->gallery->helper('upload')->fetchSessionUploadsAsJson( $sessionKey, $albumId, $categoryId ) );
	}
	
	/**
	 * Adds a new note to an image
	 *
	 * @return	@e void
	 */
	public function addNote()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$id		= intval( $this->request['img'] );
		$top	= intval( $this->request['top'] );
		$left	= intval( $this->request['left'] );
		$width	= intval( $this->request['width'] );
		$height	= intval( $this->request['height'] );
		$note	= $this->convertAndMakeSafe( $_POST['note'], TRUE );
		
		//-----------------------------------------
		// Are we missing anything?
		//-----------------------------------------

		if( ! $id || ! $top || ! $left || ! $width || ! $height || ! $note )
		{
			$this->returnString( 'missing_data' );
		}
		
		//-----------------------------------------
		// Fix unicode issues
		//-----------------------------------------

		if ( strtolower(IPS_DOC_CHAR_SET) != 'utf-8' )
		{
			$note = IPSText::utf8ToEntities( $note );
		}
		
		//-----------------------------------------
		// Fetch the image and test permissions
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( $id );
		
		if( !$this->registry->gallery->helper('image')->isViewable( $image ) )
		{
			$this->returnString( 'nopermission' );
		}

		if ( $image['image_member_id'] != $this->memberData['member_id'] AND !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) )
		{ 
			$this->returnString( 'nopermission' );
		}
		
		//-----------------------------------------
		// Fetch the current notes
		//-----------------------------------------

		$currNotes	= IPSLib::safeUnserialize( $image['image_notes'] );
		$currNotes	= is_array( $currNotes ) ? $currNotes : array();
		
		//-----------------------------------------
		// Add the new note
		//-----------------------------------------

		$noteId			= md5( time() );
		$currNotes[]	= array(
								'id'		=> $noteId,
								'top'		=> $top,
								'left'		=> $left,
								'width'		=> $width,
								'height'	=> $height,
								'note'		=> $note
								);
							
		//-----------------------------------------
		// Store in the database and return
		//-----------------------------------------

		$this->DB->update( 'gallery_images', array( 'image_notes' => serialize( $currNotes ) ), "image_id={$id}" );
		
		$this->returnString( 'ok|' . $noteId );
	}
	
	/**
	 * Edit an existing image note
	 *
	 * @return	@e void
	 */
	public function editNote()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$id		= intval( $this->request['img'] );
		$top	= intval( $this->request['top'] );
		$left	= intval( $this->request['left'] );
		$width	= intval( $this->request['width'] );
		$height	= intval( $this->request['height'] );
		$note	= $this->convertAndMakeSafe( $_POST['note'], TRUE );
		$noteId	= $this->request['noteId'];
		
		//-----------------------------------------
		// Make sure we have everything
		//-----------------------------------------

		if( ! $id || ! $top || ! $left || ! $width || ! $height || ! $note || ! $noteId )
		{
			$this->returnString( 'missing_data' );
		}
		
		//-----------------------------------------
		// Fetch the image and test permissions
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( $id );
		
		if( !$this->registry->gallery->helper('image')->isViewable( $image ) )
		{
			$this->returnString( 'nopermission' );
		}

		if ( $image['image_member_id'] != $this->memberData['member_id'] AND !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) )
		{ 
			$this->returnString( 'nopermission' );
		}
		
		//-----------------------------------------
		// Fetch the current ntoes
		//-----------------------------------------

		$currNotes	= IPSLib::safeUnserialize( $image['image_notes'] );
		$currNotes	= is_array( $currNotes ) ? $currNotes : array();
		
		//-----------------------------------------
		// Loop through and find our note
		//-----------------------------------------

		foreach( $currNotes as $k => $v )
		{
			//-----------------------------------------
			// Is this it?  If so, update
			//-----------------------------------------

			if( $v['id'] == $noteId )
			{
				$currNotes[$k]['top']		= $top;
				$currNotes[$k]['left']		= $left;
				$currNotes[$k]['width']		= $width;
				$currNotes[$k]['height']	= $height;
				$currNotes[$k]['note']		= $note;
				
				break;
			}
		}

		//-----------------------------------------
		// Store in DB and return
		//-----------------------------------------

		$this->DB->update( 'gallery_images', array( 'image_notes' => serialize( $currNotes ) ), "image_id={$id}" );
		
		$this->returnString( 'ok' );
	}
	
	/**
	 * Remove an existing image note
	 *
	 * @return	@e void
	 */
	public function removeNote()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$id			= intval( $this->request['img'] );
		$noteId		= $this->request['noteId'];
		
		//-----------------------------------------
		// Make sure we have everything
		//-----------------------------------------

		if( ! $id || ! $noteId )
		{
			$this->returnString( 'missing_data' );
		}
		
		//-----------------------------------------
		// Fetch the image and test permissions
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( $id );
		
		if( !$this->registry->gallery->helper('image')->isViewable( $image ) )
		{
			$this->returnString( 'nopermission' );
		}

		if ( $image['image_member_id'] != $this->memberData['member_id'] AND !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) )
		{ 
			$this->returnString( 'nopermission' );
		}
		
		//-----------------------------------------
		// Fetch our current notes
		//-----------------------------------------

		$currNotes		= IPSLib::safeUnserialize( $image['image_notes'] );
		$currNotes		= is_array( $currNotes ) ? $currNotes : array();
		$newNoteArray	= array();

		//-----------------------------------------
		// Loop through the notes and remove this note
		//-----------------------------------------

		foreach( $currNotes as $k => $v )
		{
			if( $v['id'] != $noteId )
			{
				$newNoteArray[] = $v;
			}
		}

		//-----------------------------------------
		// Store in DB and return
		//-----------------------------------------

		$this->DB->update( 'gallery_images', array( 'image_notes' => serialize( $newNoteArray ) ), "image_id={$id}" );
		
		$this->returnString( 'ok' );
	}

	/**
	 * Rotates an image via ajax.  Source could be upload table or image table
	 *
	 * @param	string	$direction	right or left
	 * @return	@e void
	 */
	public function rotateImage( $direction )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$id		= trim( $this->request['img'] );

		//-----------------------------------------
		// Fetch the image and test permissions
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( $id );
		
		if( !$this->registry->gallery->helper('image')->isViewable( $image ) )
		{
			$this->returnString( 'nopermission' );
		}

		if ( $image['image_member_id'] != $this->memberData['member_id'] AND !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) )
		{ 
			$this->returnString( 'nopermission' );
		}

		//-----------------------------------------
		// Rotate the image and return
		//-----------------------------------------

		if( $this->registry->gallery->helper('image')->rotateImage( $image, ( $direction == 'left' ) ? 90 : -90 ) )
		{
			$this->returnString( 'ok' );
		}
		else
		{
			$this->returnString( 'rotate_failed' );
		}
	}

	/**
	 * Sets an image as a photo.
	 *
	 * @return	@e void
	 */
	protected function _setAsPhoto()
	{
		//-----------------------------------------
		// Fetch the image and test permissions
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageId'] ) );

		if ( empty( $image['image_id'] ) )
		{
			$this->returnJsonArray( array( 'status' => 'error' ) );
		}

		if( !$this->registry->gallery->helper('image')->isViewable( $image ) )
		{
			$this->returnJsonArray( array( 'status' => 'error' ) );
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$x1			= intval( $this->request['x1'] );
		$x2			= intval( $this->request['x2'] );
		$y1			= intval( $this->request['y1'] );
		$y2			= intval( $this->request['y2'] );
		$dir		= $image['image_directory'] ? $image['image_directory'] . "/" : '';
		$save		= array();
		$tHeight	= $this->settings['member_photo_crop'] ? $this->settings['member_photo_crop'] : 100;
		$tWidth		= $this->settings['member_photo_crop'] ? $this->settings['member_photo_crop'] : 100;
		$filename	= $image['image_medium_file_name'] ? $image['image_medium_file_name'] : $image['image_masked_file_name'];

		//-----------------------------------------
		// Make sure image exists
		//-----------------------------------------

		if ( ! file_exists( $this->settings['gallery_images_path'] . '/' . $dir . $filename ) )
		{
			$this->returnJsonArray( array( 'status' => 'error' ) );
		}
		
		//-----------------------------------------
		// Get member photo restrictions
		//-----------------------------------------

		list( $pMax, $pWidth, $pHeight )	= explode( ":", $this->memberData['g_photo_max_vars'] );
		
		//-----------------------------------------
		// Fix the upload path
		//-----------------------------------------

		$this->settings['upload_dir']	= str_replace( '&#46;', '.', $this->settings['upload_dir'] );		
		$upload_path					= $this->settings['upload_dir'];
		$_upload_path					= $this->settings['upload_dir'];
		$upload_dir						= "";

		//-----------------------------------------
		// Create /profile directory if missing (and we can)
		//-----------------------------------------

		if ( ! file_exists( $upload_path . '/profile' ) )
		{
			if ( @mkdir( $upload_path . '/profile', IPS_FOLDER_PERMISSION ) )
			{
				@file_put_contents( $upload_path . '/profile/index.html', '' );
				@chmod( $upload_path . '/profile', IPS_FOLDER_PERMISSION );

				$upload_path	.= '/profile';
				$upload_dir		= 'profile/';
			}
		}
		else
		{
			$upload_path	.= "/profile";
			$upload_dir		= "profile/";
		}
		
		//-----------------------------------------
		// Figure out the filenames and urls
		//-----------------------------------------
		
		$fileExt		= IPSText::getFileExtension( $filename );
		$photoFullSize	= $upload_path . '/' . 'photo-' . $this->memberData['member_id'] . '.' . $fileExt;
		$photoThumb		= $upload_path . '/' . 'photo-thumb-' . $this->memberData['member_id'] . '.' . $fileExt;
		$photoFullLoc	= $upload_dir . 'photo-' . $this->memberData['member_id'] . '.' . $fileExt;
		$photoThumbLoc	= $upload_dir . 'photo-thumb-' . $this->memberData['member_id'] . '.' . $fileExt;
		
		//-----------------------------------------
		// Get our image library
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
		$img = ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
		
		//-----------------------------------------
		// Set up our resizing params
		//-----------------------------------------

		$settings	= array(
							'image_path'	=> $this->settings['gallery_images_path'] . '/' . $dir, 
							'image_file'	=> $filename,
							'im_path'		=> $this->settings['gallery_im_path'],
							'temp_path'		=> DOC_IPS_ROOT_PATH . '/cache/tmp',
							'jpg_quality'	=> GALLERY_JPG_QUALITY,
							'png_quality'	=> GALLERY_PNG_QUALITY
							);
		
		//-----------------------------------------
		// Build full size profile photo
		//-----------------------------------------

		if ( $img->init( $settings ) )
		{
			//-----------------------------------------
			// Remove our existing profile photos, if any
			//-----------------------------------------

			$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/photo.php', 'classes_member_photo' );
			$photos			= new $classToLoad( $this->registry );
			$photos->removeUploadedPhotos( $this->memberData['member_id'] );	
			
			//-----------------------------------------
			// Crop and write to disk
			//-----------------------------------------

			$width	= $x2 - $x1;
			$height	= $y2 - $y1;

			$return	= $img->crop( $x1, $y1, $width, $height );
			
			if ( $img->writeImage( $photoFullSize ) )
			{
				$save['pp_main_photo']   = $photoFullLoc;
				$save['pp_main_width']   = $return['newWidth'];
				$save['pp_main_height']  = $return['newHeight'];
				$save['pp_thumb_photo']  = $photoThumbLoc;
				$save['pp_thumb_width']  = $return['newWidth'];
				$save['pp_thumb_height'] = $return['newHeight'];
				$save['fb_photo']		 = '';
				$save['fb_photo_thumb']	 = '';
			}
			
			unset( $img );
		}

		//-----------------------------------------
		// Resize image down if crop is too large
		//-----------------------------------------

		if ( ! empty( $save['pp_main_width'] ) && ( $save['pp_main_width'] > $pWidth OR $save['pp_main_height'] > $pHeight ) )
		{
			//-----------------------------------------
			// Reinitialize image library
			//-----------------------------------------

			$img	= ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
		
			//-----------------------------------------
			// Set up params
			//-----------------------------------------

			$settings	= array(
								'image_path'	=> $upload_path, 
								'image_file'	=> 'photo-' . $this->memberData['member_id'] . '.' . $fileExt,
								'im_path'		=> $this->settings['gallery_im_path'],
								'temp_path'		=> DOC_IPS_ROOT_PATH . '/cache/tmp',
								'jpg_quality'	=> GALLERY_JPG_QUALITY,
								'png_quality'	=> GALLERY_PNG_QUALITY
								);

			//-----------------------------------------
			// Resize, write image and reset save array
			//-----------------------------------------

			if ( $img->init( $settings ) )
			{
				$return = $img->resizeImage( $pWidth, $pHeight );
				
				if ( $img->writeImage( $photoFullSize ) )
				{
					$save['pp_main_width']  = $return['newWidth'];
					$save['pp_main_height'] = $return['newHeight'];
				}
				
				unset( $img );
			}
		}

		//-----------------------------------------
		// Build thumbnail
		//-----------------------------------------

		if ( ! empty( $save['pp_main_width'] ) && ( $save['pp_main_width'] > $tWidth OR $save['pp_main_height'] > $tHeight ) )
		{
			//-----------------------------------------
			// Reinitialize image library
			//-----------------------------------------

			$img	= ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
		
			//-----------------------------------------
			// Set up params
			//-----------------------------------------

			$settings	= array(
								'image_path'	=> $upload_path, 
								'image_file'	=> 'photo-' . $this->memberData['member_id'] . '.' . $fileExt,
								'im_path'		=> $this->settings['gallery_im_path'],
								'temp_path'		=> DOC_IPS_ROOT_PATH . '/cache/tmp',
								'jpg_quality'	=> GALLERY_JPG_QUALITY,
								'png_quality'	=> GALLERY_PNG_QUALITY
								);

			//-----------------------------------------
			// INIT library and build thumb
			//-----------------------------------------

			if ( $img->init( $settings ) )
			{
				$return = $img->resizeImage( $tWidth, $tHeight );
				
				if ( $img->writeImage( $photoThumb ) )
				{
					$save['pp_thumb_photo']  = $photoThumbLoc;
					$save['pp_thumb_width']  = $return['newWidth'];
					$save['pp_thumb_height'] = $return['newHeight'];
				}
				
				unset( $img );
			}
		}
		
		//-----------------------------------------
		// Save the photo
		//-----------------------------------------

		if ( count( $save ) )
		{
			IPSMember::save( $this->memberData['member_id'], array( 'extendedProfile' => $save ) );
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------

		$this->returnJsonArray( array( 'status' => 'ok', 'oldPhoto' => $this->memberData['pp_thumb_photo'], 'photo' => $this->settings['upload_url'] . '/' . $photoFullLoc, 'thumb' => $this->settings['upload_url'] . '/' . $photoThumbLoc ) );
	}

	/**
	 * Return the move image dialog box
	 *
	 * Rules:
	 * -If you are a moderator with permission to move images in the container the image is currently in, you can move to anywhere you can view
	 * -If you are a member, you can move images between your own albums
	 * -If you are a member, you cannot move images from a category, to a category, or from/to an album you do not own
	 *
	 * @return	@e void
	 */
	protected function _moveDialogue()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );
		$data	= array();
		
		if ( empty( $image['image_id'] ) )
		{
			return false;
		}

		//-----------------------------------------
		// Moderators can move images
		//-----------------------------------------

		if( $this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_move' ) )
		{
			$data['options']		= $this->registry->gallery->helper('albums')->getOptionTags( 0, array( 'isUploadable' => 1, 'memberData' => array( 'member_id' => $image['image_member_id'] ), 'skip' => array( $image['image_album_id'] ), 'moderatingData'=> array( 'moderator' => $this->memberData['member_id'], 'action'=> 'moveImages') ) );
			$data['_catOptions']	= $this->registry->gallery->helper('categories')->catJumpList( false, 'images', array(), $this->lang->words['cs_select'] );
			$data['_acceptImages']	= $this->registry->gallery->helper('categories')->fetchImageCategories();
		}
		else
		{
			//-----------------------------------------
			// A non-mod can only move this image if it is their own, and in an album
			// and only to another album that they own
			//-----------------------------------------

			if( $image['image_member_id'] != $this->memberData['member_id'] OR !$image['image_album_id'] )
			{
				$this->returnHtml( $this->lang->words['move_image_no_perm'] );
			}

			$data['options']		= $this->registry->gallery->helper('albums')->getOptionTags( 0, array( 'isUploadable' => 1, 'memberData' => array( 'member_id' => $image['image_member_id'] ), 'album_owner_id' => $image['image_member_id'], 'skip' => array( $image['image_album_id'] ) ) );
			$data['_catOptions']	= array();
			$data['_acceptImages']	= array();
		}

		//-----------------------------------------
		// Pass to the template
		//-----------------------------------------

		return $this->returnHtml( $this->registry->output->getTemplate('gallery_img')->moveDialogue( $image, $data ) );
	}

	/**
	 * Retrieve the "set as cover" options for this image
	 *
	 * @return	@e void
	 */
	protected function _getCoverOptions()
	{
		//-----------------------------------------
		// Get our image
		//-----------------------------------------

		$image		= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageId'] ) );

		//-----------------------------------------
		// If we are just a member, we can only set as album cover
		// Otherwise we can set for album and parent categories
		//-----------------------------------------

		if( !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) )
		{
			//-----------------------------------------
			// Are we the owner?
			//-----------------------------------------

			if( !$image['image_album_id'] OR !$this->registry->gallery->helper('albums')->isOwner( $image ) )
			{
				$this->returnJsonArray( array( 'status' => 'error' ) );
			}

			//-----------------------------------------
			// Build our one and only option
			//-----------------------------------------

			$options	= array( array( 'a' . $image['image_album_id'], $this->lang->words['dd_album_pre'] . ' ' . $image['album_name'], ( $image['image_id'] == $image['album_cover_img_id'] ) ) );
		}
		else
		{
			//-----------------------------------------
			// Add album option, if in an album
			//-----------------------------------------

			$options	= array();

			if( $image['image_album_id'] )
			{
				$options[]	= array( 'a' . $image['image_album_id'], $this->lang->words['dd_album_pre'] . ' ' . $image['album_name'], ( $image['image_id'] == $image['album_cover_img_id'] ) );
			}

			//-----------------------------------------
			// Now add categories
			//-----------------------------------------

			$_category	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'] );
			$options[]	= array( $image['image_category_id'], $this->lang->words['dd_category_pre'] . ' ' . $_category['category_name'], ( $image['image_id'] == $_category['category_cover_img_id'] ) );

			foreach( $this->registry->gallery->helper('categories')->getParents( $image['image_category_id'] ) as $category )
			{
				$_category	= $this->registry->gallery->helper('categories')->fetchCategory( $category );
				$options[]	= array( $category, $this->lang->words['dd_category_pre'] . ' ' . $_category['category_name'], ( $image['image_id'] == $_category['category_cover_img_id'] ) );
			}
		}

		//-----------------------------------------
		// Pass to the template
		//-----------------------------------------

		return $this->returnHtml( $this->registry->output->getTemplate('gallery_img')->setAsCover( $image, $options ) );
	}
}