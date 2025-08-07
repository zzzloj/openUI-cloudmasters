<?php
/**
 * @file		viewimage.php 	Display the image view screen
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $ (Orginal: Matt Mecham)
 * $LastChangedDate: 2013-05-21 20:51:17 -0400 (Tue, 21 May 2013) $
 * @version		v5.0.5
 * $Revision: 12263 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_viewimage extends ipsCommand
{
	/**
	 * Comments class
	 *
	 * @var	object
	 */
	protected $_comments;

	/**
	 * Follow class
	 *
	 * @var	object
	 */
	protected $_follow;

	/**
	 * Mapping class
	 *
	 * @var	object
	 */
	protected $_mapping;

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Do we have an image?
		//-----------------------------------------

		$imageId	= intval($this->request['image']);
		
		if ( $imageId < 1 )
		{
			//-----------------------------------------
			// Is this a legacy link?
			//-----------------------------------------

			if ( $this->request['img'] )
			{
				$image	= $this->registry->gallery->helper('image')->fetchImage( intval($this->request['img']) );

				if( $image['image_id'] )
				{
					$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&image=' . $image['image_id'], $image['image_caption_seo'], TRUE, 'viewimage' );
				}
			}

			//-----------------------------------------
			// No image found
			//-----------------------------------------

			$this->registry->output->showError( 'img_not_found', 10740, null, null, 404 );
		}
		
		//-----------------------------------------
		// Load language files
		//-----------------------------------------

		$this->lang->loadLanguageFile( array( 'public_topic' ), 'forums' );
		$this->lang->loadLanguageFile( array( 'public_editors'), 'core' );
		
		//-----------------------------------------
		// Grab some other classes we'll need
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
		$this->_comments = classes_comments_bootstrap::controller( 'gallery-images' );
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_follow = classes_like::bootstrap( 'gallery', 'images' );
		
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
		// Did we find an image?
		//-----------------------------------------

		if ( ! $image['image_id'] )
		{
		 	$this->registry->output->showError( 'img_not_found', 10744, null, null, 404 );
		}
		
		//-----------------------------------------
		// Can we view this image?
		//-----------------------------------------

		if ( ! $this->registry->gallery->helper('image')->isViewable( $image ) )
		{
			$this->registry->output->showError( 'img_not_found', 10744.1, null, null, 403 );
		}
		
		//-----------------------------------------
		// Verify permalink is correct
		//-----------------------------------------

		$this->registry->getClass('output')->checkPermalink( $image['image_caption_seo'] );
		
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
		// Set some keys for the session class
		//-----------------------------------------

		$this->member->sessionClass()->addQueryKey( 'location_2_id', intval( $image['image_album_id'] ) );
		$this->member->sessionClass()->addQueryKey( 'location_3_id', intval( $image['image_category_id'] ) );

		//-----------------------------------------
		// Pull out serialized image data
		//-----------------------------------------

		if ( IPSLib::isSerialized( $image['image_data'] ) )
		{
			$image['_data'] = IPSLib::safeUnserialize( $image['image_data'] );
		}

		//-----------------------------------------
		// Unpack the image notes
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->unpackNotes( $image );

		//-----------------------------------------
		// Fetch the image output
		//-----------------------------------------

		$output	= $this->_showImage( $image, $author );

		//-----------------------------------------
		// Update 'views' count
		//-----------------------------------------

		$this->DB->update( 'gallery_images', array( 'image_views' => ( $image['image_views'] + 1 ) ), 'image_id=' . $image['image_id'], true );

		//-----------------------------------------
		// Set the meta image source to the image tag
		//-----------------------------------------

		$this->settings['meta_imagesrc'] = $this->registry->gallery->helper('image')->makeImageTag( $image, array( 'type' => 'thumb', 'link-type' => 'src' ) );

		//-----------------------------------------
		// Fetch navigation
		//-----------------------------------------

		$nav	= array( array( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' ) );
		$cat	= $this->registry->gallery->helper('categories')->getNav( $image['image_category_id'] );

		foreach( $cat as $_categoryNav )
		{
			$nav[]	= array( $_categoryNav[0], $_categoryNav[1], $_categoryNav[2], 'viewcategory' );
		}

		if( $image['image_album_id'] )
		{
			$_album	= $this->registry->gallery->helper('albums')->fetchAlbum( $image['image_album_id'] );
			$title	= $_album['album_name'];

			$nav[]	= array( $_album['album_name'], 'app=gallery&amp;album=' . $_album['album_id'], $_album['album_name_seo'], 'viewalbum' );
		}
		else
		{
			$title	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'], 'category_name' );
		}

		$nav[]	= array( $image['image_caption'] );

		//-----------------------------------------
		// Set output elements
		//-----------------------------------------

		$this->registry->getClass('output')->addContent( $output );
		$this->registry->getClass('output')->setTitle( $image['image_caption'] . ' - ' . $title . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );

		foreach( $nav as $_nav )
		{
			$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
		}
		
		$this->registry->getClass('output')->addCanonicalTag( 'app=gallery&amp;image=' . $image['image_id'], $image['image_caption_seo'], 'viewimage' );
		$this->registry->getClass('output')->storeRootDocUrl( $this->registry->getClass('output')->buildSEOUrl( 'app=gallery&amp;image=' . $image['image_id'], 'publicNoSession', $image['image_caption_seo'], 'viewimage' ) );
		$this->registry->output->addMetaTag( 'keywords', $image['image_caption'], TRUE );
		$this->registry->output->addMetaTag( 'description', sprintf( $this->lang->words['gallery_img_meta_description'], $image['image_caption'], $title, $image['image_description'] ), FALSE, 155 );

		//-----------------------------------------
		// Send output
		//-----------------------------------------

		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Show an image from an album
	 *
	 * @param	array		Image array
	 * @param	array		Author array
	 * @return	@e void
	 */
	protected function _showImage( $image, $author )
	{
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
		// Set up image buttons
		//-----------------------------------------

		$image = $this->_setImageButtons( $image );

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
			$image['image']		= $this->registry->gallery->helper('image')->makeImageTag( $image, array( 'type' => 'medium', 'link-type' => 'none' ) );
			$image['image_url']	= $this->registry->gallery->helper('image')->makeImageTag( $image, array( 'type' => 'full'  , 'link-type' => 'src' ) );
		 	$dimensions			= $image['_data']['sizes']['max'];
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
		// Get comment HTML
		//-----------------------------------------

		$comment_html	= $this->_comments->fetchFormatted( $image, array( 'offset' => intval( $this->request['st'] ) ) );

		//-----------------------------------------
		// Get photo strip
		//-----------------------------------------

		$photo_strip	= $this->registry->gallery->helper('image')->fetchPhotoStrip( $image );
		
		//-----------------------------------------
		// Get follow data
		//-----------------------------------------

		$follow			= $this->_follow->render( 'summary', $image['image_id'] );
		
		//-----------------------------------------
		// Mark image as read
		//-----------------------------------------

		$this->registry->classItemMarking->markRead( array( 'albumID' => $image['image_album_id'], 'categoryID' => $image['image_category_id'], 'itemID' => $image['image_id'] ), 'gallery' );

		//-----------------------------------------
		// Get reputation
		//-----------------------------------------

		if ( $this->settings['reputation_enabled'] )
		{
			$image['rep_points']	= $this->registry->repCache->getRepPoints( array( 'app' => 'gallery', 'type' => 'image_id', 'type_id' => $image['image_id'], 'rep_points' => $image['rep_points'] ) );
			$image['like']			= $this->registry->repCache->getLikeFormatted( array( 'app' => 'gallery', 'type' => 'image_id', 'id' => $image['image_id'], 'rep_like_cache' => $image['rep_like_cache'] ) );
		}
		
		//-----------------------------------------
		// Can we report the image?
		//-----------------------------------------

		$classToLoad			= IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportLibrary.php', 'reportLibrary' );
		$reports				= new $classToLoad( $this->registry );
		$image['_canReport']	= $reports->canReport('gallery');
		
		//-----------------------------------------
		// Pass to the skin template and return HTML
		//-----------------------------------------

		return $this->registry->output->getTemplate('gallery_img')->show_image( $image, $author, $photo_strip, $comment_html, $this->registry->gallery->helper('image')->fetchNextPrevImages( $image['image_id'] ), $follow );
	}

	/**
	 * Get special buttons
	 *
	 * @param	array	$image	Image data
	 * @return	@e array
	 */
	public function _setImageButtons( $image )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$image['edit_button']		= false;
		$image['mod_buttons']		= false;
		$image['delete_button']		= false;
		$image['pin_button']		= false;
		$image['approve_button']	= false;
		$image['move_button']		= false;
		$image['set_as_cover']		= false;
		$image['image_control_mod']	= false;

		//-----------------------------------------
		// Are we a moderator or the image owner?
		//-----------------------------------------

		$_isModerator	= $this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] );
		$_isOwner		= ( $image['image_member_id'] == $this->memberData['member_id'] AND $this->memberData['member_id'] ) ? true : false;
		$_album			= array();

		if( $image['image_album_id'] )
		{
			$_album	= $this->registry->gallery->helper('albums')->fetchAlbum( $image['image_album_id'] );
		}

		//-----------------------------------------
		// Can we edit image?
		//-----------------------------------------

		if( $this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_edit' ) || ( $_isOwner && $this->memberData['g_edit_own'] ) )
		{
			$image['edit_button']		= true;

			if( $this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_edit' ) )
			{
				$image['pin_button']		= true;
			}

			$image['mod_buttons']		= true;
		}

		//-----------------------------------------
		// Can we delete?
		//-----------------------------------------

		if( $this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_delete' ) || ( $_isOwner && $this->memberData['g_del_own'] ) )
		{
			$image['delete_button']		= true;
			$image['mod_buttons']		= true;
		}

		//-----------------------------------------
		// Can we move?
		//-----------------------------------------

		if( $this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_move' ) )
		{
			$image['move_button']		= true;
			$image['mod_buttons']		= true;
		}
		
		//-----------------------------------------
		// Can we set as cover image?
		//-----------------------------------------

		if ( $this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_set_cover_image' ) || ( $image['image_album_id'] && $_album['album_owner_id'] == $this->memberData['member_id'] && $this->memberData['member_id'] ) )
		{
			$image['set_as_cover']	= true;
		}
		
		//-----------------------------------------
		// Can we moderate this image?
		//-----------------------------------------

		if( ! $image['image_media'] )
		{
			if( $this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) || $_isOwner )
			{
				$image['image_control_mod']	= true;
			}
		}

		return $image;	
	}
}